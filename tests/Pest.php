<?php

declare(strict_types=1);

use OpenDxp\Bundle\AdminBundle\Tests\TestCase\LoginLinkTestCase;
use OpenDxp\TestFoundation\TestCase;

pest()->extend(TestCase::class)->in(
    'Feature/Application',
    'Feature/Grid',
    'Feature/Tree',
);
pest()->extend(LoginLinkTestCase::class)->in('Feature/LoginLink');
