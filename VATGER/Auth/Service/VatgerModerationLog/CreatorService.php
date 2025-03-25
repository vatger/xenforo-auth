<?php

namespace VATGER\Auth\Service\VatgerModerationLog;

use VATGER\Auth\Entity\VatgerModerationLog;
use VATGER\Auth\Entity\VatgerPostContent;
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
    private string|null $reason;
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
        $this->reason = null;
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

    public function setPostHardDeleteDetails(Post $post): void {
        $this->changeType = ModerationLogType::DELETE_HARD;
        $this->post = $post;
        $this->message = "Post {$post->post_id} (in thread {$post->Thread->title}) deleted by {$this->user?->username}";
    }

    public function setThreadHardDeleteDetails(Thread $thread): void {
        $this->changeType = ModerationLogType::DELETE_HARD;
        $this->thread = $thread;
        $this->message = "Thread {$thread->title} deleted by {$this->user?->username}";
    }

    public function setPostSoftDeleteDetails(Post $post): void {
        $this->changeType = ModerationLogType::DELETE_SOFT;
        $this->post = $post;
        $this->reason = $post->DeletionLog->delete_reason;
        $this->message = "Post {$post->post_id} (in thread {$post->Thread->title}) hidden by {$this->user?->username}";
    }

    public function setThreadSoftDeleteDetails(Thread $thread): void
    {
        $this->changeType = ModerationLogType::DELETE_SOFT;
        $this->thread = $thread;
        $this->reason = $thread->DeletionLog->delete_reason;
        $this->message = "Thread {$thread->title} hidden by {$this->user?->username}";
    }

    public function setThreadMadeVisibleDetails(Thread $thread): void {
        $this->changeType = ModerationLogType::UNDELETED;
        $this->thread = $thread;
        $this->message = "Thread {$thread->title} undeleted (made visible) by {$this->user?->username}";
    }

    protected function _validate(): array
    {
        $errors = [];

        if (!$this->user) {
            $errors[] = "User is empty";
        }

        return $errors;
    }

    private function getThreadId(): int|null {
        if ($this->changeType === ModerationLogType::DELETE_HARD) {
            return null;
        }

        return $this->thread?->thread_id;
    }

    private function getPostId(): int|null {
        if ($this->changeType === ModerationLogType::DELETE_HARD) {
            return null;
        }

        return $this->post?->post_id;
    }

    private function createPostContent(Post $post, int $id): void {
        /** @var VatgerPostContent $postContentEntity */
        $postContentEntity = $this->em()->create(VatgerPostContent::class);

        $postContentEntity->vatger_moderation_log_id = $id;
        $postContentEntity->user_id = $post->User->user_id;
        $postContentEntity->content = $post->message;

        try {
            $postContentEntity->save();
        } catch (\Exception $e) {
            \XF::logException($e);
        }
    }

    protected function _save(): void
    {
        /** @var VatgerModerationLog $entity */
        $moderationLogEntity = $this->em()->create(VatgerModerationLog::class);

        $moderationLogEntity->user_id = $this->user->user_id;
        $moderationLogEntity->thread_id = $this->getThreadId();
        $moderationLogEntity->post_id = $this->getPostId();
        $moderationLogEntity->reason = $this->reason;
        $moderationLogEntity->message = $this->message;
        $moderationLogEntity->change_type = $this->changeType->toString();

        try {
            $moderationLogEntity->save();
        } catch (\Exception $e) {
            \XF::logException($e);
        }

        // We only log the deleted messages' content, if they were actually permanently deleted.
        // Anything else can be recovered.
        if ($this->changeType !== ModerationLogType::DELETE_HARD) {
            return;
        }

        if ($this->post !== null) {
            $this->createPostContent($this->post, $moderationLogEntity->id);
        }

        if ($this->thread !== null) {
            $posts = $this->finder(Post::class)->where('thread_id', $this->thread->thread_id)->fetch();

            /** @var Post $thread */
            foreach ($posts as $post) {
                // Create an entry for every single post of the thread
                $this->createPostContent($post, $moderationLogEntity->id);
            }
        }
    }
}