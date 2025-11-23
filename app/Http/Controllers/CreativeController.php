<?php

namespace App\Http\Controllers;

use App\Http\Requests\Creative\FilterCreativeAssetsRequest;
use App\Http\Requests\Creative\StoreCreativeAssetRequest;
use App\Http\Requests\Creative\UpdateCreativeAssetRequest;
use App\Http\Resources\Creative\CreativeAssetCollection;
use App\Http\Resources\Creative\CreativeAssetResource;
use App\Models\CreativeAsset;
use App\Services\CreativeService;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;

class CreativeController extends Controller
{
    use ApiResponse;

    protected CreativeService $creativeService;

    public function __construct(CreativeService $creativeService)
    {
        $this->creativeService = $creativeService;
    }

    /**
     * Display a listing of creative assets
     */
    public function index(FilterCreativeAssetsRequest $request)
    {
        try {
            $validated = $request->validated();
            $orgId = session('current_org_id') ?? auth()->user()->org_id;

            $query = CreativeAsset::where('org_id', $orgId);

            // Apply filters
            if (!empty($validated['asset_type'])) {
                $query->where('asset_type', $validated['asset_type']);
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['search'])) {
                $query->where('asset_name', 'ilike', "%{$validated['search']}%");
            }

            // Sorting
            $query->orderBy(
                $validated['sort_by'] ?? 'created_at',
                $validated['sort_direction'] ?? 'desc'
            );

            // Pagination
            $assets = $query->paginate($validated['per_page'] ?? 20);

            return new CreativeAssetCollection($assets);

        } catch (\Exception $e) {
            return $this->serverError('فشل جلب المواد الإبداعية' . ': ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created creative asset
     */
    public function store(StoreCreativeAssetRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $file = $request->file('file');

            $asset = $this->creativeService->uploadAsset($validated, $file);

            return (new CreativeAssetResource($asset))
                ->additional([
                    'success' => true,
                    'message' => 'تم رفع المادة الإبداعية بنجاح',
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            return $this->serverError('فشل رفع المادة الإبداعية' . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified creative asset
     */
    public function show(string $assetId): JsonResponse
    {
        try {
            $orgId = session('current_org_id') ?? auth()->user()->org_id;

            $asset = CreativeAsset::where('org_id', $orgId)
                ->where('asset_id', $assetId)
                ->firstOrFail();

            $this->authorize('view', $asset);

            return (new CreativeAssetResource($asset))
                ->additional([
                    'success' => true,
                ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('لم يتم العثور على المادة الإبداعية');
        } catch (\Exception $e) {
            return $this->serverError('فشل جلب المادة الإبداعية' . ': ' . $e->getMessage());
        }
    }

    /**
     * Update the specified creative asset
     */
    public function update(UpdateCreativeAssetRequest $request, string $assetId): JsonResponse
    {
        try {
            $orgId = session('current_org_id') ?? auth()->user()->org_id;

            $asset = CreativeAsset::where('org_id', $orgId)
                ->where('asset_id', $assetId)
                ->firstOrFail();

            $validated = $request->validated();

            $asset->update($validated);

            return (new CreativeAssetResource($asset->fresh()))
                ->additional([
                    'success' => true,
                    'message' => 'تم تحديث المادة الإبداعية بنجاح',
                ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('لم يتم العثور على المادة الإبداعية');
        } catch (\Exception $e) {
            return $this->serverError('فشل التحديث' . ': ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified creative asset
     */
    public function destroy(string $assetId): JsonResponse
    {
        try {
            $deleted = $this->creativeService->deleteAsset($assetId);

            if ($deleted) {
                return $this->success(null, 'تم حذف المادة الإبداعية بنجاح');
            }

            return $this->serverError('فشل حذف المادة الإبداعية');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('لم يتم العثور على المادة الإبداعية');
        } catch (\Exception $e) {
            return $this->serverError('فشل الحذف' . ': ' . $e->getMessage());
        }
    }

    /**
     * Approve a creative asset
     */
    public function approve(string $assetId): JsonResponse
    {
        try {
            $approved = $this->creativeService->approveAsset($assetId, auth()->id());

            if ($approved) {
                $asset = CreativeAsset::findOrFail($assetId);

                return (new CreativeAssetResource($asset))
                    ->additional([
                        'success' => true,
                        'message' => 'تم اعتماد المادة الإبداعية بنجاح',
                    ]);
            }

            return $this->serverError('فشل اعتماد المادة الإبداعية');

        } catch (\Exception $e) {
            return $this->serverError('فشل اعتماد المادة الإبداعية' . ': ' . $e->getMessage());
        }
    }

    /**
     * Reject a creative asset
     */
    public function reject(Request $request, string $assetId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $rejected = $this->creativeService->rejectAsset($assetId, $request->reason);

            if ($rejected) {
                $asset = CreativeAsset::findOrFail($assetId);

                return (new CreativeAssetResource($asset))
                    ->additional([
                        'success' => true,
                        'message' => 'تم رفض المادة الإبداعية',
                    ]);
            }

            return $this->serverError('فشل رفض المادة الإبداعية');

        } catch (\Exception $e) {
            return $this->serverError('فشل رفض المادة الإبداعية' . ': ' . $e->getMessage());
        }
    }
}