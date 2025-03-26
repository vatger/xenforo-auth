<?php

namespace VATGER\Auth\Option;

use XF\Entity\Option;
use XF\Entity\Thread;
use XF\Finder\ForumFinder;
use XF\Finder\ThreadFinder;
use XF\Option\AbstractOption;

class IgnoreModeration extends AbstractOption {
    private static string $CHOICE_TYPE_FORUM = "Forum";
    private static string $CHOICE_TYPE_THREAD = "Thread";

    private static function renderTemplate(array $choices, Option $option, array $htmlParams, string $choiceType): string
    {
        return static::getTemplate('admin:option_template_vatger_ignoreModeration', $option, $htmlParams, [
            'choices' => $choices,
            'choiceType' => $choiceType,
            'nextCounter' => count($choices),
        ]);
    }

    public static function renderForumOption(Option $option, array $htmlParams): string
    {
        $choices = [];

        /** @var ForumFinder $finder */
        $finder = \XF::finder(ForumFinder::class);

        foreach ($option->option_value as $node) {
            /** @var ForumFinder|null $thread */
            $forum = $finder->where('node_id', $node)->fetchOne();

            $choices[] = [
                'id' => $node,
                'name' => $forum?->title ?? "Error: Forum with this ID doesn't exist!",
            ];

            $finder->resetWhere();
        }

        return self::renderTemplate($choices, $option, $htmlParams, self::$CHOICE_TYPE_FORUM);
    }

    public static function renderThreadOption(Option $option, array $htmlParams): string
    {
        $choices = [];

        /** @var ThreadFinder $finder */
        $finder = \XF::finder(ThreadFinder::class);

        foreach ($option->option_value as $node) {
            /** @var Thread|null $thread */
            $thread = $finder->where('thread_id', $node)->fetchOne();

            $choices[] = [
                'id' => $node,
                'name' => $thread?->title ?? "Error: Thread with this ID doesn't exist!",
            ];

            $finder->resetWhere();
        }

        return self::renderTemplate($choices, $option, $htmlParams, self::$CHOICE_TYPE_THREAD);
    }

    public static function verifyOption(array &$value): true
    {
        $output = [];

        foreach ($value as $node) {
            if (!isset($node['id'])) {
                continue;
            }

            $idStr = $node['id'];
            $split = explode(' ', $idStr);

            if (count($split) > 0) {
                $idStr = $split[0];
            }

            if (!is_numeric($idStr)) {
                continue;
            }

            if (array_find($output, fn(string $value) => $value === $idStr) !== null) {
                continue;
            }

            $output[] = $idStr;
        }

        sort($output);

        $value = $output;

        return true;
    }
}