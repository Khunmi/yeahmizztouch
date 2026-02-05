@extends('layouts.admin')

@section('title', 'Services')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Services</h1>
    <a href="{{ route('admin.services.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        + New Service
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($services as $service)
                <tr class="{{ $service->trashed() ? 'bg-gray-50' : '' }}">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 {{ $service->trashed() ? 'line-through' : '' }}">{{ $service->name }}</p>
                        @if($service->description)
                            <p class="text-sm text-gray-500">{{ Str::limit($service->description, 50) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-900">{{ $service->formatted_duration }}</td>
                    <td class="px-6 py-4 text-gray-900">{{ $service->formatted_price }}</td>
                    <td class="px-6 py-4">
                        @if($service->trashed())
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Archived</span>
                        @elseif($service->is_active)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        @if($service->trashed())
                            <form method="POST" action="{{ route('admin.services.restore', $service->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800">Restore</button>
                            </form>
                        @else
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="inline" onsubmit="return confirm('Archive this service?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
