<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Services/Index', [
            'services' => Service::query()
                ->latest()
                ->get()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'description' => $service->description,
                    'is_active' => $service->is_active,
                    'queues_count' => $service->queues()->count(),
                    'created_at' => $service->created_at?->format('d M Y H:i'),
                ]),
            'meta' => [
                'title' => 'Kelola Layanan',
                'description' => 'Atur jenis layanan yang tersedia untuk pengambilan antrian.',
                'dateLabel' => now()->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:services,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Service::query()->create([
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:services,code,'.$service->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->update([
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return back()->with('success', 'Layanan berhasil dihapus.');
    }
}
