<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlackoutDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BlackoutController extends Controller
{
    /**
     * GET /admin/blackouts
     */
    public function index(): View
    {
        $blackouts = BlackoutDate::upcoming()->get();
        $past = BlackoutDate::where('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        return view('admin.blackouts.index', [
            'blackouts' => $blackouts,
            'past' => $past,
        ]);
    }

    /**
     * GET /admin/blackouts/create
     */
    public function create(): View
    {
        return view('admin.blackouts.create');
    }

    /**
     * POST /admin/blackouts
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'is_full_day' => 'boolean',
            'start_time' => 'required_if:is_full_day,false|nullable|date_format:H:i',
            'end_time' => 'required_if:is_full_day,false|nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        BlackoutDate::create([
            'date' => Carbon::parse($request->date),
            'start_time' => $request->boolean('is_full_day') ? null : $request->start_time,
            'end_time' => $request->boolean('is_full_day') ? null : $request->end_time,
            'reason' => $request->reason,
        ]);

        return redirect()
            ->route('admin.blackouts.index')
            ->with('success', 'Blocked time added.');
    }

    /**
     * DELETE /admin/blackouts/{blackout}
     */
    public function destroy(BlackoutDate $blackout): RedirectResponse
    {
        $blackout->delete();

        return redirect()
            ->route('admin.blackouts.index')
            ->with('success', 'Blocked time removed.');
    }
}
