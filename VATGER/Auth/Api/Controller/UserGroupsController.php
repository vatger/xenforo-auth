<?php

namespace VATGER\Auth\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Finder\UserGroupFinder;
use XF\Mvc\Entity\UserGroup;
use XF\Mvc\ParameterBag;

class UserGroupsController extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertApiScopeByRequestMethod('vatger_usergroup');
    }

    /**
     * @api-desc Gets a list of usergroups
     *
     * @api-out UserGroup[] $groups
     */
    public function actionGet(): ApiResult
    {
        /** @var UserGroupFinder $finder */
        $finder = $this->finder(UserGroupFinder::class);
        $usergroups = $finder->fetchColumns(['user_group_id', 'title', 'user_title']);
        return $this->apiResult(
            $usergroups,
        );
    }
}
