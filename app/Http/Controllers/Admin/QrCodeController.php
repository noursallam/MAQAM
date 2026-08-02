<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryPrize;
use App\Models\QrCode;
use App\Services\QrBatchGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class QrCodeController extends Controller
{
    public function index(Request $request): View
    {
        $codes = QrCode::with('categoryPrize')
            ->when($request->batch_id, fn ($q, $batch) => $q->where('batch_id', $batch))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $batchesRaw = QrCode::query()
            ->selectRaw('batch_id, category_id, COUNT(*) as total, MIN(generated_at) as generated_at')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id', 'category_id')
            ->orderByDesc('generated_at')
            ->limit(50)
            ->get();

        $batches = $batchesRaw->map(function ($batch) {
            $batch->active_count = QrCode::where('batch_id', $batch->batch_id)->where('status', 'active')->count();
            $batch->used_count = QrCode::where('batch_id', $batch->batch_id)->where('status', 'used')->count();

            return $batch;
        });

        $categories = CategoryPrize::whereIn('id', $batches->pluck('category_id')->filter())->get()->keyBy('id');

        return view('admin.qr-codes.index', [
            'codes' => $codes,
            'batches' => $batches,
            'categories' => $categories,
            'prizeCategories' => CategoryPrize::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.qr-codes.generate', [
            'prizeCategories' => CategoryPrize::where('is_active', true)->orderBy('name_ar')->get(),
            'maxBatch' => QrBatchGenerator::MAX_BATCH,
        ]);
    }

    public function store(Request $request, QrBatchGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories_prize,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.QrBatchGenerator::MAX_BATCH],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $category = CategoryPrize::findOrFail($data['category_id']);
        $result = $generator->generate($category, (int) $data['quantity']);

        return redirect()
            ->route('admin.qr-codes.create', ['done' => 1, 'batch' => $result['batch_id'], 'count' => $result['count'], 'color' => $result['background_color']])
            ->with('success', __('admin.qr.success_title'))
            ->with('download_batch', $result['batch_id']);
    }

    public function download(string $batchId): BinaryFileResponse
    {
        $path = storage_path('app/qr-batches/'.$batchId.'.zip');
        abort_unless(is_file($path), 404, 'Batch ZIP not found.');

        return response()->download($path, $batchId.'.zip');
    }

    public function downloadJson(string $batchId): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $jsonContent = $this->jsonFromZip($batchId) ?? $this->jsonFromDatabase($batchId);
        abort_unless($jsonContent !== null, 404, __('admin.qr.batch_json_missing'));

        return response($jsonContent, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$batchId.'.json"',
        ]);
    }

    protected function jsonFromZip(string $batchId): ?string
    {
        $path = storage_path('app/qr-batches/'.$batchId.'.zip');
        if (! is_file($path)) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $jsonContent = $zip->getFromName($batchId.'.json');
        $zip->close();

        return $jsonContent === false ? null : $jsonContent;
    }

    protected function jsonFromDatabase(string $batchId): ?string
    {
        $codes = QrCode::query()
            ->with('categoryPrize')
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get();

        if ($codes->isEmpty()) {
            return null;
        }

        $category = $codes->first()?->categoryPrize;

        $firstGenerated = $codes->sortBy('generated_at')->first()?->generated_at;

        $payload = [
            'batch_id' => $batchId,
            'generated_at' => $firstGenerated?->toIso8601String() ?? now()->toIso8601String(),
            'exported_from' => 'database',
            'category' => $category ? [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'points_value' => $category->points_value,
                'background_color' => $category->background_color,
            ] : null,
            'quantity' => $codes->count(),
            'codes' => $codes->map(fn (QrCode $code) => [
                'serial_code' => $code->serial_code,
                'category_id' => $code->category_id,
                'points_awarded' => $code->points_awarded,
                'status' => $code->status,
                'generated_at' => $code->generated_at?->toDateTimeString() ?? $code->created_at?->toDateTimeString(),
                'batch_id' => $code->batch_id,
            ])->values()->all(),
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'json_file' => ['required', 'file', 'mimes:json'],
        ]);

        $jsonContent = file_get_contents($request->file('json_file')->getPathname());
        $data = json_decode($jsonContent, true);

        if (!isset($data['batch_id'], $data['codes']) || !is_array($data['codes'])) {
            return back()->with('error', 'Invalid JSON backup file format.');
        }

        $batchId = $data['batch_id'];
        $restoredCount = 0;
        $skippedCount = 0;

        foreach ($data['codes'] as $codeData) {
            $serial = $codeData['serial_code'] ?? null;
            
            // Check if code already exists
            if (QrCode::where('serial_code', $serial)->exists()) {
                $skippedCount++;
                continue;
            }

            // Restore the code
            QrCode::create([
                'serial_code' => $serial,
                'category_id' => $codeData['category_id'] ?? null,
                'points_awarded' => $codeData['points_awarded'] ?? 0,
                'status' => $codeData['status'] ?? 'active',
                'generated_at' => $codeData['generated_at'] ?? now(),
                'batch_id' => $batchId,
            ]);

            $restoredCount++;
        }

        return back()->with('success', "Restored {$restoredCount} codes from batch {$batchId}. Skipped {$skippedCount} existing codes.");
    }

    public function destroyBatch(Request $request, string $batchId): RedirectResponse
    {
        $batchId = trim($batchId);
        abort_if($batchId === '', 404);

        $query = QrCode::query()->where('batch_id', $batchId);
        $total = (clone $query)->count();
        abort_if($total === 0, 404);

        $usedCount = (clone $query)->where('status', 'used')->count();
        if ($usedCount > 0 && ! $request->boolean('force')) {
            return back()->with('error', __('admin.qr.batch_has_used', ['count' => $usedCount]));
        }

        $deleted = $query->delete();

        $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        return redirect()
            ->route('admin.qr-codes.index')
            ->with('success', __('admin.qr.batch_deleted', ['count' => $deleted]));
    }

    public function destroy(QrCode $qr_code): RedirectResponse
    {
        abort_if($qr_code->status === 'used', 422, __('admin.qr.cannot_delete_used'));

        $qr_code->delete();

        return back()->with('success', __('admin.success'));
    }
}
