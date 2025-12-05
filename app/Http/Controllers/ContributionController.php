<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\ChurchMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContributionController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user(); 
    
        if (!$user || !$user->can('view contributions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view contributions'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_id' => 'nullable|exists:members,id',
            'contribution_type' => 'nullable|in:tithe,offering,donation,building_fund,missionary,thanksgiving,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:id,contribution_date,amount',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $query = Contribution::with([
            'member.user:id,name',
            'recorder:id,name'
        ]);

        if ($request->has('member_id')) {
            $query->where('member_id', $validated['member_id']);
        }

        if ($request->has('contribution_type')) {
            $query->where('contribution_type', $validated['contribution_type']);
        }

        if ($request->has('start_date')) {
            $query->whereDate('contribution_date', '>=', $validated['start_date']);
        }

        if ($request->has('end_date')) {
            $query->whereDate('contribution_date', '<=', $validated['end_date']);
        }

        if ($request->has('search')) {
            $search = $validated['search'];
            $query->whereHas('member.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortField = $validated['sort_by'] ?? 'contribution_date';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;
        $contributions = $query->paginate($perPage);

        $totalAmount = $contributions->sum('amount');
        $averageAmount = $contributions->average('amount') ?? 0;

        return response()->json([
            'success' => true,
            'data' => $contributions,
            'summary' => [
                'total_amount' => number_format($totalAmount, 2),
                'average_amount' => number_format($averageAmount, 2),
                'total_count' => $contributions->total()
            ],
            'message' => 'Contributions retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user(); 
    
        if (!$user || !$user->can('create contributions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create contributions'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:0',
            'contribution_type' => 'required|in:tithe,offering,donation,building_fund,missionary,thanksgiving,other',
            'contribution_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,check',
            'reference_number' => 'nullable|string|unique:contributions',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,quarterly,yearly'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['recorded_by'] = $request->user()->id; // FIXED

        $contribution = Contribution::create($validated);

        return response()->json([
            'success' => true,
            'data' => $contribution->load(['member.user', 'recorder']),
            'message' => 'Contribution recorded successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user(); 
    
        if (!$user || !$user->can('view contributions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view contributions'
            ], 403);
        }

        $contribution = Contribution::with(['member.user', 'recorder'])->find($id);

        if (!$contribution) {
            return response()->json([
                'success' => false,
                'message' => 'Contribution not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contribution,
            'message' => 'Contribution retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user(); 
    
        if (!$user || !$user->can('update contributions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update contributions'
            ], 403);
        }

        $contribution = Contribution::find($id);

        if (!$contribution) {
            return response()->json([
                'success' => false,
                'message' => 'Contribution not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'contribution_type' => 'sometimes|in:tithe,offering,donation,building_fund,missionary,thanksgiving,other',
            'contribution_date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,mobile_money,bank_transfer,check',
            'reference_number' => 'nullable|string|unique:contributions,reference_number,' . $id,
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:weekly,monthly,quarterly,yearly'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contribution->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $contribution->fresh()->load(['member.user', 'recorder']),
            'message' => 'Contribution updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user(); 
    
        if (!$user || !$user->can('delete contributions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete contributions'
            ], 403);
        }

        $contribution = Contribution::find($id);

        if (!$contribution) {
            return response()->json([
                'success' => false,
                'message' => 'Contribution not found'
            ], 404);
        }

        $contribution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contribution deleted successfully'
        ]);
    }

    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:daily,weekly,monthly,yearly'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->format('Y-m-d');
        $type = $validated['type'] ?? 'monthly';

        $typeBreakdown = Contribution::select(
                'contribution_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(amount) as average_amount')
            )
            ->whereBetween('contribution_date', [$startDate, $endDate])
            ->groupBy('contribution_type')
            ->get();

        $timeFormat = match($type) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%U',
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m'
        };

        $timeBreakdown = Contribution::select(
                DB::raw("DATE_FORMAT(contribution_date, '{$timeFormat}') as period"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('contribution_date', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $topContributors = Contribution::with('member.user:id,name')
            ->select('member_id', DB::raw('SUM(amount) as total'))
            ->whereBetween('contribution_date', [$startDate, $endDate])
            ->groupBy('member_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $summary = [
            'total_amount' => $typeBreakdown->sum('total_amount'),
            'total_count' => $typeBreakdown->sum('count'),
            'average_per_contribution' => $typeBreakdown->avg('average_amount') ?? 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'report_type' => $type
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'breakdown_by_type' => $typeBreakdown,
                'time_breakdown' => $timeBreakdown,
                'top_contributors' => $topContributors
            ],
            'message' => 'Contribution report generated successfully'
        ]);
    }

    public function memberContributions($memberId)
    {
        $member = ChurchMember::with('user:id,name')->find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found'
            ], 404);
        }

        $contributions = Contribution::where('member_id', $memberId)
            ->with(['recorder:id,name'])
            ->orderBy('contribution_date', 'desc')
            ->paginate(15);

        $summary = [
            'total_contributions' => $member->total_contributions,
            'total_this_year' => $member->contributions()
                ->whereYear('contribution_date', date('Y'))
                ->sum('amount'),
            'total_this_month' => $member->contributions()
                ->whereMonth('contribution_date', date('m'))
                ->whereYear('contribution_date', date('Y'))
                ->sum('amount'),
            'contribution_count' => $contributions->total()
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member->user,
                'summary' => $summary,
                'contributions' => $contributions
            ],
            'message' => 'Member contributions retrieved successfully'
        ]);
    }
}
