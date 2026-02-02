@extends('layouts.admin')

@section('title', 'Block Time')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.blackouts.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
</div>

<div class="max-w-md" x-data="{ isFullDay: true }">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Block Time</h1>

    <form method="POST" action="{{ route('admin.blackouts.store') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        @csrf

        <div>
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="date" id="date" value="{{ old('date') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_full_day" value="1" x-model="isFullDay" {{ old('is_full_day', true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 mr-2">
                <span class="text-sm text-gray-700">Block entire day</span>
            </label>
        </div>

        <div x-show="!isFullDay" x-cloak class="grid grid-cols-2 gap-4">
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>

        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-gray-400">(optional)</span></label>
            <input type="text" name="reason" id="reason" value="{{ old('reason') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Holiday, vacation, etc.">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium">
            Block Time
        </button>
    </form>
</div>
@endsection
