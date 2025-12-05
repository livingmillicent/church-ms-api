<?php

namespace App\Http\Controllers;

use App\Models\ChurchMember;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ChurchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    // List members
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('view members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_type' => 'nullable|in:regular,visitor,new_convert,inactive',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:id,created_at,member_type',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = ChurchMember::with('user');
        if ($request->filled('member_type')) {
            $query->where('member_type', $request->member_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
            );
        }

        $sortField = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $perPage = $request->per_page ?? 15;

        $members = $query->orderBy($sortField, $sortOrder)->paginate($perPage);

        return response()->json(['success' => true, 'data' => $members, 'message' => 'Members retrieved successfully']);
    }

    // Create member
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('create members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'member_type' => 'required|in:regular,visitor,new_convert,inactive',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'baptism_date' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'occupation' => 'nullable|string',
            'home_cell_group' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'membership_date' => now(),
                'is_active' => true,
            ]);

            $user->assignRole('member');

            $member = ChurchMember::create([
                'user_id' => $user->id,
                'member_type' => $data['member_type'],
                'baptism_date' => $data['baptism_date'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'home_cell_group' => $data['home_cell_group'] ?? null,
            ]);

            DB::commit();

            return response()->json(['success' => true, 'data' => $member->load('user'), 'message' => 'Member created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create member', 'error' => $e->getMessage()], 500);
        }
    }

    // Show a single member
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('view members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $member = ChurchMember::with(['user', 'contributions', 'attendances'])->find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $totalContributions = $member->total_contributions;
        $attendanceRate = $this->calculateAttendanceRate($member->id);

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'stats' => [
                    'total_contributions' => $totalContributions,
                    'attendance_rate' => round($attendanceRate, 2),
                    'contribution_count' => $member->contributions->count(),
                    'attendance_count' => $member->attendances->count(),
                ]
            ],
            'message' => 'Member retrieved successfully'
        ]);
    }

    // Update member
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('edit members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $member = ChurchMember::with('user')->find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'member_type' => 'sometimes|in:regular,visitor,new_convert,inactive',
            'baptism_date' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'family_members' => 'nullable|array',
            'spiritual_gifts' => 'nullable|array',
            'ministries' => 'nullable|array',
            'home_cell_group' => 'nullable|string',
            'user.name' => 'sometimes|string|max:255',
            'user.email' => 'sometimes|email|unique:users,email,' . $member->user_id,
            'user.phone' => 'sometimes|string',
            'user.address' => 'nullable|string',
            'user.date_of_birth' => 'nullable|date',
            'user.gender' => 'nullable|in:male,female,other',
            'user.occupation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            // Update member fields
            $member->update(array_filter($data));

            // Update user fields if provided
            if (isset($data['user'])) {
                $member->user->update(array_filter($data['user']));
            }

            DB::commit();

            return response()->json(['success' => true, 'data' => $member->fresh()->load('user'), 'message' => 'Member updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update member', 'error' => $e->getMessage()], 500);
        }
    }

    // Activate member
    public function activate(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('manage members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $member = ChurchMember::with('user')->find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $member->user->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Member activated successfully']);
    }

    // Deactivate member
    public function deactivate(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->can('manage members')) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $member = ChurchMember::with('user')->find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $member->user->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Member deactivated successfully']);
    }

    // Private helper for attendance rate
    private function calculateAttendanceRate($memberId)
    {
        $totalEvents = ChurchEvent::where('start_time', '>=', now()->subMonth())->count();
        $attendedEvents = Attendance::where('member_id', $memberId)
            ->whereHas('event', fn($q) => $q->where('start_time', '>=', now()->subMonth()))
            ->count();

        return $totalEvents > 0 ? ($attendedEvents / $totalEvents) * 100 : 0;
    }
}
