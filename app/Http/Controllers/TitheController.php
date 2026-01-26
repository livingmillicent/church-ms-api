<?php

namespace App\Http\Controllers;

use App\Models\Tithe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TitheController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view tithes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view tithes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Tithe::with('creator:id,name');

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('account_name', 'like', "%{$request->search}%");
        }

        $tithes = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $tithes,
            'summary' => [
                'total_amount' => number_format(Tithe::sum('amount'), 2),
                'total_records' => Tithe::count()
            ],
            'message' => 'Tithes retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create tithes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create tithes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tithe = Tithe::create([
            ...$validator->validated(),
            'transaction_id' => 'TITHE-' . now()->year . '-' . Str::upper(Str::random(8)),
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => $tithe->load('creator'),
            'message' => 'Tithe created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('view tithes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view tithes'
            ], 403);
        }

        $tithe = Tithe::with('creator')->find($id);

        if (!$tithe) {
            return response()->json([
                'success' => false,
                'message' => 'Tithe not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tithe,
            'message' => 'Tithe retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('update tithes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update tithes'
            ], 403);
        }

        $tithe = Tithe::find($id);
        if (!$tithe) {
            return response()->json([
                'success' => false,
                'message' => 'Tithe not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:1',
            'payment_method' => 'sometimes|string',
            'date' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tithe->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $tithe->fresh(),
            'message' => 'Tithe updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('delete tithes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete tithes'
            ], 403);
        }

        $tithe = Tithe::find($id);
        if (!$tithe) {
            return response()->json([
                'success' => false,
                'message' => 'Tithe not found'
            ], 404);
        }

        $tithe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tithe deleted successfully'
        ]);
    }
}
