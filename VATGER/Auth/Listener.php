<?php

namespace VATGER\Auth;

use XF\Mvc\Entity\Entity;

class Listener {
    public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure): void {
        $structure->columns['vatsim_id']                        = ['type' => Entity::INT, 'default' => null];
    }
}