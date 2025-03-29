<?php

namespace VATGER\Auth\Helpers;

/**
 * This class is **USED** in templates.
 *
 * In order for the methods here to be accepted,
 * they must be prefixed with any of:
 *
 * are
 * can
 * count
 * data
 * display
 * does
 * exists
 * fetch
 * filter
 * find
 * get
 * has
 * is
 * pluck
 * print
 * render
 * return
 * show
 * total
 * validate
 * verify
 * view
 *
 */
class Text {
    static function returnFirstLetterCapitalized($contents, array $params): string {
        return ucfirst($params[0]);
    }
}