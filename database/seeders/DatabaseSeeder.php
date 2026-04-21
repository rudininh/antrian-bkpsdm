<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@bkpsdm.test'],
            [
                'name' => 'Admin BKPSDM',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operator@bkpsdm.test'],
            [
                'name' => 'Operator BKPSDM',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'email_verified_at' => now(),
            ],
        );

        $services = collect([
            ['name' => 'Verifikasi Data', 'code' => 'A', 'description' => 'Layanan verifikasi berkas dan data ASN.'],
            ['name' => 'Kenaikan Pangkat', 'code' => 'B', 'description' => 'Pemrosesan administrasi kenaikan pangkat.'],
            ['name' => 'Mutasi ASN', 'code' => 'C', 'description' => 'Pengelolaan perpindahan dan mutasi pegawai.'],
            ['name' => 'Konsultasi Administrasi', 'code' => 'D', 'description' => 'Sesi konsultasi layanan kepegawaian.'],
        ])->map(fn (array $service) => Service::query()->updateOrCreate(
            ['code' => $service['code']],
            $service + ['is_active' => true],
        ));

        $counters = collect(range(1, 6))->map(fn (int $index) => Counter::query()->updateOrCreate(
            ['code' => 'L'.$index],
            [
                'name' => 'Loket '.$index,
                'location' => 'Lantai 1',
                'is_active' => true,
            ],
        ));

        Call::query()->delete();
        Queue::query()->delete();

        $today = Carbon::today();
        $statuses = ['completed', 'completed', 'completed', 'serving', 'called', 'waiting', 'waiting', 'completed'];

        foreach ($services as $serviceIndex => $service) {
            foreach (range(1, 8) as $number) {
                $status = $statuses[($serviceIndex + $number - 1) % count($statuses)];
                $counter = in_array($status, ['completed', 'serving', 'called'], true)
                    ? $counters[($serviceIndex + $number - 1) % $counters->count()]
                    : null;
                $queuedAt = $today->copy()->setTime(8, 0)->addMinutes((($serviceIndex * 8) + $number) * 7);

                $queue = Queue::query()->create([
                    'service_id' => $service->id,
                    'counter_id' => $counter?->id,
                    'ticket_number' => sprintf('%s-%03d', $service->code, $number),
                    'queue_date' => $today->toDateString(),
                    'status' => $status,
                    'queued_at' => $queuedAt,
                    'called_at' => in_array($status, ['completed', 'serving', 'called'], true) ? $queuedAt->copy()->addMinutes(6) : null,
                    'started_at' => in_array($status, ['completed', 'serving'], true) ? $queuedAt->copy()->addMinutes(8) : null,
                    'completed_at' => $status === 'completed' ? $queuedAt->copy()->addMinutes(15) : null,
                    'notes' => $status === 'waiting' ? 'Menunggu giliran pada area tunggu.' : null,
                ]);

                if ($counter && in_array($status, ['completed', 'serving', 'called'], true)) {
                    Call::query()->create([
                        'queue_id' => $queue->id,
                        'counter_id' => $counter->id,
                        'status' => $status,
                        'called_at' => $queue->called_at ?? $queuedAt->copy()->addMinutes(6),
                        'started_at' => $queue->started_at,
                        'finished_at' => $queue->completed_at,
                        'notes' => $status === 'serving' ? 'Sedang diproses operator.' : null,
                    ]);
                }
            }
        }
    }
}
