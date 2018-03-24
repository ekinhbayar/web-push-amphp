<?php declare(strict_types=1);

use Minishlink\WebPush\Subscription;
use ekinhbayar\AmpWebPush\WebPushClient;

require_once __DIR__ . "/../vendor/autoload.php";

$config = require_once __DIR__ . "/../config/config.php";

const AMP_DEBUG = true;

$payload = json_encode([
    'title' => 'Example Title',
    'body'  => 'Foo:Bar'
]);

$auth = [
    'VAPID' => [
        'subject'    => 'pushNotifications',
        'publicKey'  => $config['vapidPublicKey'],
        'privateKey' => $config['vapidPrivateKey']
    ]
];

try {

    $pushManager = new WebPushClient($auth);

    $subscriptions = $config['subscriptions'];

    foreach ($subscriptions as $browser => $subscriptionJson) {
        $subscriptionJson = json_decode($subscriptionJson, true);

        if (!$subscriptionJson) {
            continue;
        }

        $subscription = new Subscription($subscriptionJson['endpoint'], $subscriptionJson['keys']['p256dh'], $subscriptionJson['keys']['auth']);
        $pushManager->addNotification($subscription, $payload);
    }

    #$response = $pushManager->sendArtax();
    $response = $pushManager->sendCurl();
    var_dump($response);
}
catch (Throwable $e) {
    var_dump($e);
}
