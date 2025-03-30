<?php

namespace VATGER\Auth\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Entity\User;

/**
 * COLUMNS
 * @property int $id
 * @property int $user_id
 * @property int $vatger_moderation_log_id
 * @property string $content
 *
 * RELATIONS
 * @property-read VatgerModerationLog $ModerationLog
 * @property-read User $User
 */
class VatgerPostContent extends Entity {
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_vatger_post_content';
        $structure->shortName = 'VATGER\Auth:VatgerPostContent';
        $structure->primaryKey = 'id';
        $structure->columns = [
            'id' => ['type' => self::UINT, 'primary' => true, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'vatger_moderation_log_id' => ['type' => self::UINT, 'required' => true],
            'content' => ['type' => self::STR, 'required' => true]
        ];
        $structure->getters = [];
        $structure->relations = [
            'ModerationLog' => [
                'entity' => 'VATGER\Auth:VatgerModerationLog',
                'type' => self::TO_ONE,
                'conditions' => [['id', '=', '$vatger_moderation_log_id']],
                'primary' => false
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => false
            ]
        ];

        return $structure;
    }
}