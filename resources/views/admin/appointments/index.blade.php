@extends('layouts.admin')

@section('title', 'Appointments')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
    <a href="{{ route('admin.appointments.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        + New Appointment
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <div>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="confirmed" {{ ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="no_show" {{ ($filters['status'] ?? '') === 'no_show' ? 'selected' : '' }}>No Show</option>
            </select>
        </div>
        <div class="flex-1">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search client..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(!empty($filters['date']) || !empty($filters['status']) || !empty($filters['search']))
                <a href="{{ route('admin.appointments.index') }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($appointments as $appointment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900">{{ $appointment->date->format('M j, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->formatted_start_time }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900">{{ $appointment->client->name }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->client->email }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm text-gray-900">{{ $appointment->service->name }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->service->formatted_duration }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {!! $appointment->status_badge !!}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-800">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        No appointments found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-4">
    {{ $appointments->withQueryString()->links() }}
</div>
@endsection
