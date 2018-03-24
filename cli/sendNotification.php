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

    // from synx context using wait()
    /*
    $results = \Amp\Promise\wait($pushManager->sendArtax());

    foreach ($results as $uri => $body) {
        var_dump($body);
    }
    */

    // from async context inside an event loop
    \Amp\Loop::run(function() use ($pushManager) {
        $results = yield $pushManager->sendArtax();

        foreach ($results as $uri => $body) {
            var_dump($body);
        }
    });
}
catch (Throwable $e) {
    var_dump($e);
}
