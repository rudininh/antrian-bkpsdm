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
    protected ?array $staleLockNotice = null;

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
        $remoteHead = $this->resolveRemoteHead($branch, $projectPath);
        $statusShort = $this->runCommand(['git', 'status', '--short'], $projectPath)['output'];
        $blockingStatusShort = $this->filterBlockingGitStatus($statusShort);
        $ignoredStatusShort = $this->filterIgnoredGitStatus($statusShort);
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
                'staleLockNotice' => $this->staleLockNotice,
                'logPath' => $logPath,
                'logExists' => $logExists,
                'logUpdatedAt' => $logExists ? Carbon::createFromTimestamp(File::lastModified($logPath))->toDateTimeString() : null,
            ],
            'gitStatus' => [
                'branch' => $branch,
                'localHead' => $localHead,
                'remoteHead' => $remoteHead,
                'hasLocalChanges' => $blockingStatusShort !== '',
                'hasIgnoredLocalChanges' => $ignoredStatusShort !== '',
                'isUpToDate' => $localHead !== '' && $localHead === $remoteHead,
                'statusShort' => $blockingStatusShort,
                'ignoredStatusShort' => $ignoredStatusShort,
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
                    'label' => sprintf('git ls-remote origin refs/heads/%s', $branch),
                    'output' => $remoteHead,
                ],
                [
                    'label' => 'git status --short',
                    'output' => $blockingStatusShort !== '' ? $blockingStatusShort : 'Working tree clean',
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

        if ($this->readLockData() !== null) {
            return back()->with('error', 'Update masih berjalan. Tunggu proses sebelumnya selesai.');
        }

        $statusResult = $this->runCommand(['git', 'status', '--porcelain'], base_path());
        $blockingStatus = $this->filterBlockingGitStatus($statusResult['output']);

        if (! $statusResult['successful']) {
            return back()->with('error', 'Gagal memeriksa status repository sebelum update: '.$statusResult['output']);
        }

        if ($blockingStatus !== '') {
            return back()->with('error', 'Update ditolak karena working tree Git belum bersih. Perubahan terdeteksi: '.$this->formatBlockingStatusPreview($blockingStatus).' Bersihkan dari panel ini atau commit/stash dulu sebelum update.');
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

        $runnerPath = $this->runnerScriptPath();

        File::put($runnerPath, $this->buildRunnerScript($updatePath, $logPath, $lockPath));

        $escapedRunnerPath = str_replace("'", "''", $runnerPath);
        $launchCommand = "Start-Process -FilePath 'cmd.exe' -ArgumentList '/c','call `\"`\"{$escapedRunnerPath}`\"`\"' -WindowStyle Hidden";
        $launchResult = Process::path(base_path())
            ->timeout(15)
            ->run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-Command',
                $launchCommand,
            ]);

        if (! $launchResult->successful()) {
            File::delete($lockPath);
            File::delete($runnerPath);

            return back()->with('error', 'Gagal menjalankan update.bat dari panel admin: '.trim($launchResult->errorOutput()));
        }

        return back()->with('success', 'Update server dimulai. Halaman akan menampilkan log terbaru secara otomatis.');
    }

    public function cleanWorkingTree(Request $request, string $mode): RedirectResponse
    {
        Gate::authorize('manage-system');

        if ($this->readLockData() !== null) {
            return back()->with('error', 'Pembersihan repository tidak bisa dijalankan saat update masih berjalan.');
        }

        $actions = [
            'restore-tracked' => [
                'title' => 'balikkan perubahan tracked',
                'commands' => [
                    ['git', 'reset', '--hard', 'HEAD'],
                ],
            ],
            'clean-untracked' => [
                'title' => 'hapus file untracked',
                'commands' => [
                    ['git', 'clean', '-fd'],
                ],
            ],
            'all' => [
                'title' => 'bersihkan semua perubahan lokal',
                'commands' => [
                    ['git', 'reset', '--hard', 'HEAD'],
                    ['git', 'clean', '-fd'],
                ],
            ],
        ];

        if (! array_key_exists($mode, $actions)) {
            return back()->with('error', 'Aksi pembersihan repository tidak dikenali.');
        }

        foreach ($actions[$mode]['commands'] as $command) {
            $result = $this->runCommand($command, base_path(), 120);

            if (! $result['successful']) {
                return back()->with('error', 'Gagal '.$actions[$mode]['title'].': '.$result['output']);
            }
        }

        $statusResult = $this->runCommand(['git', 'status', '--short'], base_path());

        if (! $statusResult['successful']) {
            return back()->with('success', 'Perintah pembersihan repository selesai. Status repo tidak dapat diverifikasi ulang.');
        }

        if ($statusResult['output'] !== '') {
            return back()->with('warning', 'Perintah pembersihan selesai, tetapi masih ada perubahan lokal tersisa: '.$statusResult['output']);
        }

        return back()->with('success', 'Repository berhasil dibersihkan. Working tree sekarang sudah clean dan update bisa dijalankan.');
    }

    public function runArtisanAction(Request $request, string $action): RedirectResponse
    {
        Gate::authorize('manage-system');

        $this->readLockData();

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

    protected function runnerScriptPath(): string
    {
        return storage_path('app/update-runner.cmd');
    }

    protected function readLockData(): ?array
    {
        $lockPath = $this->lockPath();

        if (! File::exists($lockPath)) {
            return null;
        }

        if ($this->shouldReleaseStaleLock()) {
            $this->releaseStaleLock($lockPath);

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

    protected function shouldReleaseStaleLock(): bool
    {
        $isRunning = $this->isUpdateProcessRunning();

        return $isRunning === false;
    }

    protected function isUpdateProcessRunning(): ?bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $projectPath = str_replace("'", "''", base_path());
        $script = <<<'POWERSHELL'
$project = '%s'
$process = Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -and $_.CommandLine -like "*$project*" -and (
        $_.CommandLine -like '*update.bat*' -or $_.CommandLine -like '*update-runner.cmd*'
    )
} | Select-Object -First 1

if ($null -ne $process) {
    $process.ProcessId
}
POWERSHELL;

        $result = Process::timeout(10)->run([
            'powershell',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-Command',
            sprintf($script, $projectPath),
        ]);

        if (! $result->successful()) {
            return null;
        }

        return trim($result->output()) !== '';
    }

    protected function releaseStaleLock(string $lockPath): void
    {
        $previousLockData = $this->parseLockData($lockPath);

        File::delete($lockPath);
        File::delete($this->runnerScriptPath());

        $this->staleLockNotice = [
            'released_at' => now()->toDateTimeString(),
            'started_at' => $previousLockData['started_at'] ?? null,
            'started_by' => $previousLockData['started_by'] ?? 'Tidak diketahui',
        ];

        $logPath = storage_path('logs/update-runner.log');

        if (! File::exists($logPath)) {
            return;
        }

        File::append(
            $logPath,
            PHP_EOL.'['.now()->toDateTimeString().'] Lock update dibersihkan otomatis karena proses update sudah tidak terdeteksi.'.PHP_EOL
        );
    }

    protected function parseLockData(string $lockPath): array
    {
        if (! File::exists($lockPath)) {
            return [];
        }

        $decoded = json_decode(File::get($lockPath), true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'started_at' => Carbon::createFromTimestamp(File::lastModified($lockPath))->toDateTimeString(),
            'started_by' => 'Tidak diketahui',
        ];
    }

    protected function formatBlockingStatusPreview(string $blockingStatus, int $maxLines = 3): string
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($blockingStatus)) ?: [])
            ->filter(fn (string $line) => $line !== '')
            ->take($maxLines)
            ->values();

        $preview = $lines->implode(' | ');
        $remaining = max(0, count(preg_split('/\r\n|\r|\n/', trim($blockingStatus)) ?: []) - $lines->count());

        if ($remaining > 0) {
            $preview .= " | +{$remaining} lainnya";
        }

        return $preview !== '' ? $preview : 'perubahan lokal tidak dapat diringkas';
    }

    protected function buildRunnerScript(string $updatePath, string $logPath, string $lockPath): string
    {
        $projectPath = base_path();

        return implode("\r\n", [
            '@echo off',
            'setlocal EnableExtensions EnableDelayedExpansion',
            'cd /d "'.$projectPath.'"',
            'call "'.$updatePath.'" --non-interactive >> "'.$logPath.'" 2>&1',
            'set "EXIT_CODE=!ERRORLEVEL!"',
            'if exist "'.$lockPath.'" del /q "'.$lockPath.'"',
            '>> "'.$logPath.'" echo.',
            '>> "'.$logPath.'" echo [INFO] Runner panel selesai dengan exit code !EXIT_CODE!.',
            'del /q "%~f0" >nul 2>nul',
            'exit /b !EXIT_CODE!',
            '',
        ]);
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

    protected function resolveRemoteHead(string $branch, string $projectPath): string
    {
        $remoteRef = sprintf('refs/heads/%s', $branch);
        $lsRemote = $this->runCommand(['git', 'ls-remote', 'origin', $remoteRef], $projectPath, 30);

        if (! $lsRemote['successful'] || $lsRemote['output'] === '') {
            return $this->runCommand(['git', 'rev-parse', sprintf('origin/%s', $branch)], $projectPath)['output'];
        }

        $firstLine = trim(explode(PHP_EOL, $lsRemote['output'])[0] ?? '');

        if ($firstLine === '') {
            return $this->runCommand(['git', 'rev-parse', sprintf('origin/%s', $branch)], $projectPath)['output'];
        }

        return preg_split('/\s+/', $firstLine)[0] ?? '';
    }

    protected function filterBlockingGitStatus(string $statusOutput): string
    {
        return collect(preg_split('/\r\n|\r|\n/', trim($statusOutput)) ?: [])
            ->filter(fn (string $line) => $line !== '' && ! $this->isIgnoredStatusLine($line))
            ->implode(PHP_EOL);
    }

    protected function filterIgnoredGitStatus(string $statusOutput): string
    {
        return collect(preg_split('/\r\n|\r|\n/', trim($statusOutput)) ?: [])
            ->filter(fn (string $line) => $line !== '' && $this->isIgnoredStatusLine($line))
            ->implode(PHP_EOL);
    }

    protected function isIgnoredStatusLine(string $line): bool
    {
        return str_contains($line, 'package-lock.json');
    }
}
