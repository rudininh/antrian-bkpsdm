<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemUpdatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_system_update_page(): void
    {
        Process::fake(function ($process) {
            $command = $process->command;

            return match ($command) {
                ['git', 'branch', '--show-current'] => Process::result('main'),
                ['git', 'fetch', 'origin', '--quiet'] => Process::result('Remote ref updated.'),
                ['git', 'rev-parse', 'HEAD'] => Process::result('abc123'),
                ['git', 'rev-parse', 'origin/main'] => Process::result('abc123'),
                ['git', 'status', '--short'] => Process::result(''),
                ['git', 'status'] => Process::result('On branch main'),
                ['git', 'log', '-1', '--pretty=format:%h - %s (%ci)'] => Process::result('abc123 - Test commit (2026-04-22 08:00:00 +0800)'),
                ['git', 'remote', 'get-url', 'origin'] => Process::result('https://example.com/repo.git'),
                default => Process::result(''),
            };
        });

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('system.update.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Update')
                ->where('gitStatus.branch', 'main')
                ->where('gitStatus.localHead', 'abc123')
                ->where('gitStatus.remoteHead', 'abc123')
                ->where('gitStatus.hasLocalChanges', false)
                ->where('systemStatus.updateBatExists', true)
                ->has('commandOutputs', 3)
            );
    }

    public function test_operator_can_view_system_update_page(): void
    {
        Process::fake(function ($process) {
            $command = $process->command;

            return match ($command) {
                ['git', 'branch', '--show-current'] => Process::result('main'),
                ['git', 'fetch', 'origin', '--quiet'] => Process::result('Remote ref updated.'),
                ['git', 'rev-parse', 'HEAD'] => Process::result('abc123'),
                ['git', 'rev-parse', 'origin/main'] => Process::result('abc123'),
                ['git', 'status', '--short'] => Process::result(''),
                ['git', 'status'] => Process::result('On branch main'),
                ['git', 'log', '-1', '--pretty=format:%h - %s (%ci)'] => Process::result('abc123 - Test commit (2026-04-22 08:00:00 +0800)'),
                ['git', 'remote', 'get-url', 'origin'] => Process::result('https://example.com/repo.git'),
                default => Process::result(''),
            };
        });

        $operator = User::factory()->create([
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('system.update.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Update')
                ->where('gitStatus.branch', 'main')
            );
    }

    public function test_package_lock_changes_do_not_block_update_page(): void
    {
        Process::fake(function ($process) {
            $command = $process->command;

            return match ($command) {
                ['git', 'branch', '--show-current'] => Process::result('main'),
                ['git', 'fetch', 'origin', '--quiet'] => Process::result('Remote ref updated.'),
                ['git', 'rev-parse', 'HEAD'] => Process::result('abc123'),
                ['git', 'rev-parse', 'origin/main'] => Process::result('abc123'),
                ['git', 'status', '--short'] => Process::result(" M package-lock.json"),
                ['git', 'status'] => Process::result('On branch main'),
                ['git', 'log', '-1', '--pretty=format:%h - %s (%ci)'] => Process::result('abc123 - Test commit (2026-04-22 08:00:00 +0800)'),
                ['git', 'remote', 'get-url', 'origin'] => Process::result('https://example.com/repo.git'),
                default => Process::result(''),
            };
        });

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('system.update.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('gitStatus.hasLocalChanges', false)
                ->where('gitStatus.hasIgnoredLocalChanges', true)
                ->where('gitStatus.statusShort', '')
                ->where('gitStatus.ignoredStatusShort', " M package-lock.json")
            );
    }

    public function test_update_page_still_opens_while_application_is_in_maintenance_mode(): void
    {
        Process::fake(function ($process) {
            $command = $process->command;

            return match ($command) {
                ['git', 'branch', '--show-current'] => Process::result('main'),
                ['git', 'fetch', 'origin', '--quiet'] => Process::result('Remote ref updated.'),
                ['git', 'rev-parse', 'HEAD'] => Process::result('abc123'),
                ['git', 'rev-parse', 'origin/main'] => Process::result('abc123'),
                ['git', 'status', '--short'] => Process::result(''),
                ['git', 'status'] => Process::result('On branch main'),
                ['git', 'log', '-1', '--pretty=format:%h - %s (%ci)'] => Process::result('abc123 - Test commit (2026-04-22 08:00:00 +0800)'),
                ['git', 'remote', 'get-url', 'origin'] => Process::result('https://example.com/repo.git'),
                default => Process::result(''),
            };
        });

        $maintenancePath = storage_path('framework/down');
        File::ensureDirectoryExists(dirname($maintenancePath));
        File::put($maintenancePath, json_encode([
            'status' => 503,
            'retry' => 60,
        ], JSON_PRETTY_PRINT));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        try {
            $this->actingAs($admin)
                ->get(route('system.update.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Settings/Update')
                );
        } finally {
            File::delete($maintenancePath);
        }
    }

    public function test_run_update_is_blocked_when_repository_has_local_changes(): void
    {
        Process::fake(function ($process) {
            return match ($process->command) {
                ['git', 'status', '--porcelain'] => Process::result(" M resources/js/Pages/Settings/Update.vue"),
                default => Process::result(''),
            };
        });

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('system.update.index'))
            ->post(route('system.update.run'))
            ->assertRedirect(route('system.update.index'))
            ->assertSessionHas('error', 'Update dari panel admin hanya bisa dijalankan saat working tree Git bersih. Commit, stash, atau buang perubahan lokal dulu.');
    }

    public function test_admin_can_restore_tracked_changes_from_update_page(): void
    {
        Process::fake(function ($process) {
            return match ($process->command) {
                ['git', 'reset', '--hard', 'HEAD'] => Process::result('HEAD is now at abc123'),
                ['git', 'status', '--short'] => Process::result(''),
                default => Process::result(''),
            };
        });

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('system.update.index'))
            ->post(route('system.update.cleanup', 'restore-tracked'))
            ->assertRedirect(route('system.update.index'))
            ->assertSessionHas('success', 'Repository berhasil dibersihkan. Working tree sekarang sudah clean dan update bisa dijalankan.');
    }

    public function test_admin_can_remove_untracked_files_from_update_page(): void
    {
        Process::fake(function ($process) {
            return match ($process->command) {
                ['git', 'clean', '-fd'] => Process::result('Removing temp.txt'),
                ['git', 'status', '--short'] => Process::result(''),
                default => Process::result(''),
            };
        });

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('system.update.index'))
            ->post(route('system.update.cleanup', 'clean-untracked'))
            ->assertRedirect(route('system.update.index'))
            ->assertSessionHas('success', 'Repository berhasil dibersihkan. Working tree sekarang sudah clean dan update bisa dijalankan.');
    }
}
