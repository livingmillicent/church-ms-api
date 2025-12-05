<?php

namespace App\Http\Controllers;

use App\Models\ChurchAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view assets'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'asset_type' => 'nullable|string',
            'condition' => 'nullable|in:excellent,good,fair,poor,broken',
            'is_available' => 'nullable|boolean',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:id,name,purchase_date,value',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $query = ChurchAsset::with(['assignedUser:id,name']);

        if ($request->has('asset_type')) {
            $query->where('asset_type', $validated['asset_type']);
        }

        if ($request->has('condition')) {
            $query->where('condition', $validated['condition']);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $validated['is_available']);
        }

        if ($request->has('search')) {
            $search = $validated['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $sortField = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;
        $assets = $query->paginate($perPage);

        // Summary stats
        $totalValue = ChurchAsset::sum('value');
        $totalAssets = ChurchAsset::count();
        $availableAssets = ChurchAsset::where('is_available', true)->count();

        return response()->json([
            'success' => true,
            'data' => $assets,
            'summary' => [
                'total_value' => number_format($totalValue, 2),
                'total_assets' => $totalAssets,
                'available_assets' => $availableAssets,
                'assigned_assets' => $totalAssets - $availableAssets
            ],
            'message' => 'Assets retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create assets'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'asset_type' => 'required|string',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'location' => 'required|string',
            'condition' => 'required|in:excellent,good,fair,poor,broken',
            'is_available' => 'boolean',
            'maintenance_schedule' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $asset = ChurchAsset::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $asset->load('assignedUser'),
            'message' => 'Asset created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('view assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view assets'
            ], 403);
        }

        $asset = ChurchAsset::with(['assignedUser:id,name,email,phone'])->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $asset,
            'message' => 'Asset retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('update assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update assets'
            ], 403);
        }

        $asset = ChurchAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'asset_type' => 'sometimes|string',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'location' => 'sometimes|string',
            'condition' => 'sometimes|in:excellent,good,fair,poor,broken',
            'is_available' => 'boolean',
            'maintenance_schedule' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $asset->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $asset->fresh()->load('assignedUser'),
            'message' => 'Asset updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('delete assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete assets'
            ], 403);
        }

        $asset = ChurchAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    }

    public function assign(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('assign assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign assets'
            ], 403);
        }

        $asset = ChurchAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        if (!$asset->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Asset is already assigned'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $asset->update([
            'assigned_to' => $request->user_id,
            'is_available' => false
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset->fresh()->load('assignedUser'),
            'message' => 'Asset assigned successfully'
        ]);
    }

    public function unassign(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('unassign assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to unassign assets'
            ], 403);
        }

        $asset = ChurchAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $asset->update([
            'assigned_to' => null,
            'is_available' => true
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset->fresh(),
            'message' => 'Asset unassigned successfully'
        ]);
    }

    public function maintenance(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('maintain assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add maintenance records'
            ], 403);
        }

        $asset = ChurchAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'performed_by' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule = $asset->maintenance_schedule ?? [];
        $schedule[] = $validator->validated();

        $asset->update([
            'maintenance_schedule' => $schedule
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset->fresh(),
            'message' => 'Maintenance record added successfully'
        ]);
    }

    public function types(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view assets')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view asset types'
            ], 403);
        }

        $types = ChurchAsset::select('asset_type')
            ->distinct()
            ->pluck('asset_type');

        return response()->json([
            'success' => true,
            'data' => $types,
            'message' => 'Asset types retrieved successfully'
        ]);
    }
}
