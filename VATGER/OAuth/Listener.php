<?php

namespace VATGER\OAuth;

use XF\Mvc\Entity\Entity;

class Listener {
    public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure): void {
        $structure->columns[Setup::$OAUTH_DB_AUTH_COLUMN]       = ['type' => Entity::STR, 'default' => null];
        $structure->columns[Setup::$OAUTH_DB_REFRESH_COLUMN]   = ['type' => Entity::STR, 'default' => null];
        $structure->columns['vatsim_id']                        = ['type' => Entity::INT, 'default' => null];
    }
}