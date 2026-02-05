@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-600">{{ $today->format('l, F j, Y') }}</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm text-gray-500">Today's Appointments</p>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['today_count'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm text-gray-500">This Week</p>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['week_count'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm text-gray-500">Total Clients</p>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_clients'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm text-gray-500">Total Revenue</p>
        <p class="text-3xl font-bold text-gray-900">${{ number_format($stats['total_revenue'] / 100, 0) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-8">
    <!-- Today's Schedule -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Today's Schedule</h2>
            <a href="{{ route('admin.appointments.create', ['date' => $today->format('Y-m-d')]) }}" class="text-sm text-blue-600 hover:text-blue-800">+ Add</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($todayAppointments as $appointment)
                <a href="{{ route('admin.appointments.show', $appointment) }}" class="block px-6 py-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-gray-900">{{ $appointment->client->name }}</p>
                            <p class="text-sm text-gray-500">{{ $appointment->service->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900">{{ $appointment->formatted_start_time }}</p>
                            <p class="text-sm text-gray-500">{{ $appointment->service->formatted_duration }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    No appointments scheduled for today.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Upcoming -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Upcoming</h2>
            <a href="{{ route('admin.appointments.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($upcomingAppointments as $appointment)
                <a href="{{ route('admin.appointments.show', $appointment) }}" class="block px-6 py-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-gray-900">{{ $appointment->client->name }}</p>
                            <p class="text-sm text-gray-500">{{ $appointment->service->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900">{{ $appointment->date->format('M j') }}</p>
                            <p class="text-sm text-gray-500">{{ $appointment->formatted_start_time }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    No upcoming appointments.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
