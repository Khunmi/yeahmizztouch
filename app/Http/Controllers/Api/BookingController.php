<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\HoldAppointmentRequest;
use App\Models\Appointment;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    public function hold(HoldAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->bookingService->holdAppointment($request->validated());

            return response()->json([
                'data' => $this->formatAppointment($appointment),
                'message' => 'Appointment held successfully. Complete payment to confirm.',
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function cancel(CancelAppointmentRequest $request, int $id): JsonResponse
    {
        try {
            $appointment = $this->bookingService->cancelAppointment(
                $id,
                $request->input('reason')
            );

            $response = [
                'data' => $this->formatAppointment($appointment),
                'message' => 'Appointment cancelled.',
            ];

            if ($appointment->cancellation_charge_cents > 0) {
                $response['warning'] = sprintf(
                    'Late cancellation fee of $%.2f may be charged.',
                    $appointment->cancellation_charge_cents / 100
                );
            }

            return response()->json($response);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['client', 'service'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    private function formatAppointment(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'hold_expires_at' => $appointment->hold_expires_at?->toIso8601String(),
            'service' => [
                'id' => $appointment->service->id,
                'name' => $appointment->service->name,
                'duration_minutes' => $appointment->service->duration_minutes,
            ],
            'client' => [
                'id' => $appointment->client->id,
                'name' => $appointment->client->full_name,
                'email' => $appointment->client->email,
            ],
            'pricing' => [
                'service_price_cents' => $appointment->service_price_cents,
                'deposit_cents' => $appointment->deposit_amount_cents,
                'late_fee_cents' => $appointment->late_fee_cents,
                'squeeze_in_fee_cents' => $appointment->squeeze_in_fee_cents,
            ],
        ];
    }
}
