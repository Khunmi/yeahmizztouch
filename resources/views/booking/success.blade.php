@extends('layouts.app')

@section('title', 'Booking Confirmed')

@section('content')
<div class="max-w-lg mx-auto text-center">
    <!-- Success Icon -->
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h1>
    <p class="text-gray-600 mb-8">A confirmation email has been sent to {{ $appointment->client->email }}</p>

    <!-- Appointment Details Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-left mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Appointment Details</h2>
        
        <dl class="space-y-3">
            <div class="flex justify-between">
                <dt class="text-gray-500">Confirmation #</dt>
                <dd class="font-mono text-gray-900">{{ strtoupper(substr($appointment->uuid, 0, 8)) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Service</dt>
                <dd class="text-gray-900">{{ $appointment->service->name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Date</dt>
                <dd class="text-gray-900">{{ $appointment->formatted_date }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Time</dt>
                <dd class="text-gray-900">{{ $appointment->formatted_time }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Duration</dt>
                <dd class="text-gray-900">{{ $appointment->service->formatted_duration }}</dd>
            </div>
        </dl>
    </div>

    <!-- Add to Calendar -->
    <div class="space-y-3">
        <a href="{{ route('booking.index') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
            Book Another Appointment
        </a>
    </div>

    <p class="mt-6 text-sm text-gray-500">
        Need to cancel or reschedule? Contact us at least {{ config('salon.cancellation_hours') }} hours before your appointment.
    </p>
</div>
@endsection
