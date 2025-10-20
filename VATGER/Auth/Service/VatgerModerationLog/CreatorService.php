<?php

namespace VATGER\Auth\Service\VatgerModerationLog;

use VATGER\Auth\Entity\VatgerModerationLog;
use VATGER\Auth\Entity\VatgerPostContent;
use VATGER\Auth\Helpers\ModerationContentType;
use VATGER\Auth\Helpers\ModerationLogType;
use XF;
use XF\App;
use XF\Entity\Forum;
use XF\Entity\Post;
use XF\Entity\Thread;
use XF\Entity\User;
use XF\Finder\ForumFinder;
use XF\Repository\ThreadRepository;
use XF\Service\AbstractService;
use XF\Service\ValidateAndSavableTrait;
use XF\Util\Ip;

class CreatorService extends AbstractService {
    use ValidateAndSavableTrait;

    private VatgerModerationLog $entity;

    private User|null $user;
    private Thread|null $thread;
    private Post|null $post;
    private string|null $reason;
    private string|null $message;
    private ModerationLogType|null $changeType;
    private ModerationContentType|null $contentType;

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
        $this->contentType = null;
    }

    public function reset(): void {
        $this->setupDefaults();
    }

    public function setPostMoveDetails(Post $post, array $options): void
    {
        $this->changeType = ModerationLogType::MOVE;
        $this->contentType = ModerationContentType::POST;
        $this->post = $post;
        $this->message = "#{$post->post_id}: ";

        $postThread = $post->Thread;

        if ($options['thread_type'] == 'new') {
            /** @var Forum|null $forum */
            $forum = $this->finder(ForumFinder::class)->where('node_id', $options['node_id'])->fetchOne();
            $this->message .= "{$postThread->title} ({$postThread->node_id}) --> Forum: {$forum?->title} ({$forum?->node_id})";
        } else if($options['thread_type'] == 'existing') {
            /** @var ThreadRepository $threadRepository */
            $threadRepository = $this->repository(ThreadRepository::class);
            $thread = $threadRepository->getThreadFromUrl($options['existing_url']);

            $this->thread = $thread;
            $this->message .= "{$postThread->title} ({$postThread->node_id}) --> Thread: {$thread?->title} ({$thread?->node_id})";
        } else {
            $this->message = "unknown move";
        }
    }

    public function setThreadMoveDetails(Thread $thread, Forum $from, Forum $to): void
    {
        $this->changeType = ModerationLogType::MOVE;
        $this->contentType = ModerationContentType::THREAD;
        $this->thread = $thread;
        $this->message = "From {$from->title} ({$from->node_id}) to {$to->title} ({$to->node_id})";
    }

    public function setPostHardDeleteDetails(Post $post): void {
        $this->changeType = ModerationLogType::DELETE_HARD;
        $this->contentType = ModerationContentType::POST;
        $this->post = $post;
        $this->message = "#{$post->post_id} (in thread {$post->Thread->title})";
    }

    public function setThreadHardDeleteDetails(Thread $thread): void {
        $this->changeType = ModerationLogType::DELETE_HARD;
        $this->contentType = ModerationContentType::THREAD;
        $this->thread = $thread;
        $this->message = "{$thread->title}";
    }

    public function setPostSoftDeleteDetails(Post $post): void {
        $this->changeType = ModerationLogType::DELETE_SOFT;
        $this->contentType = ModerationContentType::POST;
        $this->post = $post;
        $this->reason = $post->DeletionLog->delete_reason;
        $this->message = "#{$post->post_id} (in thread {$post->Thread->title})";
    }

    public function setThreadSoftDeleteDetails(Thread $thread): void
    {
        $this->changeType = ModerationLogType::DELETE_SOFT;
        $this->contentType = ModerationContentType::THREAD;
        $this->thread = $thread;
        $this->reason = $thread->DeletionLog->delete_reason;
        $this->message = "{$thread->title}";
    }

    public function setPostMadeVisibleDetails(Post $post): void {
        $this->changeType = ModerationLogType::UNDELETED;
        $this->contentType = ModerationContentType::POST;
        $this->post = $post;
        $this->message = "#{$post->post_id} (in thread {$post->Thread->title})";
    }

    public function setThreadMadeVisibleDetails(Thread $thread): void {
        $this->changeType = ModerationLogType::UNDELETED;
        $this->contentType = ModerationContentType::THREAD;
        $this->thread = $thread;
        $this->message = "{$thread->title}";
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

    private function shouldIgnore(): bool {
        /** @var string[] $ignoreForums */
        $ignoreForums = $this->app->options()['vatger_logging_ignore_forums'];

        /** @var string[] $ignoreThreads */
        $ignoreThreads = $this->app->options()['vatger_logging_ignore_threads'];

        if ($this->thread?->Forum !== null) {
            $threadIdStr = strval($this->thread->Forum->node_id);

            // Check if we're ignoring the forum!
            if (array_find($ignoreForums, fn($ignoreId) => $threadIdStr === $ignoreId)) {
                return true;
            }
        }

        if ($this->post?->Thread !== null) {
            $threadIdStr = strval($this->post->Thread->thread_id);
            if (array_find($ignoreThreads, fn($ignoreId) => $threadIdStr === $ignoreId)) {
                return true;
            }

            $forumIdStr = strval($this->post->Thread->Forum?->node_id);
            if (array_find($ignoreForums, fn($ignoreId) => $forumIdStr === $ignoreId)) {
                return true;
            }
        }

        return false;
    }

    protected function _save(): void
    {
        if ($this->shouldIgnore()) {
            return;
        }

        /** @var VatgerModerationLog $entity */
        $moderationLogEntity = $this->em()->create(VatgerModerationLog::class);

        $moderationLogEntity->user_id = $this->user->user_id;
        $moderationLogEntity->ip_address = Ip::stringToBinary(\XF::app()->request()->getIp());
        $moderationLogEntity->thread_id = $this->getThreadId();
        $moderationLogEntity->post_id = $this->getPostId();
        $moderationLogEntity->reason = $this->reason;
        $moderationLogEntity->message = $this->message;
        $moderationLogEntity->change_type = $this->changeType->toString();
        $moderationLogEntity->content_type = $this->contentType->toString();

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