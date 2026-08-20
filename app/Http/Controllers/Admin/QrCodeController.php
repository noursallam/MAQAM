<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryPrize;
use App\Models\QrBatch;
use App\Models\QrCode;
use App\Services\QrBatchGenerator;
use Illuminate\Http\JsonResponse;
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

        $meta = QrBatch::query()
            ->whereIn('batch_id', $batchesRaw->pluck('batch_id'))
            ->get()
            ->keyBy('batch_id');

        $batches = $batchesRaw->map(function ($batch) use ($meta) {
            $batch->active_count = QrCode::where('batch_id', $batch->batch_id)->where('status', 'active')->count();
            $batch->used_count = QrCode::where('batch_id', $batch->batch_id)->where('status', 'used')->count();
            $batch->zip_exists = is_file(storage_path('app/qr-batches/'.$batch->batch_id.'.zip'));
            $batch->meta = $meta->get($batch->batch_id);
            $batch->build_status = $batch->meta?->status
                ?? ($batch->zip_exists ? QrBatch::STATUS_READY : 'zip_missing');

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

    public function tracker(Request $request): View
    {
        $lifecycle = $request->string('lifecycle')->toString();

        $codes = QrCode::with(['categoryPrize', 'usedByCustomer.user', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(1)])
            ->when($request->batch_id, fn ($q, $batch) => $q->where('batch_id', $batch))
            ->when($request->q, fn ($q, $term) => $q->where('serial_code', 'like', "%{$term}%"))
            ->when($lifecycle === 'generated', fn ($q) => $q->where('status', 'active')->whereNull('printed_at')->whereNull('sold_at')->whereNull('used_at'))
            ->when($lifecycle === 'printed', fn ($q) => $q->where('status', 'active')->whereNotNull('printed_at')->whereNull('sold_at')->whereNull('used_at'))
            ->when($lifecycle === 'sold', fn ($q) => $q->where('status', 'active')->whereNotNull('sold_at')->whereNull('used_at'))
            ->when($lifecycle === 'scanned', fn ($q) => $q->where(fn ($qq) => $qq->where('status', 'used')->orWhereNotNull('used_at')))
            ->when($lifecycle === 'expired', fn ($q) => $q->where('status', 'expired'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $summary = [
            'generated' => QrCode::where('status', 'active')->whereNull('printed_at')->whereNull('sold_at')->whereNull('used_at')->count(),
            'printed' => QrCode::where('status', 'active')->whereNotNull('printed_at')->whereNull('sold_at')->whereNull('used_at')->count(),
            'sold' => QrCode::where('status', 'active')->whereNotNull('sold_at')->whereNull('used_at')->count(),
            'scanned' => QrCode::where(fn ($q) => $q->where('status', 'used')->orWhereNotNull('used_at'))->count(),
            'expired' => QrCode::where('status', 'expired')->count(),
            'total' => QrCode::count(),
        ];

        return view('admin.qr-codes.tracker', [
            'codes' => $codes,
            'summary' => $summary,
            'prizeCategories' => CategoryPrize::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function markPrinted(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'batch_id' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:qr_codes,id'],
        ]);

        $query = QrCode::query()->where('status', 'active')->whereNull('used_at');
        if (! empty($data['batch_id'])) {
            $query->where('batch_id', $data['batch_id']);
        } elseif (! empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        } else {
            return back()->withErrors(['batch_id' => __('admin.qr.lifecycle_select_required')]);
        }

        $count = $query->whereNull('printed_at')->update(['printed_at' => now()]);

        return back()->with('success', __('admin.qr.marked_printed', ['count' => $count]));
    }

    public function markSold(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'batch_id' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:qr_codes,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
        ]);

        $query = QrCode::query()->where('status', 'active')->whereNull('used_at');
        if (! empty($data['batch_id'])) {
            $query->where('batch_id', $data['batch_id']);
        } elseif (! empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        } else {
            return back()->withErrors(['batch_id' => __('admin.qr.lifecycle_select_required')]);
        }

        $payload = ['sold_at' => now()];
        if (! empty($data['order_id'])) {
            $payload['sold_order_id'] = $data['order_id'];
        }

        $count = (clone $query)->whereNull('printed_at')->update(array_merge($payload, ['printed_at' => now()]));
        $count += $query->whereNotNull('printed_at')->whereNull('sold_at')->update($payload);

        return back()->with('success', __('admin.qr.marked_sold', ['count' => $count]));
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
        $batch = $generator->queueGeneration(
            $category,
            (int) $data['quantity'],
            $data['notes'] ?? null
        );

        $bgHex = $generator->normalizeHex($category->background_color ?: '#C5A059');

        return redirect()
            ->route('admin.qr-codes.create', [
                'processing' => 1,
                'batch' => $batch->batch_id,
                'count' => $batch->quantity,
                'color' => $bgHex,
            ])
            ->with('success', __('admin.qr.queued_title'));
    }

    public function status(Request $request, string $batchId, QrBatchGenerator $generator): JsonResponse
    {
        $batch = QrBatch::query()->where('batch_id', $batchId)->first();
        $zipExists = is_file(storage_path('app/qr-batches/'.$batchId.'.zip'));
        $codeCount = QrCode::query()->where('batch_id', $batchId)->count();

        if (! $batch && $codeCount === 0) {
            return response()->json(['ok' => false, 'message' => 'Batch not found'], 404);
        }

        // App-driven build: each poll processes a small chunk (no cron / worker needed).
        $shouldWork = $request->boolean('work', true);
        $needsWork = ! $zipExists && ($batch?->isBuilding() || $batch?->status === QrBatch::STATUS_ZIP_MISSING || ! $batch);

        if ($shouldWork && $needsWork) {
            try {
                if ($batch && $batch->status === QrBatch::STATUS_ZIP_MISSING) {
                    $generator->resetForRebuild($batchId);
                }
                $batch = $generator->processChunk($batchId);
                $zipExists = is_file(storage_path('app/qr-batches/'.$batchId.'.zip'));
            } catch (\Throwable $e) {
                $batch = QrBatch::query()->where('batch_id', $batchId)->first();
            }
        }

        $status = $batch?->status
            ?? ($zipExists ? QrBatch::STATUS_READY : QrBatch::STATUS_ZIP_MISSING);

        return response()->json([
            'ok' => true,
            'batch_id' => $batchId,
            'status' => $status,
            'quantity' => $batch?->quantity ?? $codeCount,
            'processed_count' => $batch?->processed_count ?? ($zipExists ? $codeCount : 0),
            'progress' => $batch?->progressPercent() ?? ($zipExists ? 100 : 0),
            'zip_ready' => $zipExists && $status === QrBatch::STATUS_READY,
            'error_message' => $batch?->error_message,
            'download_url' => ($zipExists && $status === QrBatch::STATUS_READY)
                ? route('admin.qr-codes.download', $batchId)
                : null,
        ]);
    }

    public function rebuild(string $batchId, QrBatchGenerator $generator): RedirectResponse
    {
        $batchId = trim($batchId);
        abort_if($batchId === '', 404);
        abort_unless(QrCode::query()->where('batch_id', $batchId)->exists(), 404);

        $batch = $generator->resetForRebuild($batchId);

        return redirect()
            ->route('admin.qr-codes.create', [
                'processing' => 1,
                'batch' => $batch->batch_id,
                'count' => $batch->quantity,
            ])
            ->with('success', __('admin.qr.rebuild_queued'));
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

        if (! isset($data['batch_id'], $data['codes']) || ! is_array($data['codes'])) {
            return back()->with('error', 'Invalid JSON backup file format.');
        }

        $batchId = $data['batch_id'];
        $restoredCount = 0;
        $skippedCount = 0;
        $categoryId = null;

        foreach ($data['codes'] as $codeData) {
            $serial = $codeData['serial_code'] ?? null;

            if (QrCode::where('serial_code', $serial)->exists()) {
                $skippedCount++;
                continue;
            }

            $categoryId = $codeData['category_id'] ?? $categoryId;

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

        if ($restoredCount > 0 && $categoryId) {
            QrBatch::query()->updateOrCreate(
                ['batch_id' => $batchId],
                [
                    'category_id' => $categoryId,
                    'quantity' => QrCode::query()->where('batch_id', $batchId)->count(),
                    'status' => is_file(storage_path('app/qr-batches/'.$batchId.'.zip'))
                        ? QrBatch::STATUS_READY
                        : QrBatch::STATUS_ZIP_MISSING,
                ]
            );
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
        QrBatch::query()->where('batch_id', $batchId)->delete();

        $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $dir = storage_path('app/qr-batches/'.$batchId);
        if (is_dir($dir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($dir);
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
