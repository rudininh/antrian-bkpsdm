<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CounterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Counters/Index', [
            'counters' => Counter::query()
                ->latest()
                ->get()
                ->map(fn (Counter $counter) => [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    'code' => $counter->code,
                    'location' => $counter->location,
                    'is_active' => $counter->is_active,
                    'queues_count' => $counter->queues()->count(),
                    'created_at' => $counter->created_at?->format('d M Y H:i'),
                ]),
            'meta' => [
                'title' => 'Kelola Loket',
                'description' => 'Atur loket operasional dan status aktifnya.',
                'dateLabel' => now()->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:counters,code'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Counter::query()->create([
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Loket berhasil ditambahkan.');
    }

    public function update(Request $request, Counter $counter): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:counters,code,'.$counter->id],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $counter->update([
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Loket berhasil diperbarui.');
    }

    public function destroy(Counter $counter): RedirectResponse
    {
        $counter->delete();

        return back()->with('success', 'Loket berhasil dihapus.');
    }
}
