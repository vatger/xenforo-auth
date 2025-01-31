<?php

namespace VATGER\Auth\Api\Controller;

use VATGER\Auth\Entity\User;
use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Finder\UserFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Error;
use XF\PrintableException;

class UserModeratorController extends AbstractController {
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertApiScopeByRequestMethod('moderators');
    }

    public function actionGet(ParameterBag $params): ApiResult|Error {
        $userId = $params->get('user_id');

        if ($userId == null) {
            return $this->apiError(404, "Missing user_id in request");
        }

        $userFinder = $this->finder(UserFinder::class);
        $user = $userFinder->where('user_id', '=', $userId)->fetchOne();

        if (!$user instanceof User) {
            return $this->apiError(404, "user_not_found", [
                'user_id' => $userId
            ]);
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
        $userId = $params->get('user_id');

        if ($userId == null) {
            return $this->apiError(404, "bad_request", [
                'user_id'
            ]);
        }

        $userFinder = $this->finder(UserFinder::class);
        $user = $userFinder->where('user_id', '=', $userId)->fetchOne();

        if (!$user instanceof User) {
            return $this->apiError(404, "user_not_found", [
                'user_id' => $userId
            ]);
        }

        $user->makeSuperModerator();

        return $this->apiBoolResult(true);
    }
}
