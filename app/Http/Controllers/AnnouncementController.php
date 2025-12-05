<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_published' => 'nullable|boolean',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:id,publish_date,priority',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $query = Announcement::with(['author:id,name']);

        if ($request->has('priority')) {
            $query->where('priority', $validated['priority']);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $validated['is_published']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $sortField = $validated['sort_by'] ?? 'publish_date';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        $query->orderBy($sortField, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
            'message' => 'Announcements retrieved successfully',
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:publish_date',
            'priority' => 'required|in:low,normal,high,urgent',
            'target_audience' => 'nullable|array',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $request->user()->id; // FIXED

        $announcement = Announcement::create($validated);

        return response()->json([
            'success' => true,
            'data' => $announcement->load('author'),
            'message' => 'Announcement created successfully',
        ], 201);
    }

    public function show($id)
    {
        $announcement = Announcement::with(['author:id,name'])->find($id);

        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $announcement,
            'message' => 'Announcement retrieved successfully',
        ]);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'publish_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after:publish_date',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'target_audience' => 'nullable|array',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $announcement->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $announcement->fresh()->load('author'),
            'message' => 'Announcement updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
            ], 404);
        }

        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }

    public function publish($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
            ], 404);
        }

        $announcement->update(['is_published' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully',
        ]);
    }

    public function unpublish($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
            ], 404);
        }

        $announcement->update(['is_published' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Announcement unpublished successfully',
        ]);
    }

    public function active()
    {
        $announcements = Announcement::where('is_published', true)
            ->whereDate('publish_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhereDate('expiry_date', '>=', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('publish_date', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'content', 'priority', 'publish_date']);

        return response()->json([
            'success' => true,
            'data' => $announcements,
            'message' => 'Active announcements retrieved successfully',
        ]);
    }
}
