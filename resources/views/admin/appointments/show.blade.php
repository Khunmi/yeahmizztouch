@extends('layouts.admin')

@section('title', 'Appointment Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.appointments.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Appointments
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $appointment->service->name }}</h1>
                    <p class="text-gray-500">Confirmation #{{ strtoupper(substr($appointment->uuid, 0, 8)) }}</p>
                </div>
                <div>{!! $appointment->status_badge !!}</div>
            </div>

            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Date</dt>
                    <dd class="font-medium">{{ $appointment->formatted_date }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Time</dt>
                    <dd class="font-medium">{{ $appointment->formatted_time }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Duration</dt>
                    <dd class="font-medium">{{ $appointment->service->formatted_duration }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Service Price</dt>
                    <dd class="font-medium">{{ $appointment->service->formatted_price }}</dd>
                </div>
            </dl>

            @if($appointment->notes)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <dt class="text-sm text-gray-500 mb-1">Notes</dt>
                    <dd class="text-gray-900">{{ $appointment->notes }}</dd>
                </div>
            @endif
        </div>

        <!-- Client Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Client Information</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Name</dt>
                    <dd class="font-medium">{{ $appointment->client->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd><a href="mailto:{{ $appointment->client->email }}" class="text-blue-600 hover:text-blue-800">{{ $appointment->client->email }}</a></dd>
                </div>
                @if($appointment->client->phone)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Phone</dt>
                    <dd><a href="tel:{{ $appointment->client->phone }}" class="text-blue-600 hover:text-blue-800">{{ $appointment->client->phone }}</a></dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Payments -->
        @if($appointment->payments->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h2>
            <div class="space-y-3">
                @foreach($appointment->payments as $payment)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="font-medium">{{ $payment->formatted_amount }}</p>
                        <p class="text-sm text-gray-500">{{ $payment->type_label }} • {{ $payment->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div>{!! $payment->status_badge !!}</div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between font-medium">
                <span>Total Paid</span>
                <span>{{ $appointment->formatted_total_paid }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Actions Sidebar -->
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
            <div class="space-y-3">
                @if($appointment->status === 'confirmed')
                    <a href="{{ route('admin.appointments.edit', $appointment) }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium">
                        Edit Appointment
                    </a>
                    <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="date" value="{{ $appointment->date->format('Y-m-d') }}">
                        <input type="hidden" name="start_time" value="{{ substr($appointment->start_time, 0, 5) }}">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium">
                            Mark Completed
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="date" value="{{ $appointment->date->format('Y-m-d') }}">
                        <input type="hidden" name="start_time" value="{{ substr($appointment->start_time, 0, 5) }}">
                        <input type="hidden" name="status" value="no_show">
                        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-sm font-medium">
                            Mark No Show
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.appointments.destroy', $appointment) }}" onsubmit="return confirm('Cancel this appointment?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-sm font-medium">
                            Cancel Appointment
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-500 text-center">
                        This appointment is {{ $appointment->status }}.
                    </p>
                @endif
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-sm text-gray-500">
            <p><strong>Created:</strong> {{ $appointment->created_at->format('M j, Y g:i A') }}</p>
            @if($appointment->is_admin_created)
                <p class="mt-1"><strong>Source:</strong> Admin</p>
            @else
                <p class="mt-1"><strong>Source:</strong> Online Booking</p>
            @endif
        </div>
    </div>
</div>
@endsection
