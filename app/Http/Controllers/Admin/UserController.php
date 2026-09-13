<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordModerationAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Http\Requests\Admin\UnsuspendUserRequest;
use App\Http\Requests\Admin\WarnUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['products', 'reports'])
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim()->toString().'%';
                $query->where(function (Builder $sub) use ($keyword): void {
                    $sub->where('name', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword)
                        ->orWhere('whatsapp_number', 'like', $keyword);
                });
            })
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = $request->string('status')->toString();
                if ($status === 'suspended') {
                    $query->whereNotNull('suspended_until')->where('suspended_until', '>', now());
                } elseif ($status === 'warned') {
                    $query->where('warning_count', '>', 0);
                } elseif ($status === 'active') {
                    $query->where(function (Builder $q): void {
                        $q->whereNull('suspended_until')->orWhere('suspended_until', '<=', now());
                    });
                }
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load([
            'products' => fn ($q) => $q->withTrashed()->latest()->limit(10),
            'payments' => fn ($q) => $q->latest()->limit(10),
            'reports' => fn ($q) => $q->latest()->limit(10),
            'moderationSubjectAudits' => fn ($q) => $q->with('actor:id,name')->latest(),
        ])->loadCount(['products', 'payments', 'reports']);

        return view('admin.users.show', compact('user'));
    }

    public function warn(WarnUserRequest $request, User $user, RecordModerationAudit $recordAudit): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Admin tidak dapat diberi peringatan.']);
        }

        DB::transaction(function () use ($request, $user, $recordAudit): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = ['warning_count' => $lockedUser->warning_count];

            $lockedUser->increment('warning_count');
            $after = ['warning_count' => $lockedUser->warning_count];

            $recordAudit->handle(
                $request->user(),
                'user.warned',
                $lockedUser,
                before: $before,
                after: $after,
                reason: $request->string('reason')->toString(),
            );
        });

        return redirect()->route('admin.users.show', $user)->with('status', 'Peringatan berhasil dicatat untuk user ini.');
    }

    public function suspend(SuspendUserRequest $request, User $user, RecordModerationAudit $recordAudit): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Admin tidak dapat ditangguhkan.']);
        }

        $type = $request->string('type')->toString();
        $until = $type === 'permanent'
            ? now()->addYears(80)
            : now()->addDays($request->integer('days'));

        DB::transaction(function () use ($request, $user, $until, $recordAudit): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = [
                'suspended_until' => $lockedUser->suspended_until?->toIso8601String(),
                'suspension_reason' => $lockedUser->suspension_reason,
            ];

            $lockedUser->update([
                'suspended_until' => $until,
                'suspension_reason' => $request->string('reason')->toString(),
            ]);

            $after = [
                'suspended_until' => $until->toIso8601String(),
                'suspension_reason' => $request->string('reason')->toString(),
            ];

            $recordAudit->handle(
                $request->user(),
                'user.suspended',
                $lockedUser,
                before: $before,
                after: $after,
                reason: $request->string('reason')->toString(),
            );
        });

        return redirect()->route('admin.users.show', $user)->with('status', 'Akun user berhasil ditangguhkan.');
    }

    public function unsuspend(UnsuspendUserRequest $request, User $user, RecordModerationAudit $recordAudit): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $recordAudit): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = [
                'suspended_until' => $lockedUser->suspended_until?->toIso8601String(),
                'suspension_reason' => $lockedUser->suspension_reason,
            ];

            $lockedUser->update([
                'suspended_until' => null,
                'suspension_reason' => null,
            ]);

            $recordAudit->handle(
                $request->user(),
                'user.unsuspended',
                $lockedUser,
                before: $before,
                after: ['suspended_until' => null, 'suspension_reason' => null],
                reason: $request->string('reason')->toString(),
            );
        });

        return redirect()->route('admin.users.show', $user)->with('status', 'Penangguhan akun user berhasil dicabut.');
    }
}
