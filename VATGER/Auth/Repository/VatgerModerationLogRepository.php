<?php

namespace VATGER\Auth\Repository;

use VATGER\Auth\Finder\VatgerModerationLogFinder;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;

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

    /**
     * @throws PrintableException
     */
    public function deleteExpiredLogs(): void
    {
        $deleteAfterDays = $this->options()->vatger_logging_prune_after_days ?? 180;
        $cutOff = \XF::$time - 86400 * $deleteAfterDays;

        $logEntities = $this->finder(VatgerModerationLogFinder::class)
            ->where('date', '<', $cutOff)
            ->fetch();

        foreach ($logEntities as $entity) {
            $entity->delete();
        }
    }
}