@extends('layouts.app')

@section('title', 'Your Details')

@section('content')
<div x-data="{ submitting: false }" class="max-w-lg mx-auto">
    <a href="{{ route('booking.select-datetime', $service) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Change date/time
    </a>

    <!-- Booking Summary -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-semibold text-blue-900 mb-2">{{ $service->name }}</h3>
        <p class="text-sm text-blue-800">
            {{ $hold->date->format('l, F j, Y') }} at {{ $hold->formatted_time }}
        </p>
        <p class="text-sm text-blue-800 mt-1">
            Duration: {{ $service->formatted_duration }} • Price: {{ $service->formatted_price }}
        </p>
    </div>

    <!-- Hold Timer -->
    <div x-data="holdTimer({{ $hold->expires_at->timestamp * 1000 }})" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
        <p class="text-sm text-yellow-800 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Your slot is reserved for <strong x-text="timeRemaining"></strong></span>
        </p>
    </div>

    <!-- Details Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Your Information</h2>

        <form method="POST" action="{{ route('booking.details.process', $hold->uuid) }}" @submit="submitting = true">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Jane Smith"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="jane@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Confirmation will be sent to this email</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-gray-400">(optional)</span></label>
                    <input 
                        type="tel" 
                        name="phone" 
                        id="phone" 
                        value="{{ old('phone') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="(555) 123-4567"
                    >
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-600">
                        @if(config('salon.require_full_payment'))
                            Total Due
                        @else
                            Deposit Due Now
                        @endif
                    </span>
                    <span class="text-xl font-bold text-gray-900">
                        @if(config('salon.require_full_payment'))
                            {{ $service->formatted_price }}
                        @elseif($service->deposit_cents)
                            ${{ number_format($service->deposit_cents / 100, 2) }}
                        @else
                            ${{ number_format($service->price_cents * config('salon.deposit_percentage') / 10000, 2) }}
                        @endif
                    </span>
                </div>

                <button 
                    type="submit"
                    :disabled="submitting"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center"
                >
                    <span x-show="!submitting">Continue to Payment</span>
                    <span x-show="submitting" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <p class="mt-4 text-xs text-center text-gray-500">
        By continuing, you agree to our cancellation policy. 
        Cancellations must be made at least {{ config('salon.cancellation_hours') }} hours in advance.
    </p>
</div>

@push('scripts')
<script>
function holdTimer(expiresAt) {
    return {
        timeRemaining: '',
        interval: null,

        init() {
            this.updateTime();
            this.interval = setInterval(() => this.updateTime(), 1000);
        },

        updateTime() {
            const now = Date.now();
            const remaining = Math.max(0, expiresAt - now);
            
            if (remaining === 0) {
                clearInterval(this.interval);
                window.location.href = '{{ route("booking.index") }}?expired=1';
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            this.timeRemaining = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }
}
</script>
@endpush
@endsection
