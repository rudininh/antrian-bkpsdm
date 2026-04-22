<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
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

        collect([
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
    }
}
