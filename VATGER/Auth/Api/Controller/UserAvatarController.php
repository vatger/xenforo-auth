<?php

namespace VATGER\Auth\Api\Controller;

use VATGER\Auth\Helpers\ErrorResponse;
use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Entity\User;
use XF\Finder\UserFinder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Error;
use XF\Service\User\AvatarService;

class UserAvatarController extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertApiScopeByRequestMethod("vatger_user_avatar");
    }

    public function actionDelete(ParameterBag $params): ApiResult|Error
    {
        /** @var User | null $user */
        $user = $this->finder(UserFinder::class)
            ->where("user_id", $params->get("user_id"))
            ->fetchOne();

        if ($user == null) {
            return ErrorResponse::userNotFoundResponse($this, $params->get("user_id"));
        }

        /** @var AvatarService $avatarService */
        $avatarService = $this->service(AvatarService::class, $user);
        $avatarService->deleteAvatar();

        return $this->apiBoolResult(true);
    }
}