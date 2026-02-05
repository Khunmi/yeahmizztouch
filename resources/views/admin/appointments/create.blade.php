@extends('layouts.admin')

@section('title', 'New Appointment')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.appointments.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
</div>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">New Appointment</h1>

    <form method="POST" action="{{ route('admin.appointments.store') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        @csrf

        <div>
            <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Service</label>
            <select name="service_id" id="service_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <option value="">Select a service...</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                        {{ $service->name }} ({{ $service->formatted_duration }} - {{ $service->formatted_price }})
                    </option>
                @endforeach
            </select>
            @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" id="date" value="{{ old('date', $preselectedDate) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                <input type="time" name="start_time" id="start_time" value="{{ old('start_time', $preselectedTime) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('start_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <hr class="border-gray-200">

        <h3 class="text-lg font-medium text-gray-900">Client Information</h3>

        <div>
            <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="client_name" id="client_name" value="{{ old('client_name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Jane Smith">
            @error('client_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="client_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="client_email" id="client_email" value="{{ old('client_email') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="jane@example.com">
            @error('client_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="client_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-400">(optional)</span></label>
            <input type="tel" name="client_phone" id="client_phone" value="{{ old('client_phone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="(555) 123-4567">
            @error('client_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
            <textarea name="notes" id="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Any special requests...">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium">
                Create Appointment
            </button>
        </div>
    </form>
</div>
@endsection
