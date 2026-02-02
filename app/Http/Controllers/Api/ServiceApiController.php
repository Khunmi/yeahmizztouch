<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceApiController extends Controller
{
    /**
     * GET /api/services
     * 
     * List all active services.
     */
    public function index(): JsonResponse
    {
        $services = Service::active()
            ->ordered()
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration_minutes' => $service->duration_minutes,
                    'formatted_duration' => $service->formatted_duration,
                    'price_cents' => $service->price_cents,
                    'formatted_price' => $service->formatted_price,
                ];
            });

        return response()->json([
            'data' => $services,
        ]);
    }

    /**
     * GET /api/services/{id}
     * 
     * Get a single service.
     */
    public function show(Service $service): JsonResponse
    {
        if (!$service->is_active) {
            return response()->json([
                'error' => 'Service not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'formatted_duration' => $service->formatted_duration,
                'price_cents' => $service->price_cents,
                'formatted_price' => $service->formatted_price,
            ],
        ]);
    }
}
