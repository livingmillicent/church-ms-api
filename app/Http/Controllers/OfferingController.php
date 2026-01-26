<?php

namespace App\Http\Controllers;

use App\Models\Offering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OfferingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view offerings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view offerings'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string',
            'fund_name' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:id,amount,date,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $query = Offering::with('creator:id,name');

        if (!empty($validated['payment_method'])) {
            $query->where('payment_method', $validated['payment_method']);
        }

        if (!empty($validated['fund_name'])) {
            $query->where('fund_name', $validated['fund_name']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('date', '<=', $validated['date_to']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $sortField = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;
        $offerings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $offerings,
            'summary' => [
                'total_amount' => number_format(Offering::sum('amount'), 2),
                'total_offerings' => Offering::count()
            ],
            'message' => 'Offerings retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create offerings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create offerings'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'fund_name' => 'required|string|max:255',
            'date' => 'required|date',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offering = Offering::create([
            ...$validator->validated(),
            'transaction_id' => 'OFFER-' . now()->year . '-' . Str::upper(Str::random(8)),
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => $offering->load('creator'),
            'message' => 'Offering created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('view offerings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view offerings'
            ], 403);
        }

        $offering = Offering::with('creator:id,name,email')->find($id);

        if (!$offering) {
            return response()->json([
                'success' => false,
                'message' => 'Offering not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $offering,
            'message' => 'Offering retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('update offerings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update offerings'
            ], 403);
        }

        $offering = Offering::find($id);
        if (!$offering) {
            return response()->json([
                'success' => false,
                'message' => 'Offering not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:1',
            'payment_method' => 'sometimes|string',
            'fund_name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offering->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $offering->fresh(),
            'message' => 'Offering updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('delete offerings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete offerings'
            ], 403);
        }

        $offering = Offering::find($id);
        if (!$offering) {
            return response()->json([
                'success' => false,
                'message' => 'Offering not found'
            ], 404);
        }

        $offering->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offering deleted successfully'
        ]);
    }
}
