<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * GET /admin
     * 
     * Admin dashboard - today's overview.
     */
    public function index(): View
    {
        $today = Carbon::today();

        $todayAppointments = Appointment::with(['client', 'service'])
            ->forDate($today)
            ->confirmed()
            ->orderBy('start_time')
            ->get();

        $upcomingAppointments = Appointment::with(['client', 'service'])
            ->where('date', '>', $today)
            ->confirmed()
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $stats = [
            'today_count' => $todayAppointments->count(),
            'week_count' => Appointment::forDateRange(
                $today,
                $today->copy()->endOfWeek()
            )->confirmed()->count(),
            'total_clients' => Client::count(),
            'total_revenue' => Appointment::whereHas('payments', function ($q) {
                $q->where('status', 'succeeded');
            })->sum('payments.amount_cents') ?? 0,
        ];

        return view('admin.dashboard', [
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'stats' => $stats,
            'today' => $today,
        ]);
    }
}
