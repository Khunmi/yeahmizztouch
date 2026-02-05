@extends('layouts.app')

@section('title', 'Booking Cancelled')

@section('content')
<div class="max-w-lg mx-auto text-center py-12">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h1>
    <p class="text-gray-600 mb-8">Your booking was not completed. No charges have been made.</p>

    <a href="{{ route('booking.index') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
        Start New Booking
    </a>
</div>
@endsection
