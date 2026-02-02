<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $slots = $this->availabilityService->getAvailableSlots(
            (int) $request->input('service_id'),
            $request->input('date')
        );

        return response()->json([
            'data' => [
                'date' => $request->input('date'),
                'service_id' => (int) $request->input('service_id'),
                'slots' => $slots,
                'available_count' => $slots->where('available', true)->count(),
            ],
        ]);
    }

    public function dates(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);

        $dates = $this->availabilityService->getAvailableDates(
            (int) $request->input('service_id'),
            (int) $request->input('days', 30)
        );

        return response()->json([
            'data' => $dates,
        ]);
    }
}
