<?php

namespace VATGER\Auth\Cron;

use VATGER\Auth\Repository\VatgerModerationLogRepository;

class CleanUpModerationLogs
{
    public static function deleteExpiredLogs(): void
    {
        $repo = \XF::app()->repository(VatgerModerationLogRepository::class);
        $repo->deleteExpiredLogs();
    }
}