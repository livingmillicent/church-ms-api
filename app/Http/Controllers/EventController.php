<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use App\Models\Attendance;
use App\Models\ChurchMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('view events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view events'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'event_type' => 'nullable|string',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|string|in:start_time,end_time,title',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $query = ChurchEvent::query();

        if ($request->has('event_type')) {
            $query->where('event_type', $validated['event_type']);
        }

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        if ($request->has('start_date')) {
            $query->whereDate('start_time', '>=', $validated['start_date']);
        }

        if ($request->has('end_date')) {
            $query->whereDate('end_time', '<=', $validated['end_date']);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($validated) {
                $q->where('title', 'like', "%{$validated['search']}%")
                  ->orWhere('description', 'like', "%{$validated['search']}%")
                  ->orWhere('location', 'like', "%{$validated['search']}%");
            });
        }

        $query->orderBy(
            $validated['sort_by'] ?? 'start_time',
            $validated['sort_order'] ?? 'desc'
        );

        $events = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Events retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('create events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create events'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:sunday_service,midweek_service,prayer_meeting,youth_service,children_service,conference,outreach,special_event',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'required|string',
            'participants' => 'nullable|array',
            'speakers' => 'nullable|array',
            'budget' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = 'scheduled';

        $event = ChurchEvent::create($validated);

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Event created successfully'
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || !$user->can('view events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view events'
            ], 403);
        }

        $event = ChurchEvent::with(['attendances.member.user:id,name'])->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $attendanceStats = [
            'total_attended' => $event->attendances()->count(),
            'checked_in' => $event->attendances()->whereNotNull('check_in_time')->count(),
            'checked_out' => $event->attendances()->whereNotNull('check_out_time')->count(),
            'attendance_rate' => $event->max_attendees
                ? round(($event->attendee_count / $event->max_attendees) * 100, 2)
                : null
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'event' => $event,
                'attendance_stats' => $attendanceStats
            ],
            'message' => 'Event retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || !$user->can('update events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update events'
            ], 403);
        }

        $event = ChurchEvent::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'sometimes|in:sunday_service,midweek_service,prayer_meeting,youth_service,children_service,conference,outreach,special_event',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'location' => 'sometimes|string',
            'participants' => 'nullable|array',
            'speakers' => 'nullable|array',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
            'max_attendees' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $event->fresh(),
            'message' => 'Event updated successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || !$user->can('delete events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete events'
            ], 403);
        }

        $event = ChurchEvent::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }

    public function upcoming(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('view events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view events'
            ], 403);
        }

        $events = ChurchEvent::where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Upcoming events retrieved successfully'
        ]);
    }

    public function past(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('view events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view past events'
            ], 403);
        }

        $events = ChurchEvent::where('end_time', '<', now())
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Past events retrieved successfully'
        ]);
    }

    public function register(Request $request, $eventId)
    {
        $user = $request->user();

        if (!$user || !$user->can('manage attendance')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage attendance'
            ], 403);
        }

        $event = ChurchEvent::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        if (!$event->requires_registration) {
            return response()->json([
                'success' => false,
                'message' => 'This event does not require registration'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $exists = Attendance::where('event_id', $eventId)
            ->where('member_id', $validated['member_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Member already registered for this event'
            ], 400);
        }

        if ($event->max_attendees) {
            $count = Attendance::where('event_id', $eventId)->count();
            if ($count >= $event->max_attendees) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event has reached maximum capacity'
                ], 400);
            }
        }

        $attendance = Attendance::create([
            'event_id' => $eventId,
            'member_id' => $validated['member_id'],
            'check_in_time' => null,
            'check_out_time' => null,
            'is_present' => false
        ]);

        return response()->json([
            'success' => true,
            'data' => $attendance->load('member.user'),
            'message' => 'Successfully registered for event'
        ], 201);
    }

    public function attendance(Request $request, $eventId)
    {
        $user = $request->user();

        if (!$user || !$user->can('manage attendance')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage attendance'
            ], 403);
        }

        $event = ChurchEvent::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'action' => 'required|in:check_in,check_out'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $attendance = Attendance::where('event_id', $eventId)
            ->where('member_id', $validated['member_id'])
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Member not registered for this event'
            ], 404);
        }

        if ($validated['action'] === 'check_in') {
            $attendance->update([
                'check_in_time' => now(),
                'is_present' => true
            ]);
            $message = 'Checked in successfully';
        } else {
            $attendance->update([
                'check_out_time' => now()
            ]);
            $message = 'Checked out successfully';
        }

        return response()->json([
            'success' => true,
            'data' => $attendance->fresh(),
            'message' => $message
        ]);
    }

    public function calendar(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->can('view events')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view calendar events'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'start' => 'required|date',
            'end' => 'required|date|after:start'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $events = ChurchEvent::whereBetween('start_time', [$request->start, $request->end])
            ->orWhereBetween('end_time', [$request->start, $request->end])
            ->get(['id', 'title', 'start_time', 'end_time', 'event_type', 'location'])
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'type' => $event->event_type,
                    'location' => $event->location,
                    'color' => $this->getEventColor($event->event_type)
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Calendar events retrieved successfully'
        ]);
    }

    private function getEventColor($type)
    {
        return match($type) {
            'sunday_service' => '#3B82F6',
            'midweek_service' => '#10B981',
            'prayer_meeting' => '#8B5CF6',
            'youth_service' => '#EF4444',
            'children_service' => '#F59E0B',
            'conference' => '#EC4899',
            'outreach' => '#06B6D4',
            default => '#6B7280'
        };
    }
}
