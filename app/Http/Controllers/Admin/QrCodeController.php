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

    public function downloadJson(string $batchId): BinaryFileResponse
    {
        $path = storage_path('app/qr-batches/'.$batchId.'.zip');
        abort_unless(is_file($path), 404, 'Batch ZIP not found.');

        // Extract JSON from ZIP
        $zip = new ZipArchive;
        $zip->open($path);
        $jsonFile = $batchId.'.json';
        $jsonContent = $zip->getFromName($jsonFile);
        $zip->close();

        if ($jsonContent === false) {
            abort(404, 'JSON backup not found in ZIP.');
        }

        return response()->make($jsonContent, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$batchId.'.json"',
        ]);
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

    public function destroy(QrCode $qr_code): RedirectResponse
    {
        abort_if($qr_code->status === 'used', 422, 'Cannot delete a used QR code.');
        
        // Warning: This will permanently delete the code
        $qr_code->delete();

        return back()->with('success', __('admin.success'));
    }
}
