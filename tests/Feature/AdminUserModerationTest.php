<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_user_management(): void
    {
        $user = User::factory()->create();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs(User::factory()->seller()->create())->get(route('admin.users.show', $user))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.show', $user))->assertOk();
    }

    public function test_admin_can_issue_warning_to_user_with_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['warning_count' => 0]);

        $this->actingAs($admin)->post(route('admin.users.warn', $user), [
            'reason' => 'Peringatan pertama karena chat tidak sopan.',
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertSame(1, $user->refresh()->warning_count);
        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'user.warned',
            'subject_id' => $user->id,
            'reason' => 'Peringatan pertama karena chat tidak sopan.',
        ]);
    }

    public function test_admin_cannot_warn_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $targetAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.warn', $targetAdmin), [
            'reason' => 'Coba beri peringatan ke admin.',
        ])->assertSessionHasErrors('user');

        $this->assertSame(0, $targetAdmin->refresh()->warning_count);
    }

    public function test_admin_can_temporarily_suspend_user_and_lift_suspension(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->seller()->create();

        // 1. Suspend temporarily for 14 days
        $this->actingAs($admin)->post(route('admin.users.suspend', $user), [
            'type' => 'temporary',
            'days' => 14,
            'reason' => 'Terindikasi melakukan penipuan transaksi.',
        ])->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertTrue($user->isSuspended());
        $this->assertFalse($user->isPermanentlySuspended());
        $this->assertSame('Terindikasi melakukan penipuan transaksi.', $user->suspension_reason);

        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'user.suspended',
            'subject_id' => $user->id,
        ]);

        // 2. Lift suspension (unsuspend)
        $this->actingAs($admin)->post(route('admin.users.unsuspend', $user), [
            'reason' => 'Masa hukuman selesai dan investigasi klir.',
        ])->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertFalse($user->isSuspended());
        $this->assertNull($user->suspension_reason);

        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'user.unsuspended',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_permanently_suspend_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->seller()->create();

        $this->actingAs($admin)->post(route('admin.users.suspend', $user), [
            'type' => 'permanent',
            'reason' => 'Akun terbukti melakukan penipuan berulang.',
        ])->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertTrue($user->isSuspended());
        $this->assertTrue($user->isPermanentlySuspended());
    }
}
