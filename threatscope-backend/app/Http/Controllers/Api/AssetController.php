<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function index(): JsonResponse
    {
        $assets = Asset::latest()->get();

        return response()->json([
            'data' => $assets,
        ], 200);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::create($request->validated());

        return response()->json([
            'message' => 'Asset added to watchlist.',
            'data' => $asset,
        ], 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        return response()->json([
            'data' => $asset,
        ], 200);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $asset->update($request->validated());

        return response()->json([
            'message' => 'Asset updated successfully.',
            'data' => $asset->fresh(),
        ], 200);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'message' => 'Asset removed from watchlist.',
        ], 200);
    }
}
