<?php

namespace VATGER\Auth\Repository;

use VATGER\Auth\Finder\VatgerModerationLogFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class VatgerModerationLogRepository extends Repository {
    public function findLogsForList(): Finder
    {
        return $this->finder(VatgerModerationLogFinder::class)
            ->with('User')
            ->setDefaultOrder('date', 'DESC');
    }

    public function getUsersInLog(): array
    {
        return $this->db()->fetchPairs(query: "
            SELECT user.user_id, user.username
            FROM (
                SELECT DISTINCT user_id FROM xf_vatger_moderation_logs
            ) AS log
            INNER JOIN xf_user AS user ON (log.user_id = user.user_id)
            ORDER BY user.username
        ");
    }
}