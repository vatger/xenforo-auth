<?php

namespace VATGER\Auth\Helpers;

use XF\Api\Controller\AbstractController as AbstractAPIController;
use XF\Mvc\Reply\Error;

class ErrorResponse
{
    private static string $CODE_USR_NOT_FOUND = "user_not_found";
    private static string $CODE_BAD_REQUEST = "http_bad_request";

    public static function userNotFoundResponse(AbstractAPIController $controller, mixed $userId): Error
    {
        return $controller->apiError(HttpResponseCode::HTTP_NOT_FOUND->getStatusCode(), self::$CODE_USR_NOT_FOUND, [
            'user_id' => $userId
        ]);
    }

    public static function badRequestResponse(AbstractAPIController $controller, array $params = []): Error
    {
        return $controller->apiError(HttpResponseCode::HTTP_BAD_REQUEST->getStatusCode(), self::$CODE_BAD_REQUEST, $params);
    }
}