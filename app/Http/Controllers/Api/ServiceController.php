<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()
            ->active()
            ->ordered()
            ->get([
                'id',
                'name',
                'description',
                'duration_minutes',
                'price_cents',
                'deposit_cents',
                'minimum_age',
            ]);

        return response()->json([
            'data' => $services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => [
                    'cents' => $service->price_cents,
                    'formatted' => '$' . number_format($service->price_cents / 100, 2),
                ],
                'deposit' => [
                    'cents' => $service->deposit_cents,
                    'formatted' => '$' . number_format($service->deposit_cents / 100, 2),
                ],
                'minimum_age' => $service->minimum_age,
            ]),
        ]);
    }
}
