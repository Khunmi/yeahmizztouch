@extends('layouts.app')

@section('title', 'Select Date & Time')

@section('content')
<div x-data="bookingCalendar()" x-init="init()" class="max-w-2xl mx-auto">
    <a href="{{ route('booking.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to services
    </a>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $service->name }}</h2>
                <p class="text-sm text-gray-500">{{ $service->formatted_duration }}</p>
            </div>
            <span class="text-xl font-bold text-gray-900">{{ $service->formatted_price }}</span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Select a Date</h3>
        
        <div class="flex items-center justify-between mb-4">
            <button @click="previousMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span class="text-lg font-medium" x-text="currentMonthName"></span>
            <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-2">
            <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']">
                <div class="text-center text-xs font-medium text-gray-500 py-2" x-text="day"></div>
            </template>
        </div>
        <div class="grid grid-cols-7 gap-1">
            <template x-for="day in calendarDays" :key="day.date">
                <button 
                    @click="selectDate(day)"
                    :disabled="!day.isCurrentMonth || day.isPast || !day.isAvailable"
                    :class="{
                        'bg-blue-600 text-white': selectedDate === day.date,
                        'hover:bg-gray-100': day.isCurrentMonth && !day.isPast && day.isAvailable && selectedDate !== day.date,
                        'text-gray-300 cursor-not-allowed': !day.isCurrentMonth || day.isPast || !day.isAvailable,
                        'text-gray-900': day.isCurrentMonth && !day.isPast && day.isAvailable
                    }"
                    class="aspect-square flex items-center justify-center text-sm rounded-lg transition-colors"
                    x-text="day.dayNumber">
                </button>
            </template>
        </div>
    </div>

    <div x-show="selectedDate" x-cloak class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Select a Time
            <span class="text-sm font-normal text-gray-500" x-text="formattedSelectedDate"></span>
        </h3>
        
        <div x-show="loadingSlots" class="flex justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <div x-show="!loadingSlots && slots.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            <template x-for="slot in slots" :key="slot.start_time">
                <button 
                    @click="selectSlot(slot)"
                    :class="{
                        'bg-blue-600 text-white border-blue-600': selectedSlot?.start_time === slot.start_time,
                        'hover:border-blue-300 hover:bg-blue-50': selectedSlot?.start_time !== slot.start_time
                    }"
                    class="py-3 px-2 text-sm font-medium border border-gray-200 rounded-lg transition-colors"
                    x-text="slot.formatted_start">
                </button>
            </template>
        </div>

        <div x-show="!loadingSlots && slots.length === 0" class="text-center py-8 text-gray-500">
            No available times for this date.
        </div>
    </div>

    <div x-show="selectedSlot" x-cloak>
        <button 
            @click="continueToDetails()"
            :disabled="creatingHold"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold py-4 px-6 rounded-lg transition-colors">
            <span x-show="!creatingHold">Continue to Details</span>
            <span x-show="creatingHold">Reserving...</span>
        </button>
    </div>

    <div x-show="error" x-cloak class="mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg" x-text="error"></div>
</div>

@push('scripts')
<script>
function bookingCalendar() {
    return {
        serviceId: {{ $service->id }},
        currentMonth: new Date(),
        selectedDate: null,
        selectedSlot: null,
        slots: [],
        loadingSlots: false,
        creatingHold: false,
        error: null,
        availableDates: [],

        async init() {
            await this.loadAvailableDates();
        },

        get currentMonthName() {
            return this.currentMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        get formattedSelectedDate() {
            if (!this.selectedDate) return '';
            const date = new Date(this.selectedDate + 'T12:00:00');
            return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const days = [];
            
            for (let i = 0; i < firstDay.getDay(); i++) {
                const date = new Date(year, month, -i);
                days.unshift({
                    date: this.formatDate(date),
                    dayNumber: date.getDate(),
                    isCurrentMonth: false,
                    isPast: true,
                    isAvailable: false
                });
            }

            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                const dateStr = this.formatDate(date);
                days.push({
                    date: dateStr,
                    dayNumber: i,
                    isCurrentMonth: true,
                    isPast: date < today,
                    isAvailable: this.availableDates.includes(dateStr)
                });
            }

            return days;
        },

        formatDate(date) {
            return date.toISOString().split('T')[0];
        },

        previousMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() - 1);
            this.loadAvailableDates();
        },

        nextMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1);
            this.loadAvailableDates();
        },

        async loadAvailableDates() {
            const startDate = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth(), 1);
            const endDate = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1, 0);
            
            const response = await fetch(`/api/availability/dates?service_id=${this.serviceId}&start_date=${this.formatDate(startDate)}&end_date=${this.formatDate(endDate)}`);
            const data = await response.json();
            this.availableDates = (data.data || [])
                .filter(d => d.has_availability)
                .map(d => d.date);
        },

        async selectDate(day) {
            if (!day.isCurrentMonth || day.isPast || !day.isAvailable) return;
            
            this.selectedDate = day.date;
            this.selectedSlot = null;
            this.loadingSlots = true;
            this.error = null;

            const response = await fetch(`/api/availability?service_id=${this.serviceId}&date=${day.date}`);
            const data = await response.json();
            this.slots =
                data?.data?.slots ??
                data?.data ??
                data?.slots ??
                [];

            if (!Array.isArray(this.slots)) this.slots = [];
            this.loadingSlots = false;
        },

        selectSlot(slot) {
            this.selectedSlot = slot;
            this.error = null;
        },

        async continueToDetails() {
            if (!this.selectedSlot || this.creatingHold) return;

            this.creatingHold = true;
            this.error = null;

            const response = await fetch('/book/hold', {
                method: 'POST',
                headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                service_id: this.serviceId,
                date: this.selectedDate,
                start_time: `${this.selectedSlot.time}:00`
                })
            });

            const data = await response.json();

            if (response.ok) {
                const holdUuid = data?.data?.hold_uuid ?? data?.data?.uuid ?? data?.hold_uuid;
                window.location.href = `/book/details/${holdUuid}`;
            } else {
                this.error = data?.message || data?.error || 'This time slot is no longer available.';
                this.creatingHold = false;
            }
        }

    }
}
</script>
@endpush
@endsection
