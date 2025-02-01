<?php

namespace VATGER\Auth\Entity;

use XF\Api\Result\EntityResult;
use XF\Api\Result\EntityResultInterface;

class PermissionEntry extends XFCP_PermissionEntry {
    protected function setupApiResultData(EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = [])
    {
        if ($result->getResultType() === EntityResultInterface::TYPE_API) {
            $result->includeColumn('user_id');
            $result->includeColumn('permission_group_id');
            $result->includeColumn('permission_id');
        }
    }
}