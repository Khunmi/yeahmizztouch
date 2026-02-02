<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityApiController extends Controller
{
    private AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * GET /api/availability
     * 
     * Get available time slots for a service on a date.
     * 
     * Query params:
     * - service_id: required
     * - date: required (Y-m-d format)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $service = Service::findOrFail($request->service_id);
        
        if (!$service->is_active) {
            return response()->json([
                'error' => 'Service not available',
            ], 400);
        }

        $date = Carbon::parse($request->date);

        // Validate date is not in the past
        if ($date->isPast() && !$date->isToday()) {
            return response()->json([
                'error' => 'Cannot book appointments in the past',
            ], 400);
        }

        // Validate date is within booking window
        $maxDate = Carbon::today()->addDays(config('salon.max_advance_days', 60));
        if ($date->gt($maxDate)) {
            return response()->json([
                'error' => 'Cannot book more than ' . config('salon.max_advance_days') . ' days in advance',
            ], 400);
        }

        $slots = $this->availabilityService->getAvailableSlots($service, $date);

        return response()->json([
            'data' => [
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('l, F j, Y'),
                'service_id' => $service->id,
                'slots' => $slots->toArray(),
            ],
        ]);
    }

    /**
     * GET /api/availability/dates
     * 
     * Get dates with available slots within a range.
     * 
     * Query params:
     * - service_id: required
     * - start_date: optional (defaults to today)
     * - end_date: optional (defaults to start + 30 days)
     */
    public function dates(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $service = Service::findOrFail($request->service_id);
        
        if (!$service->is_active) {
            return response()->json([
                'error' => 'Service not available',
            ], 400);
        }

        $startDate = $request->start_date 
            ? Carbon::parse($request->start_date) 
            : Carbon::today();
        
        $endDate = $request->end_date 
            ? Carbon::parse($request->end_date) 
            : $startDate->copy()->addDays(30);

        // Limit range to max advance days
        $maxDate = Carbon::today()->addDays(config('salon.max_advance_days', 60));
        if ($endDate->gt($maxDate)) {
            $endDate = $maxDate;
        }

        $availableDates = $this->availabilityService->getAvailableDates($service, $startDate, $endDate);

        return response()->json([
            'data' => [
                'service_id' => $service->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'available_dates' => $availableDates->toArray(),
            ],
        ]);
    }
}
