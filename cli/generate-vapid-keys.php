<?php declare(strict_types=1);

/**
 * Run only once.
 */

use Minishlink\WebPush\VAPID;
const __PROJECT_ROOT__ = __DIR__ . "/../";

require_once __PROJECT_ROOT__ . "vendor/autoload.php";
var_dump( VAPID::createVapidKeys() );
