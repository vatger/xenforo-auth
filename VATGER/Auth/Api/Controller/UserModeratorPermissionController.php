<?php

namespace VATGER\Auth\Api\Controller;

use VATGER\Auth\Entity\User;
use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Entity\PermissionEntry;
use XF\Finder\PermissionEntryFinder;
use XF\Finder\UserFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Error;
use XF\PrintableException;

class UserModeratorPermissionController extends AbstractController {
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertApiScopeByRequestMethod('moderators');
    }

    /**
     * @throws PrintableException
     */
    public function actionPost(ParameterBag $params): ApiResult|Error
    {
        $userId = $params->get('user_id');
        $input = $this->request->getInput();

        $userFinder = $this->finder(UserFinder::class);
        $user = $userFinder->where('user_id', $userId)->fetchOne();

        if (!is_array($input) || !$user instanceof User) {
            return $this->apiError(400, "error_body_or_user_not_found");
        }

        if (!$user->isSuperModerator()) {
            $user->makeSuperModerator();
        }

        // Delete all permissions of user
        /** @var PermissionEntryFinder $userFinder */
        $permEntryFinder = $this->finder(PermissionEntryFinder::class);
        $permissions = $permEntryFinder->where('user_id', $user->user_id)->fetch();

        $db = \XF::db();
        $db->beginTransaction();
        /** @var PermissionEntry $perm */
        foreach ($permissions as $perm) {
            $perm->delete(true, false);
        }
        $db->commit();


        // Create new permissions
        $db->beginTransaction();
        foreach ($input as $perm) {
            $permEntry = $this->em()->create(PermissionEntry::class);
            $permEntry->user_group_id = 0;
            $permEntry->user_id = $userId;
            $permEntry->permission_group_id = $perm["permission_group_id"];
            $permEntry->permission_id = $perm["permission_id"];
            $permEntry->permission_value = "allow";
            $permEntry->permission_value_int = 0;
            $permEntry->save(true, false);
        }
        $db->commit();

        $user->rebuildPermissionCombination();

        return $this->apiBoolResult(true);
    }
}