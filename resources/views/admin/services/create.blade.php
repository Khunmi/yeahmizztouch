@extends('layouts.admin')

@section('title', isset($service) ? 'Edit Service' : 'New Service')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.services.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Services
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ isset($service) ? 'Edit Service' : 'New Service' }}</h1>

    <form method="POST" action="{{ isset($service) ? route('admin.services.update', $service) : route('admin.services.store') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        @csrf
        @if(isset($service)) @method('PUT') @endif

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $service->name ?? '') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Women's Haircut">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
            <textarea name="description" id="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Includes wash, cut, and style...">{{ old('description', $service->description ?? '') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes ?? 60) }}" required min="15" max="480" step="15" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('duration_minutes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                <input type="number" name="price" id="price" value="{{ old('price', isset($service) ? $service->price : '') }}" required min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="75.00">
                @error('price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="deposit" class="block text-sm font-medium text-gray-700 mb-1">Deposit ($) <span class="text-gray-400">(optional)</span></label>
                <input type="number" name="deposit" id="deposit" value="{{ old('deposit', isset($service) ? $service->deposit : '') }}" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Leave blank for default">
                @error('deposit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-500">Overrides default deposit percentage</p>
            </div>
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 mr-2">
                <span class="text-sm text-gray-700">Active (visible for online booking)</span>
            </label>
        </div>

        <div class="pt-4 flex gap-3">
            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium">
                {{ isset($service) ? 'Update Service' : 'Create Service' }}
            </button>
            <a href="{{ route('admin.services.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
