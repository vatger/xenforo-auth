<?php

namespace VATGER\Auth\Service\VatgerModerationLog;

use VATGER\Auth\Entity\VatgerModerationLog;
use VATGER\Auth\Helpers\ModerationLogType;
use XF;
use XF\App;
use XF\Entity\Forum;
use XF\Entity\Post;
use XF\Entity\Thread;
use XF\Entity\User;
use XF\Service\AbstractService;
use XF\Service\ValidateAndSavableTrait;

class CreatorService extends AbstractService {
    use ValidateAndSavableTrait;

    private VatgerModerationLog $entity;

    private User|null $user;
    private Thread|null $thread;
    private Post|null $post;
    private string|null $message;
    private ModerationLogType|null $changeType;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->entity = $this->em()->create(VatgerModerationLog::class);
        $this->setupDefaults();
    }

    protected function setupDefaults(): void
    {
        $visitor = XF::visitor();
        if ($visitor) {
            $this->user = $visitor;
        }

        $this->thread = null;
        $this->post = null;
        $this->message = null;
        $this->changeType = null;
    }

    public function setThreadMoveDetails(Thread $thread, Forum $from, Forum $to): void
    {
        $this->changeType = ModerationLogType::MOVE;
        $this->thread = $thread;
        $this->message = "Thread {$this->thread->title} moved from {$from->title} ({$from->node_id})" .
                         "to {$to->title} ({$to->node_id}) by {$this->user?->username}";
    }

    public function setThreadSoftDeleteDetails(Thread $thread): void
    {
        $this->changeType = ModerationLogType::DELETE_SOFT;
        $this->thread = $thread;
        $this->message = "Thread {$thread->title} hidden by {$this->user?->username}";
    }

    public function setThreadHardDeleteDetails(Thread $thread): void {
        $this->changeType = ModerationLogType::DELETE_HARD;
        $this->thread = $thread;
        $this->message = "Thread {$thread->title} deleted by {$this->user?->username}";
    }

    protected function _validate(): array
    {
        $errors = [];

        if (!$this->user) {
            $errors[] = "User is empty";
        }

        if (!$this->thread && ($this->changeType == ModerationLogType::DELETE_SOFT || $this->changeType == ModerationLogType::MOVE)) {
            $errors[] = "Thread is empty";
        }

        return $errors;
    }

    protected function _save(): void
    {
        /** @var VatgerModerationLog $entity */
        $entity = $this->em()->create(VatgerModerationLog::class);

        $entity->bulkSet([
            'user_id' => $this->user->user_id,
            'thread_id' => $this->thread?->thread_id,
            'post_id' => $this->post?->post_id,
            'message' => $this->message,
            'change_type' => $this->changeType->toString()
        ]);

        try {
            $entity->save();
        } catch (\Exception $e) {
            \XF::logException($e);
        }
    }
}