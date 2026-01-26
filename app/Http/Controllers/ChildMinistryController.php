<?php

namespace App\Http\Controllers;

use App\Models\ChildMinistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChildMinistryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view children ministry')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view children ministry'
            ], 403);
        }

        $query = ChildMinistry::with('creator:id,name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $ministries = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $ministries,
            'message' => 'Children ministries retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create children ministry')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create children ministry'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age_from' => 'required|integer|min:1',
            'age_to' => 'required|integer|gte:age_from',
            'class_teacher' => 'required|string|max:255',
            'total_children' => 'nullable|integer|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ministry = ChildMinistry::create([
            ...$validator->validated(),
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => $ministry,
            'message' => 'Children ministry created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('view children ministry')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission'
            ], 403);
        }

        $ministry = ChildMinistry::with('creator')->find($id);

        if (!$ministry) {
            return response()->json([
                'success' => false,
                'message' => 'Children ministry not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ministry,
            'message' => 'Children ministry retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('update children ministry')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission'
            ], 403);
        }

        $ministry = ChildMinistry::find($id);
        if (!$ministry) {
            return response()->json([
                'success' => false,
                'message' => 'Children ministry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'age_from' => 'sometimes|integer|min:1',
            'age_to' => 'sometimes|integer',
            'class_teacher' => 'sometimes|string|max:255',
            'total_children' => 'nullable|integer|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ministry->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $ministry->fresh(),
            'message' => 'Children ministry updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('delete children ministry')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission'
            ], 403);
        }

        $ministry = ChildMinistry::find($id);
        if (!$ministry) {
            return response()->json([
                'success' => false,
                'message' => 'Children ministry not found'
            ], 404);
        }

        $ministry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Children ministry deleted successfully'
        ]);
    }
}
