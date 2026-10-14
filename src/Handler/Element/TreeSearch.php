<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Db\Helper;

final class TreeSearch
{
    /**
     * Converts the search of a tree folder into a LIKE pattern.
     *
     * The pattern matches:
     * - a key that contains the search anywhere
     * - any text in place of an asterisk in the search
     */
    public static function toLikePattern(string $search): string
    {
        return '%' . str_replace('*', '%', Helper::escapeLike($search)) . '%';
    }
}
