@extends('layouts.app')

@section('title', 'Processing Payment')

@section('content')
<div x-data="paymentStatus()" x-init="checkStatus()" class="max-w-lg mx-auto text-center">
    <!-- Loading State -->
    <div x-show="status === 'processing'" class="py-12">
        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-6" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Processing Your Booking</h1>
        <p class="text-gray-600">Please wait while we confirm your payment...</p>
    </div>

    <!-- Error State -->
    <div x-show="status === 'failed'" x-cloak class="py-12">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h1>
        <p class="text-gray-600 mb-6">We couldn't process your payment. Please try again.</p>
        <a href="{{ route('booking.index') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg">
            Try Again
        </a>
    </div>

    <!-- Timeout State -->
    <div x-show="status === 'timeout'" x-cloak class="py-12">
        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Taking Longer Than Expected</h1>
        <p class="text-gray-600 mb-6">If your payment was successful, you should receive a confirmation email shortly.</p>
        <a href="{{ route('booking.index') }}" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg">
            Return to Booking
        </a>
    </div>
</div>

@push('scripts')
<script>
function paymentStatus() {
    return {
        status: 'processing',
        attempts: 0,
        maxAttempts: 20,

        async checkStatus() {
            const sessionId = '{{ $session_id }}';
            
            while (this.attempts < this.maxAttempts && this.status === 'processing') {
                this.attempts++;
                
                try {
                    const response = await fetch(`/api/booking/status/${sessionId}`);
                    const data = await response.json();

                    if (data.status === 'complete') {
                        window.location.href = data.appointment.confirmation_url;
                        return;
                    } else if (data.status === 'failed') {
                        this.status = 'failed';
                        return;
                    }
                } catch (e) {
                    console.error('Status check failed', e);
                }

                await new Promise(resolve => setTimeout(resolve, 2000));
            }

            if (this.status === 'processing') {
                this.status = 'timeout';
            }
        }
    }
}
</script>
@endpush
@endsection
