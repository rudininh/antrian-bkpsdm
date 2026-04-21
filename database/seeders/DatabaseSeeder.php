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
            ['name' => 'Administrasi Kepegawaian', 'code' => 'A', 'description' => 'Kenaikan pangkat, KGB, SK CPNS/PNS/PPPK, perubahan data, dan legalisir dokumen.'],
            ['name' => 'Mutasi dan Penempatan', 'code' => 'B', 'description' => 'Mutasi antar instansi, rotasi jabatan, penempatan awal, dan perpindahan unit kerja.'],
            ['name' => 'Pengembangan Kompetensi', 'code' => 'C', 'description' => 'Diklat, pelatihan, izin belajar, tugas belajar, dan sertifikasi.'],
            ['name' => 'Disiplin dan Status ASN', 'code' => 'D', 'description' => 'Klarifikasi pelanggaran, proses hukuman disiplin, dan pembinaan ASN.'],
            ['name' => 'Kesejahteraan dan Hak', 'code' => 'E', 'description' => 'Layanan Taspen, pensiun, cuti, dan tunjangan pegawai.'],
            ['name' => 'Layanan Data dan Informasi', 'code' => 'F', 'description' => 'Permintaan data ASN, verifikasi data, dan konsultasi kepegawaian.'],
            ['name' => 'Layanan Umum', 'code' => 'G', 'description' => 'Konsultasi umum dan helpdesk aplikasi sebagai layanan buffer.'],
        ])->map(fn (array $service) => Service::query()->updateOrCreate(
            ['code' => $service['code']],
            $service + ['is_active' => true],
        ));

        $receptionCounter = Counter::query()->updateOrCreate(
            ['code' => 'RCP'],
            [
                'name' => 'Receptionist',
                'location' => 'Meja Receptionist',
                'is_active' => true,
            ],
        );

        Call::query()->delete();
        Queue::query()->delete();

        $today = Carbon::today();
        $statuses = ['completed', 'completed', 'completed', 'serving', 'called', 'waiting', 'waiting', 'completed'];

        foreach ($services as $serviceIndex => $service) {
            foreach (range(1, 8) as $number) {
                $status = $statuses[($serviceIndex + $number - 1) % count($statuses)];
                $counter = in_array($status, ['completed', 'serving', 'called'], true)
                    ? $receptionCounter
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
