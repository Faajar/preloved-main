<?php

namespace App\Actions;

use App\Models\ModerationAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordModerationAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function handle(
        User $actor,
        string $action,
        ?Model $subject,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
    ): ModerationAudit {
        return ModerationAudit::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
        ]);
    }
}
