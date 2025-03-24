<?php

namespace VATGER\Auth\Option;

use XF\Entity\Option;
use XF\Entity\Thread;
use XF\Finder\ThreadFinder;
use XF\Option\AbstractOption;

class IgnoreModeration extends AbstractOption {
    public static function renderThreadOption(Option $option, array $htmlParams): string
    {
        $choices = [];

        /** @var ThreadFinder $finder */
        $finder = \XF::finder(ThreadFinder::class);

        foreach ($option->option_value as $node) {
            /** @var Thread|null $thread */
            $thread = $finder->where('thread_id', $node)->fetchOne();
            if (!$thread) continue;

            $choices[] = [
                'thread_id' => $thread->thread_id,
                'thread_name' => $thread->title,
            ];

            $finder->resetWhere();
        }

        return static::getTemplate('admin:option_template_vatger_ignoreModeration', $option, $htmlParams, [
            'choices' => $choices,
        ]);
    }

    public static function verifyOption(array &$value): true
    {
        $output = [];

        foreach ($value as $node) {
            $split = explode(' ', $node);
            $testStr = $node;

            if (count($split) > 0) {
                $testStr = $split[0];
            }

            if (!is_numeric($testStr)) {
                continue;
            }

            $output[] = (int)$testStr;
        }

        sort($output);

        $value = $output;

        return true;
    }
}