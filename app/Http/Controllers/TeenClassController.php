<?php

namespace App\Http\Controllers;

use App\Models\TeenClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeenClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view teen classes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission'
            ], 403);
        }

        $classes = TeenClass::latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $classes,
            'message' => 'Teen classes retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create teen classes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age_from' => 'required|integer|min:10',
            'age_to' => 'required|integer|gte:age_from',
            'class_teacher' => 'required|string|max:255',
            'total_teens' => 'nullable|integer|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $class = TeenClass::create([
            ...$validator->validated(),
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => $class,
            'message' => 'Teen class created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $class = TeenClass::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Teen class not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $class,
            'message' => 'Teen class retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $class = TeenClass::find($id);
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Teen class not found'
            ], 404);
        }

        $class->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $class->fresh(),
            'message' => 'Teen class updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $class = TeenClass::find($id);
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Teen class not found'
            ], 404);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teen class deleted successfully'
        ]);
    }
}
