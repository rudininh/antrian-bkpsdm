<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Response;

class SystemUpdateController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-system');

        $projectPath = base_path();
        $lockData = $this->readLockData();
        $isRunning = $lockData !== null;

        $branch = $this->runCommand(['git', 'branch', '--show-current'], $projectPath)['output'] ?: 'main';

        $fetchResult = [
            'successful' => true,
            'output' => 'Remote ref menggunakan data fetch terakhir.',
        ];

        if (! $isRunning) {
            $fetchResult = $this->runCommand(['git', 'fetch', 'origin', '--quiet'], $projectPath, 120);
        }

        $localHead = $this->runCommand(['git', 'rev-parse', 'HEAD'], $projectPath)['output'];
        $remoteHead = $this->runCommand(['git', 'rev-parse', sprintf('origin/%s', $branch)], $projectPath)['output'];
        $statusShort = $this->runCommand(['git', 'status', '--short'], $projectPath)['output'];
        $statusFull = $this->runCommand(['git', 'status'], $projectPath)['output'];
        $lastCommit = $this->runCommand(['git', 'log', '-1', '--pretty=format:%h - %s (%ci)'], $projectPath)['output'];
        $remoteUrl = $this->runCommand(['git', 'remote', 'get-url', 'origin'], $projectPath)['output'];

        $logPath = storage_path('logs/update-runner.log');
        $logExists = File::exists($logPath);

        return Inertia::render('Settings/Update', [
            'systemStatus' => [
                'projectPath' => $projectPath,
                'updateBatPath' => base_path('update.bat'),
                'updateBatExists' => File::exists(base_path('update.bat')),
                'maintenanceMode' => $this->isMaintenanceMode(),
                'isRunning' => $isRunning,
                'lock' => $lockData,
                'logPath' => $logPath,
                'logExists' => $logExists,
                'logUpdatedAt' => $logExists ? Carbon::createFromTimestamp(File::lastModified($logPath))->toDateTimeString() : null,
            ],
            'gitStatus' => [
                'branch' => $branch,
                'localHead' => $localHead,
                'remoteHead' => $remoteHead,
                'hasLocalChanges' => $statusShort !== '',
                'isUpToDate' => $localHead !== '' && $localHead === $remoteHead,
                'statusShort' => $statusShort,
                'statusFull' => $statusFull,
                'lastCommit' => $lastCommit,
                'remoteUrl' => $remoteUrl,
                'fetch' => $fetchResult,
            ],
            'commandOutputs' => [
                [
                    'label' => 'git rev-parse HEAD',
                    'output' => $localHead,
                ],
                [
                    'label' => sprintf('git rev-parse origin/%s', $branch),
                    'output' => $remoteHead,
                ],
                [
                    'label' => 'git status --short',
                    'output' => $statusShort !== '' ? $statusShort : 'Working tree clean',
                ],
            ],
            'updateLog' => [
                'tail' => $this->tailFile($logPath),
            ],
            'meta' => [
                'title' => 'Pengaturan Server',
                'description' => 'Pantau status Git, maintenance mode, dan jalankan pembaruan server langsung dari panel admin.',
                'dateLabel' => now()->translatedFormat('d F Y H:i'),
            ],
        ]);
    }

    public function runUpdate(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system');

        $updatePath = base_path('update.bat');

        if (! File::exists($updatePath)) {
            return back()->with('error', 'File update.bat tidak ditemukan di folder project.');
        }

        $lockPath = $this->lockPath();

        if (File::exists($lockPath)) {
            return back()->with('error', 'Update masih berjalan. Tunggu proses sebelumnya selesai.');
        }

        $statusResult = $this->runCommand(['git', 'status', '--porcelain'], base_path());

        if (! $statusResult['successful']) {
            return back()->with('error', 'Gagal memeriksa status repository sebelum update: '.$statusResult['output']);
        }

        if ($statusResult['output'] !== '') {
            return back()->with('error', 'Update dari panel admin hanya bisa dijalankan saat working tree Git bersih. Commit, stash, atau buang perubahan lokal dulu.');
        }

        File::ensureDirectoryExists(dirname($lockPath));

        File::put($lockPath, json_encode([
            'started_at' => now()->toDateTimeString(),
            'started_by' => $request->user()?->name,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $logPath = storage_path('logs/update-runner.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::append(
            $logPath,
            PHP_EOL.'=================================================='.PHP_EOL
            .'['.now()->toDateTimeString().'] Update dipicu dari panel admin oleh '.$request->user()?->name.PHP_EOL
            .'=================================================='.PHP_EOL
        );

        $bat = escapeshellarg($updatePath);
        $log = escapeshellarg($logPath);
        $lock = escapeshellarg($lockPath);
        $command = 'cmd /c start "" /B cmd /c "'.$bat.' --non-interactive >> '.$log.' 2>&1 & if exist '.$lock.' del /q '.$lock.'"';

        $process = @popen($command, 'r');

        if ($process === false) {
            File::delete($lockPath);

            return back()->with('error', 'Gagal menjalankan update.bat dari panel admin.');
        }

        @pclose($process);

        return back()->with('success', 'Update server dimulai. Halaman akan menampilkan log terbaru secara otomatis.');
    }

    public function runArtisanAction(Request $request, string $action): RedirectResponse
    {
        Gate::authorize('manage-system');

        $commands = [
            'down' => ['artisan', 'down', '--render=errors::503', '--retry=60'],
            'up' => ['artisan', 'up'],
            'optimize-clear' => ['artisan', 'optimize:clear'],
        ];

        $labels = [
            'down' => 'php artisan down',
            'up' => 'php artisan up',
            'optimize-clear' => 'php artisan optimize:clear',
        ];

        if (! array_key_exists($action, $commands)) {
            return back()->with('error', 'Aksi artisan tidak dikenali.');
        }

        $result = $this->runCommand(
            [PHP_BINARY, ...$commands[$action]],
            base_path(),
            120
        );

        if (! $result['successful']) {
            return back()->with('error', $labels[$action].' gagal dijalankan: '.$result['output']);
        }

        $output = $result['output'] !== '' ? $result['output'] : 'Perintah selesai tanpa output tambahan.';

        return back()->with('success', $labels[$action].' berhasil dijalankan. '.$output);
    }

    protected function runCommand(array $command, string $path, int $timeout = 30): array
    {
        $result = Process::path($path)
            ->timeout($timeout)
            ->run($command);

        $output = trim($result->output());
        $errorOutput = trim($result->errorOutput());

        return [
            'successful' => $result->successful(),
            'output' => $output !== '' ? $output : $errorOutput,
        ];
    }

    protected function isMaintenanceMode(): bool
    {
        return File::exists(storage_path('framework/down'))
            || File::exists(storage_path('framework/maintenance.php'));
    }

    protected function lockPath(): string
    {
        return storage_path('app/update-running.lock');
    }

    protected function readLockData(): ?array
    {
        $lockPath = $this->lockPath();

        if (! File::exists($lockPath)) {
            return null;
        }

        $decoded = json_decode(File::get($lockPath), true);

        if (! is_array($decoded)) {
            return [
                'started_at' => Carbon::createFromTimestamp(File::lastModified($lockPath))->toDateTimeString(),
                'started_by' => 'Tidak diketahui',
            ];
        }

        return $decoded;
    }

    protected function tailFile(string $path, int $maxBytes = 24000): string
    {
        if (! File::exists($path)) {
            return 'Log update belum tersedia.';
        }

        $size = File::size($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return 'Log tidak dapat dibaca.';
        }

        $offset = max(0, $size - $maxBytes);
        fseek($handle, $offset);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return ltrim($content);
    }
}
