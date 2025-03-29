<?php

namespace VATGER\Auth\Api\Controller;

use VATGER\Auth\Finder\VatgerModerationLogFinder;
use XF\Api\Controller\AbstractController;
use XF\Api\Mvc\Reply\ApiResult;

class TestController extends AbstractController {
    public function actionGet(): ApiResult
    {
        /** @var VatgerModerationLogFinder $finder */
        $finder = $this->finder(VatgerModerationLogFinder::class);

        $res = $finder->fetch()->toApiResults();

        return $this->apiResult($res);
    }
}