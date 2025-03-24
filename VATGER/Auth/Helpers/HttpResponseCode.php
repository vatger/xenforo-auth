<?php

namespace VATGER\Auth\Helpers;

enum HttpResponseCode
{
    case HTTP_NOT_FOUND;
    case HTTP_BAD_REQUEST;

    public function getStatusCode(): int
    {
        return match ($this) {
            HttpResponseCode::HTTP_NOT_FOUND => 404,
            HttpResponseCode::HTTP_BAD_REQUEST => 400,
        };
    }
}