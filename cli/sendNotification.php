<?php declare(strict_types=1);

use Amp\Loop;
use Minishlink\WebPush\Subscription;
use ekinhbayar\AmpWebPush\WebPushClient;

require_once __DIR__ . "/../vendor/autoload.php";

$config = require_once __DIR__ . "/../config/config.php";

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

    // from async context inside an event loop
    Loop::run(function () use ($pushManager) {
        $results = yield $pushManager->send();

        foreach ($results as $uri => $body) {
            \var_dump('Body', $body);
        }
    });
}
catch (\Throwable $e) {
    \var_dump($e);
}
