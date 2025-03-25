<?php

namespace VATGER\Auth\InlineMod\Post;

use VATGER\Auth\Entity\Post;
use XF\Mvc\Entity\AbstractCollection;
use XF\PrintableException;

class Move extends XFCP_Move {


    /**
     * ^ array:8 [
     * "thread_type" => "existing"
     * "node_id" => 0
     * "check_node_viewable" => true
     * "prefix_id" => 0
     * "title" => ""
     * "existing_url" => "http://localhost/index.php?threads/waiting-list-rating-training-visitor-endorsements.8/"
     * "alert" => true
     * "alert_reason" => ""
     * ]
     *
     * Structure of $options (when selecting 'existing thread') ^
     *
     * ^ array:8 [
     * "thread_type" => "new"
     * "node_id" => 2
     * "check_node_viewable" => true
     * "prefix_id" => 0
     * "title" => "[WAITING LIST] Rating Training + Visitor Endorsements"
     * "existing_url" => ""
     * "alert" => true
     * "alert_reason" => ""
     * ]
     *
     * Structure of $options (when selecting 'new thread')
     */

    /**
     * @param AbstractCollection<Post> $entities
     * @throws PrintableException
     */
    public function applyInternal(AbstractCollection $entities, array $options): void
    {
        dd($options);

        /** @var Post $entity */
        foreach ($entities as $entity) {
            dd($entity);
        }

        parent::applyInternal($entities, $options);
    }
}