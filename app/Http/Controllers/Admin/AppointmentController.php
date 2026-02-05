<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;
use App\Services\BookingService;
use App\Services\AvailabilityService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AppointmentController extends Controller
{
    private BookingService $bookingService;
    private AvailabilityService $availabilityService;
    private NotificationService $notificationService;

    public function __construct(
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        NotificationService $notificationService
    ) {
        $this->bookingService = $bookingService;
        $this->availabilityService = $availabilityService;
        $this->notificationService = $notificationService;
    }

    /**
     * GET /admin/appointments
     * 
     * List appointments with filtering.
     */
    public function index(Request $request): View
    {
        $query = Appointment::with(['client', 'service']);

        // Filter by date
        if ($request->filled('date')) {
            $query->forDate(Carbon::parse($request->date));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $appointments = $query->orderByDesc('date')
            ->orderByDesc('start_time')
            ->paginate(25);

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'filters' => $request->only(['date', 'status', 'search']),
        ]);
    }

    /**
     * GET /admin/calendar
     * 
     * Calendar view (day or week).
     */
    public function calendar(Request $request): View
    {
        $view = $request->get('view', 'week');
        $date = $request->filled('date') 
            ? Carbon::parse($request->date) 
            : Carbon::today();

        if ($view === 'day') {
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();
        } else {
            $startDate = $date->copy()->startOfWeek();
            $endDate = $date->copy()->endOfWeek();
        }

        $appointments = Appointment::with(['client', 'service'])
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.appointments.calendar', [
            'appointments' => $appointments,
            'view' => $view,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * GET /admin/appointments/{appointment}
     * 
     * Show appointment details.
     */
    public function show(Appointment $appointment): View
    {
        $appointment->load(['client', 'service', 'payments']);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * GET /admin/appointments/create
     * 
     * Show create appointment form.
     */
    public function create(Request $request): View
    {
        $services = Service::active()->ordered()->get();
        $clients = Client::orderBy('name')->get();

        return view('admin.appointments.create', [
            'services' => $services,
            'clients' => $clients,
            'preselectedDate' => $request->get('date'),
            'preselectedTime' => $request->get('time'),
        ]);
    }

    /**
     * POST /admin/appointments
     * 
     * Create a new appointment (admin-created, no payment).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = Service::findOrFail($request->service_id);
        $date = Carbon::parse($request->date);
        $startTime = $request->start_time . ':00';

        // Find or create client
        $client = Client::findOrCreateByEmail($request->client_email, [
            'name' => $request->client_name,
            'phone' => $request->client_phone,
        ]);

        try {
            $appointment = $this->bookingService->createAdminBooking(
                $service,
                $date,
                $startTime,
                $client,
                $request->notes
            );

            return redirect()
                ->route('admin.appointments.show', $appointment)
                ->with('success', 'Appointment created successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * GET /admin/appointments/{appointment}/edit
     * 
     * Show edit appointment form.
     */
    public function edit(Appointment $appointment): View
    {
        $services = Service::active()->ordered()->get();

        return view('admin.appointments.edit', [
            'appointment' => $appointment,
            'services' => $services,
        ]);
    }

    /**
     * PUT /admin/appointments/{appointment}
     * 
     * Update appointment.
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:confirmed,completed,cancelled,no_show',
        ]);

        $date = Carbon::parse($request->date);
        $startTime = $request->start_time . ':00';

        // If date/time changed, check availability
        if (
            $appointment->date->format('Y-m-d') !== $date->format('Y-m-d') ||
            $appointment->start_time !== $startTime
        ) {
            try {
                $this->bookingService->rescheduleAppointment($appointment, $date, $startTime);
            } catch (\Exception $e) {
                return back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }

        // Update other fields
        $appointment->notes = $request->notes;
        
        if ($appointment->status !== $request->status) {
            $oldStatus = $appointment->status;
            $appointment->status = $request->status;
            
            if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
                $appointment->cancelled_at = now();
                $this->notificationService->sendBookingCancellation($appointment);
            }
        }
        
        $appointment->save();

        return redirect()
            ->route('admin.appointments.show', $appointment)
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * DELETE /admin/appointments/{appointment}
     * 
     * Cancel/delete appointment.
     */
    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        $reason = $request->get('reason', 'Cancelled by admin');

        $this->bookingService->cancelAppointment($appointment, $reason);
        $this->notificationService->sendBookingCancellation($appointment);

        return redirect()
            ->route('admin.appointments.index')
            ->with('success', 'Appointment cancelled.');
    }

    /**
     * GET /api/admin/appointments
     * 
     * Get appointments as JSON (for calendar AJAX).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $startDate = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : Carbon::today()->startOfMonth();
        
        $endDate = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : Carbon::today()->endOfMonth();

        $appointments = Appointment::with(['client', 'service'])
            ->forDateRange($startDate, $endDate)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->client->name . ' - ' . $appointment->service->name,
                    'start' => $appointment->start_date_time->toIso8601String(),
                    'end' => $appointment->end_date_time->toIso8601String(),
                    'status' => $appointment->status,
                    'url' => route('admin.appointments.show', $appointment),
                    'backgroundColor' => match($appointment->status) {
                        'confirmed' => '#10b981',
                        'completed' => '#3b82f6',
                        'cancelled' => '#ef4444',
                        'no_show' => '#f59e0b',
                        default => '#6b7280',
                    },
                ];
            });

        return response()->json($appointments);
    }
}
