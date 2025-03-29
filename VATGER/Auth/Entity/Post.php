<?php

namespace VATGER\Auth\Entity;

use VATGER\Auth\Service\VatgerModerationLog\CreatorService as ModeratorLogCreatorService;

class Post extends XFCP_Post {
    protected function _preDelete(): void
    {
        /** @var ModeratorLogCreatorService $creator */
        $creator = $this->app()->service(ModeratorLogCreatorService::class);

        $creator->setPostHardDeleteDetails($this);
        $creator->save();

        parent::_preDelete();
    }

    protected function _postDelete(): void
    {
        $db = $this->db();

        $db->update('xf_vatger_moderation_logs', [
            'post_id' => null
        ], "post_id = {$this->post_id}");

        parent::_postDelete();
    }

    protected function postHidden($hardDelete = false): void
    {
        if (!$hardDelete) {
            /** @var ModeratorLogCreatorService $creator */
            $creator = $this->app()->service(ModeratorLogCreatorService::class);

            $creator->setPostSoftDeleteDetails($this);
            $creator->save();
        }

        parent::postHidden($hardDelete);
    }

    protected function postMadeVisible(): void
    {
        /** @var ModeratorLogCreatorService $ModeratorLogCreatorService */
        $ModeratorLogCreatorService = $this->app()->service(ModeratorLogCreatorService::class);

        $ModeratorLogCreatorService->setPostMadeVisibleDetails($this);
        $ModeratorLogCreatorService->save();

        parent::postMadeVisible(); 
    }
}