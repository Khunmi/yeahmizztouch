@extends('layouts.admin')

@section('title', 'Blocked Time')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Blocked Time</h1>
    <a href="{{ route('admin.blackouts.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        + Block Time
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">Upcoming Blocked Dates</h2>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($blackouts as $blackout)
            <div class="px-6 py-4 flex justify-between items-center">
                <div>
                    <p class="font-medium text-gray-900">{{ $blackout->formatted_date }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $blackout->formatted_time_range }}
                        @if($blackout->reason) • {{ $blackout->reason }} @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.blackouts.destroy', $blackout) }}" onsubmit="return confirm('Remove?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                </form>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-gray-500">No upcoming blocked dates.</div>
        @endforelse
    </div>
</div>
@endsection
