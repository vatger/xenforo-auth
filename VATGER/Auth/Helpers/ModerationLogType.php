<?php

namespace VATGER\Auth\Helpers;

use VATGER\Auth\Entity\VatgerModerationLog;

/**
 * Specifies all the move types available for the {@see VatgerModerationLog} entity.
 */
enum ModerationLogType
{
    case MOVE;
    case DELETE_HARD;
    case DELETE_SOFT;

    public function toString(): string
    {
        return match ($this) {
            ModerationLogType::MOVE => 'move',
            ModerationLogType::DELETE_SOFT => 'soft_delete',
            ModerationLogType::DELETE_HARD => 'hard_delete',
        };
    }
}