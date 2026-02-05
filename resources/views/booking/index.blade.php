@extends('layouts.app')

@section('title', 'Book an Appointment')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Book an Appointment</h1>
        <p class="mt-2 text-gray-600">Select a service to get started</p>
    </div>

    <div class="space-y-4">
        @forelse ($services as $service)
            <a href="{{ route('booking.select-datetime', $service) }}" 
               class="block bg-white rounded-lg shadow-sm border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $service->name }}</h3>
                        @if ($service->description)
                            <p class="mt-1 text-sm text-gray-600">{{ $service->description }}</p>
                        @endif
                        <p class="mt-2 text-sm text-gray-500">
                            <span class="inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $service->formatted_duration }}
                            </span>
                        </p>
                    </div>
                    <div class="ml-4 text-right">
                        <span class="text-xl font-bold text-gray-900">{{ $service->formatted_price }}</span>
                        <div class="mt-2">
                            <span class="inline-flex items-center text-sm text-blue-600 font-medium">
                                Select
                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <p class="text-gray-500">No services available at this time.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
