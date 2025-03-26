<?php

namespace VATGER\Auth\Helpers;

use VATGER\Auth\Entity\VatgerModerationLog;

/**
 * Specifies all the move types available for the {@see VatgerModerationLog} entity.
 */
enum ModerationContentType
{
    case POST;
    case THREAD;

    public function toString(): string
    {
        return match ($this) {
            ModerationContentType::POST => 'post',
            ModerationContentType::THREAD => 'thread',
        };
    }
}