<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Client;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    private AvailabilityService $availabilityService;
    private BookingService $bookingService;
    private PaymentService $paymentService;

    public function __construct(
        AvailabilityService $availabilityService,
        BookingService $bookingService,
        PaymentService $paymentService
    ) {
        $this->availabilityService = $availabilityService;
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
    }

    /**
     * GET /book
     * 
     * Show the booking page (service selection).
     */
    public function index(): View
    {
        $services = Service::active()->ordered()->get();

        return view('booking.index', [
            'services' => $services,
        ]);
    }

    /**
     * GET /book/{service}
     * 
     * Show date/time selection for a service.
     */
    public function selectDateTime(Service $service): View
    {
        if (!$service->is_active) {
            abort(404);
        }

        return view('booking.select-datetime', [
            'service' => $service,
        ]);
    }

    /**
     * POST /api/bookings/hold
     * 
     * Create a temporary hold on a time slot.
     */
    public function createHold(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ]);
        // 👇 NORMALIZE HERE
        $startTime = strlen($request->start_time) === 5
            ? $request->start_time . ':00'
            : $request->start_time;

        $service = Service::findOrFail($request->service_id);
        
        if (!$service->is_active) {
            return response()->json([
                'error' => 'Service not available',
            ], 400);
        }

        $date = Carbon::parse($request->date);
        $sessionId = $request->session()->getId();

        try {
            $hold = $this->bookingService->createHold(
                $service,
                $date,
                $startTime,
                $sessionId
            );

            return response()->json([
                'data' => [
                    'hold_uuid' => $hold->uuid,
                    'expires_at' => $hold->expires_at->toIso8601String(),
                    'remaining_minutes' => $hold->remaining_minutes,
                    'service' => [
                        'name' => $service->name,
                        'formatted_price' => $service->formatted_price,
                    ],
                    'date' => $hold->date->format('Y-m-d'),
                    'formatted_date' => $hold->date->format('l, F j, Y'),
                    'formatted_time' => $hold->formatted_time,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 409); // Conflict - slot taken
        }
    }

    /**
     * GET /book/details/{holdUuid}
     * 
     * Show client details form.
     */
    public function showDetailsForm(string $holdUuid): View|RedirectResponse
    {
        $hold = $this->bookingService->getHoldByUuid($holdUuid);

        if (!$hold || $hold->isExpired()) {
            return redirect()->route('booking.index')
                ->with('error', 'Your booking session has expired. Please start again.');
        }

        return view('booking.details', [
            'hold' => $hold,
            'service' => $hold->service,
        ]);
    }

    /**
     * POST /book/details/{holdUuid}
     * 
     * Process client details and redirect to payment.
     */
    public function processDetails(Request $request, string $holdUuid): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $hold = $this->bookingService->getHoldByUuid($holdUuid);

        if (!$hold || $hold->isExpired()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Session expired'], 410);
            }
            return redirect()->route('booking.index')
                ->with('error', 'Your booking session has expired. Please start again.');
        }

        // Find or create client
        $client = Client::findOrCreateByEmail($request->email, [
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        // Store client ID in session for payment
        $request->session()->put('booking_client_id', $client->id);
        $request->session()->put('booking_hold_uuid', $hold->uuid);

        // Create Stripe checkout session
        try {
            $checkoutSession = $this->paymentService->createCheckoutSession(
                $hold,
                $hold->service,
                $client
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'checkout_url' => $checkoutSession->url,
                ]);
            }

            return redirect()->away($checkoutSession->url);

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Payment setup failed'], 500);
            }
            return back()->with('error', 'Unable to process payment. Please try again.');
        }
    }

    /**
     * GET /book/success
     * 
     * Handle successful payment return.
     */
    public function success(Request $request): View|RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('booking.index');
        }

        // Get payment record
        $payment = $this->paymentService->getPaymentBySession($sessionId);

        if (!$payment || !$payment->appointment_id) {
            // Payment might still be processing via webhook
            // Show a "processing" message that polls for completion
            return view('booking.processing', [
                'session_id' => $sessionId,
            ]);
        }

        $appointment = $payment->appointment;

        // Clear session data
        $request->session()->forget(['booking_client_id', 'booking_hold_uuid']);

        return view('booking.success', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * GET /book/cancel
     * 
     * Handle payment cancellation.
     */
    public function cancel(Request $request): View
    {
        $holdUuid = $request->query('hold');

        // Release the hold if it exists
        if ($holdUuid) {
            $hold = $this->bookingService->getHoldByUuid($holdUuid);
            if ($hold) {
                $this->bookingService->releaseHold($hold);
            }
        }

        // Clear session data
        $request->session()->forget(['booking_client_id', 'booking_hold_uuid']);

        return view('booking.cancel');
    }

    /**
     * GET /api/booking/status/{sessionId}
     * 
     * Check booking status (for polling after payment).
     */
    public function checkStatus(string $sessionId): JsonResponse
    {
        $payment = $this->paymentService->getPaymentBySession($sessionId);

        if (!$payment) {
            return response()->json([
                'status' => 'not_found',
            ], 404);
        }

        if ($payment->appointment_id) {
            $appointment = $payment->appointment;
            return response()->json([
                'status' => 'complete',
                'appointment' => [
                    'uuid' => $appointment->uuid,
                    'confirmation_url' => route('booking.confirmation', $appointment->uuid),
                ],
            ]);
        }

        if ($payment->status === 'failed') {
            return response()->json([
                'status' => 'failed',
            ]);
        }

        return response()->json([
            'status' => 'processing',
        ]);
    }

    /**
     * GET /book/confirmation/{uuid}
     * 
     * Show appointment confirmation page.
     */
    public function confirmation(string $uuid): View
    {
        $appointment = Appointment::where('uuid', $uuid)->firstOrFail();

        return view('booking.confirmation', [
            'appointment' => $appointment,
        ]);
    }
}
