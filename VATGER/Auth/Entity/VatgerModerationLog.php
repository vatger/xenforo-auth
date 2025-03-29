<?php

namespace VATGER\Auth\Entity;

use XF\Api\Result\EntityResult;
use XF\Entity\Post;
use XF\Entity\Thread;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\PrintableException;

/**
 * COLUMNS
 * @property int $id
 * @property int $user_id
 * @property string $ip_address
 * @property int|null $thread_id
 * @property int|null $post_id
 * @property string|null $reason
 * @property string|null $message
 * @property string $change_type
 * @property string $content_type
 * @property int $date
 *
 * RELATIONS
 * @property-read User|null $User
 * @property-read Thread|null $Thread
 * @property-read Post|null $Post
 * @property-read VatgerPostContent[] $PostContents
 */
class VatgerModerationLog extends Entity {
    /**
     * @throws PrintableException
     */
    protected function _preDelete(): void
    {
        // Before deleting this moderation log, we need to delete ALL postContent entities first
        foreach ($this->PostContents as $entity) {
            $entity->delete();
        }

        parent::_preDelete();
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_vatger_moderation_logs';
        $structure->shortName = 'VATGER\Auth:VatgerModerationLog';
        $structure->primaryKey = 'id';
        $structure->columns = [
            'id' => ['type' => self::UINT, 'primary' => true, 'autoIncrement' => true],
            'user_id' => ['type' => self::INT, 'required' => true],
            'ip_address' => ['type' => self::STR, 'required' => true],
            'thread_id' => ['type' => self::INT, 'required' => false, 'nullable' => true],
            'post_id' => ['type' => self::INT, 'required' => false, 'nullable' => true],
            'reason' => ['type' => self::STR, 'required' => false, 'nullable' => true],
            'message' => ['type' => self::STR, 'required' => false, 'nullable' => true],
            'change_type' => ['type' => self::STR, 'required' => true],
            'content_type' => ['type' => self::STR, 'required' => true],
            'date' => ['type' => self::UINT, 'default' => time(), 'required' => true]
        ];
        $structure->getters = [];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => false
            ],
            'Thread' => [
                'entity' => 'XF:Thread',
                'type' => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary' => false
            ],
            'Post' => [
                'entity' => 'XF:Post',
                'type' => self::TO_ONE,
                'conditions' => 'post_id',
                'primary' => false
            ],
            'PostContents' => [
                'entity' => 'VATGER\Auth:VatgerPostContent',
                'type' => self::TO_MANY,
                'conditions' => [['vatger_moderation_log_id', '=', '$id']],
                'primary' => true
            ]
        ];

        return $structure;
    }

    protected function setupApiResultData(EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = [])
    {
        $result->includeColumn('id');
        $result->includeColumn('user_id');
        $result->includeColumn('ip_address');
        $result->includeColumn('thread_id');
        $result->includeColumn('post_id');
        $result->includeColumn('reason');
        $result->includeColumn('message');
        $result->includeColumn('change_type');
        $result->includeColumn('content_type');
        $result->includeColumn('date');

        $result->postContent = $this->PostContents;
    }
}