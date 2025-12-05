<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{

    public function index(Request $request)
    {
        $query = Department::with(['leader:id,name', 'members:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $departments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $departments,
            'message' => 'Departments retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department = Department::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $department->load(['leader', 'members']),
            'message' => 'Department created successfully'
        ], 201);
    }

    public function show($id)
    {
        $department = Department::with(['leader', 'members'])->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $department,
            'message' => 'Department retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $department->fresh()->load(['leader', 'members']),
            'message' => 'Department updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }

    public function addMember(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $members = $department->members ?? [];
        if (!in_array($request->user_id, $members)) {
            $members[] = $request->user_id;
            $department->update(['members' => $members]);
        }

        return response()->json([
            'success' => true,
            'data' => $department->fresh()->load('members'),
            'message' => 'Member added to department successfully'
        ]);
    }

    public function removeMember(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $members = $department->members ?? [];
        $index = array_search($request->user_id, $members);
        
        if ($index !== false) {
            unset($members[$index]);
            $department->update(['members' => array_values($members)]);
        }

        return response()->json([
            'success' => true,
            'data' => $department->fresh()->load('members'),
            'message' => 'Member removed from department successfully'
        ]);
    }

    public function members($id)
    {
        $department = Department::with(['members:id,name,email,phone'])->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $department->members,
            'message' => 'Department members retrieved successfully'
        ]);
    }
}