<?php

namespace VATGER\Auth\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Finder\UserGroupFinder;
use XF\Mvc\Entity\UserGroup;
use XF\Mvc\ParameterBag;

class UserGroupsController extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertApiScopeByRequestMethod('usergroup');
    }

    /**
     * @api-desc Gets a list of usergroups
     *
     * @api-out UserGroup[] $groups
     */
    public function actionGet()
    {
        /** @var UserGroupFinder $finder */
        $finder = $this->finder(UserGroupFinder::class);
        $usergroups = $finder->fetch();
        return $this->apiResult(
            $usergroups->toApiResults(),
        );
    }
}
