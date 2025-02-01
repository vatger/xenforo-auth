<?php

namespace VATGER\Auth\Api\Controller;

use VATGER\Auth\Entity\User;
use VATGER\Auth\Helpers\ErrorResponse;
use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Finder\UserFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Error;
use XF\PrintableException;
use XF\Service\UpdatePermissionsService;

class UserModeratorController extends AbstractController {

    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertApiScopeByRequestMethod('vatger_moderator');
    }

    private function _getUser(ParameterBag $params) {
        $userId = $params->get('user_id');

        if ($userId == null || !is_numeric($userId)) {
            return ErrorResponse::badRequestResponse($this, ['user_id' => $userId]);
        }

        /** @var User|null $user */
        $userFinder = $this->finder(UserFinder::class);
        $user = $userFinder->where('user_id', $userId)->fetchOne();

        if ($user == null) {
            return ErrorResponse::userNotFoundResponse($this, $userId);
        }

        return $user;
    }

    public function actionGet(ParameterBag $params): ApiResult|Error {
        $user = $this->_getUser($params);
        if ($user instanceof Error) {
            return $user;
        }

        return $this->apiResult([
            'is_moderator' => $user->isModerator(),
            'is_super_moderator' => $user->isSuperModerator()
        ]);
    }

    /**
     * @throws PrintableException
     */
    public function actionPost(ParameterBag $params): ApiResult|Error
    {
        $input = $this->request->getInput();
        $user = $this->_getUser($params);
        if ($user instanceof Error) {
            return $user;
        }

        if (!is_array($input)) {
            return ErrorResponse::badRequestResponse($this, ['input' => $input]);
        }

        if (!$user->isSuperModerator()) {
            $user->makeSuperModerator();
        }

        $permissionEntries = [];

        foreach ($input as $key => $value) {
            if (!is_numeric($key)) {
                continue;
            }

            $pgid = $value['permission_group_id'];
            $pid = $value['permission_id'];
            $pval = $value['permission_value'] ?? "allow";

            if (!isset($permissionEntries[$pgid])) {
                $permissionEntries[$pgid] = [];
            }

            $permissionEntries[$pgid][$pid] = $pval;
        }

        $permissionUpdater = $this->service(UpdatePermissionsService::class);
        $permissionUpdater->setUser($user);
        $permissionUpdater->updatePermissions($permissionEntries);

        return $this->apiBoolResult(true);
    }

    /**
     * @throws PrintableException
     */
    public function actionDelete(ParameterBag $params): ApiResult|Error
    {
        $user = $this->_getUser($params);
        if ($user instanceof Error) {
            return $user;
        }

        $rowsAffected = $user->deletePermissionEntries();
        $wasModerator = $user->deleteSuperModerator();

        if ($rowsAffected > 0 || $wasModerator) {
            $permissionUpdater = $this->service(UpdatePermissionsService::class);
            $permissionUpdater->setUser($user);
            $permissionUpdater->triggerCacheRebuild();

            if ($this->app->container()->isCached('permission.builder')) {
                $this->app->permissionBuilder()->refreshData();
            }
        }

        return $this->apiBoolResult(true);
    }
}
