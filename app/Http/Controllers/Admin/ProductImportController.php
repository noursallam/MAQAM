<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSkuGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function __construct(private ProductSkuGenerator $skuGenerator) {}

    public function create(): View
    {
        return view('admin.products.import', [
            'categories' => Category::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'slug']),
            'columns' => $this->columns(),
            'sampleRows' => $this->sampleRows(),
        ]);
    }

    public function template(): StreamedResponse
    {
        $columns = array_column($this->columns(), 'key');
        $sample = $this->sampleRows()[0] ?? [];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="maqam-products-template.csv"',
        ];

        return response()->stream(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);
            fputcsv($out, array_map(fn ($key) => $sample[$key] ?? '', $columns));
            fclose($out);
        }, 200, $headers);
    }

    /**
     * @return list<array{key: string, required: bool, note: string}>
     */
    private function columns(): array
    {
        return [
            ['key' => 'sku', 'required' => false, 'note' => 'col_sku'],
            ['key' => 'name_en', 'required' => true, 'note' => 'col_name'],
            ['key' => 'name_ar', 'required' => true, 'note' => 'col_name'],
            ['key' => 'category_id', 'required' => false, 'note' => 'col_category_id'],
            ['key' => 'category_slug', 'required' => false, 'note' => 'col_category_slug'],
            ['key' => 'price', 'required' => true, 'note' => 'col_price'],
            ['key' => 'stock_quantity', 'required' => false, 'note' => 'col_stock'],
            ['key' => 'description_en', 'required' => false, 'note' => 'col_desc'],
            ['key' => 'description_ar', 'required' => false, 'note' => 'col_desc'],
            ['key' => 'production_code', 'required' => false, 'note' => 'col_production'],
            ['key' => 'is_active', 'required' => false, 'note' => 'col_active'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function sampleRows(): array
    {
        return [
            [
                'sku' => '',
                'name_en' => 'Wall Socket',
                'name_ar' => 'فيش حائط',
                'category_id' => '',
                'category_slug' => 'sockets',
                'price' => '45',
                'stock_quantity' => '100',
                'description_en' => 'Premium wall socket',
                'description_ar' => 'فيش حائط فاخر',
                'production_code' => '',
                'is_active' => '1',
            ],
            [
                'sku' => 'MQ-SW-001',
                'name_en' => 'Light Switch',
                'name_ar' => 'مفتاح إضاءة',
                'category_id' => '',
                'category_slug' => 'switches',
                'price' => '35',
                'stock_quantity' => '50',
                'description_en' => 'Single gang switch',
                'description_ar' => 'مفتاح مفرد',
                'production_code' => 'PR-100',
                'is_active' => '1',
            ],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => __('admin.import.read_failed')]);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->withErrors(['file' => __('admin.import.empty')]);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h, "\xEF\xBB\xBF \t\n\r\0\x0B")), $header);
        $required = ['name_en', 'name_ar', 'price'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);

                return back()->withErrors(['file' => __('admin.import.missing_column', ['col' => $col])]);
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $data = [];
                foreach ($header as $i => $key) {
                    $data[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
                }

                try {
                    $categoryId = $data['category_id'] !== '' ? (int) $data['category_id'] : null;
                    if (! $categoryId && ! empty($data['category_slug'])) {
                        $categoryId = Category::where('slug', $data['category_slug'])->value('id');
                    }
                    if (! $categoryId) {
                        $categoryId = Category::query()->value('id');
                    }
                    if (! $categoryId) {
                        throw new \RuntimeException(__('admin.import.no_category'));
                    }

                    $payload = [
                        'category_id' => $categoryId,
                        'name_en' => $data['name_en'],
                        'name_ar' => $data['name_ar'],
                        'price' => (float) str_replace(',', '', $data['price']),
                        'stock_quantity' => (int) ($data['stock_quantity'] !== '' ? $data['stock_quantity'] : 0),
                        'description_en' => $data['description_en'] ?? null,
                        'description_ar' => $data['description_ar'] ?? null,
                        'production_code' => ($data['production_code'] ?? '') !== '' ? $data['production_code'] : null,
                        'is_active' => ! isset($data['is_active']) || $data['is_active'] === ''
                            ? true
                            : in_array(strtolower($data['is_active']), ['1', 'true', 'yes', 'y'], true),
                    ];

                    $sku = $data['sku'] ?? '';
                    $product = $sku !== ''
                        ? Product::where('sku', $sku)->first()
                        : null;

                    if ($product) {
                        $product->update($payload);
                        $updated++;
                    } else {
                        if ($sku === '') {
                            $payload['sku'] = $this->skuGenerator->makeFromProductData(
                                $payload['name_en'],
                                null,
                                Category::find($categoryId),
                            );
                        } else {
                            $payload['sku'] = $sku;
                        }
                        Product::create($payload);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    if (count($errors) < 8) {
                        $errors[] = __('admin.import.row_error', ['row' => $rowNum, 'msg' => $e->getMessage()]);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->withErrors(['file' => $e->getMessage()]);
        }

        fclose($handle);

        $message = __('admin.import.done', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        if ($errors) {
            return back()->with('success', $message)->with('import_errors', $errors);
        }

        return back()->with('success', $message);
    }
}
