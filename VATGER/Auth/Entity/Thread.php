<?php

namespace VATGER\Auth\Entity;

use VATGER\Auth\Service\VatgerModerationLog\CreatorService as ModeratorLogCreatorService;
use XF\Entity\Forum;

class Thread extends XFCP_Thread {
    protected function _preDelete(): void
    {
        /** @var ModeratorLogCreatorService $creatorService */
        $creatorService = $this->app()->service(ModeratorLogCreatorService::class);

        $creatorService->setThreadHardDeleteDetails($this);
        $creatorService->save();

        parent::_preDelete();
    }

    protected function _postDelete(): void
    {
        $db = $this->db();

        $db->update('xf_vatger_moderation_logs', [
            'thread_id' => null
        ], "thread_id = {$this->thread_id}");

        parent::_postDelete();
    }

    protected function threadMoved(Forum $from, Forum $to): void
    {
        /** @var ModeratorLogCreatorService $creatorService */
        $creatorService = $this->app()->service(ModeratorLogCreatorService::class);

        $creatorService->setThreadMoveDetails($this, $from, $to);
        $creatorService->save();

        parent::threadMoved($from, $to);
    }

    protected function threadHidden($hardDelete = false): void
    {
        /** @var ModeratorLogCreatorService $creatorService */
        $creatorService = $this->app()->service(ModeratorLogCreatorService::class);

        $creatorService->setThreadSoftDeleteDetails($this);
        $creatorService->save();

        parent::threadHidden($hardDelete);
    }
}