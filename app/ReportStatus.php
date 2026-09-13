<?php

namespace App;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Reviewed => 'Ditinjau',
            self::Resolved => 'Diselesaikan',
            self::Rejected => 'Ditolak',
        };
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        return match ($this) {
            self::Pending => in_array($nextStatus, [self::Reviewed, self::Rejected], true),
            self::Reviewed => in_array($nextStatus, [self::Resolved, self::Rejected], true),
            self::Resolved, self::Rejected => false,
        };
    }
}
