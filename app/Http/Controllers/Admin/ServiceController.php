<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    /**
     * GET /admin/services
     */
    public function index(): View
    {
        $services = Service::withTrashed()->ordered()->get();

        return view('admin.services.index', [
            'services' => $services,
        ]);
    }

    /**
     * GET /admin/services/create
     */
    public function create(): View
    {
        return view('admin.services.create');
    }

    /**
     * POST /admin/services
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'price_cents' => (int) ($request->price * 100),
            'deposit_cents' => $request->deposit ? (int) ($request->deposit * 100) : null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * GET /admin/services/{service}/edit
     */
    public function edit(Service $service): View
    {
        return view('admin.services.edit', [
            'service' => $service,
        ]);
    }

    /**
     * PUT /admin/services/{service}
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'price' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'price_cents' => (int) ($request->price * 100),
            'deposit_cents' => $request->deposit ? (int) ($request->deposit * 100) : null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * DELETE /admin/services/{service}
     */
    public function destroy(Service $service): RedirectResponse
    {
        // Soft delete to preserve history
        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Service archived.');
    }

    /**
     * POST /admin/services/{service}/restore
     */
    public function restore(int $id): RedirectResponse
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->restore();

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Service restored.');
    }
}
