<?php

namespace VATGER\Auth\Entity;

use XF\Entity\Moderator;
use XF\Entity\User as UserBase;
use XF\Finder\ModeratorContentFinder;
use XF\PrintableException;

class User extends UserBase {
    /**
     * Returns true if the user is a "normal" moderator (forum moderator)
     *
     * @return bool
     */
    public function isModerator(): bool {
        return $this->is_moderator;
    }

    /**
     * Returns true if the user is a super moderator or false else
     *
     * @return bool
     */
    public function isSuperModerator(): bool {
        if ($this->Moderator == null) {
            return false;
        }

        return $this->Moderator->is_super_moderator;
    }

    /**
     * Makes this user a super administrator with default parameters
     *
     * @throws PrintableException
     */
    public function makeSuperModerator(): void
    {
        if (self::isSuperModerator()) {
            // User is already super-moderator... What should we do? :)
            return;
        }

        if (self::isModerator()) {
            // We need to "upgrade" from moderator to super moderator
            $moderator = $this->Moderator;
            $moderator->is_super_moderator = true;
            $moderator->save();

            /** @var ModeratorContentFinder $contentModeratorFinder */
            $contentModeratorFinder = $this->finder(ModeratorContentFinder::class);
            $contentModeratorFinder->where('user_id', $this->user_id)->fetch();

            foreach ($contentModeratorFinder as $contentModerator) {
                $contentModerator->delete();
            }
            return;
        }

        $moderator = $this->em()->create(Moderator::class);
        $moderator->user_id = $this->user_id;
        $moderator->is_super_moderator = true;
        $moderator->extra_user_group_ids = [];
        $moderator->notify_approval = false;
        $moderator->notify_report = false;
        $moderator->save();
    }
}