<?php declare(strict_types=1);

namespace ekinhbayar\AmpWebPush;

use Amp\Artax\Request;
use Amp\Artax\Response;
use Amp\Artax\DefaultClient;
use Amp\Artax\HttpException;
use Base64Url\Base64Url;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\Notification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use function Amp\call;

class WebPushClient extends WebPush {

    private $notifications;

    private $auth;

    public function __construct(array $auth = [], array $defaultOptions = [])
    {
        parent::__construct();

        if (isset($auth['VAPID'])) {
            $auth['VAPID'] = VAPID::validate($auth['VAPID']);
        }

        $this->auth = $auth;

        $this->setDefaultOptions($defaultOptions);
    }

    /**
     * @param Subscription $subscription
     * @param string       $payload
     * @param int          $automaticPadding
     *
     * @return bool
     * @throws \ErrorException
     */
    public function addNotification(
        Subscription $subscription,
        string $payload,
        int $automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH
    ): bool {
        if (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \ErrorException('Size of payload must not be greater than ' . Encryption::MAX_PAYLOAD_LENGTH . ' octets.');
        }

        $payload = Encryption::padPayload($payload, $automaticPadding, $subscription->getContentEncoding());

        $this->notifications[] = new Notification($subscription, $payload, [], $this->auth);

        return true;
    }

    public function sendArtax(): array
    {
        if (empty($this->notifications)) {
            return [];
        }

        $responses = [];

        $client = new DefaultClient;

        try {
            foreach ($this->notifications as $notification) {
                $notificationRequest = $this->prepare($notification);

                $responses[] = call(function () use ($client, $notificationRequest) {

                    $request = (new Request($notificationRequest->endpoint, 'POST'))
                        ->withHeaders($notificationRequest->headers)
                        ->withBody($notificationRequest->content);

                    $promise = $client->request($request);

                    /** @var Response $response */
                    $response = yield $promise;

                    return $response->getBody();
                });
            }
        }
        catch (\Throwable $e) {
            echo $e->getMessage();
        }

        $this->notifications = null;

        return $responses;
    }

    /**
     * @param Notification $notification
     *
     * @return RequestOptions
     * @throws \ErrorException
     */
    private function prepare(Notification $notification)
    {
        $subscription    = $notification->getSubscription();
        $endpoint        = $subscription->getEndpoint();
        $userPublicKey   = $subscription->getPublicKey();
        $userAuthToken   = $subscription->getAuthToken();
        $contentEncoding = $subscription->getContentEncoding();
        $payload         = $notification->getPayload();
        $options         = $notification->getOptions($this->getDefaultOptions());
        $auth            = $notification->getAuth($this->auth);

        if ($payload && $userPublicKey && $userAuthToken) {
            $encrypted      = Encryption::encrypt($payload, $userPublicKey, $userAuthToken, $contentEncoding);
            $cipherText     = $encrypted['cipherText'];
            $salt           = $encrypted['salt'];
            $localPublicKey = $encrypted['localPublicKey'];

            $headers = [
                'Content-Type'     => 'application/octet-stream',
                'Content-Encoding' => $contentEncoding,
            ];

            if ($contentEncoding === "aesgcm") {
                $headers['Encryption'] = 'salt=' . Base64Url::encode($salt);
                $headers['Crypto-Key'] = 'dh=' . Base64Url::encode($localPublicKey);
            }

            $encryptionContentCodingHeader = Encryption::getContentCodingHeader($salt, $localPublicKey, $contentEncoding);
            $content                       = $encryptionContentCodingHeader . $cipherText;

            $headers['Content-Length'] = Utils::safeStrlen($content);
        } else {
            $headers = [
                'Content-Length' => 0,
            ];

            $content = '';
        }

        $headers['TTL'] = $options['TTL'];

        if (isset($options['urgency'])) {
            $headers['Urgency'] = $options['urgency'];
        }

        if (isset($options['topic'])) {
            $headers['Topic'] = $options['topic'];
        }

        // if GCM
        if (substr($endpoint, 0, strlen(self::GCM_URL)) === self::GCM_URL) {
            if (array_key_exists('GCM', $auth)) {
                $headers['Authorization'] = 'key=' . $auth['GCM'];
            } else {
                throw new \ErrorException('No GCM API Key specified.');
            }
        } // if VAPID (GCM doesn't support it but FCM does)
        elseif (array_key_exists('VAPID', $auth)) {
            $vapid = $auth['VAPID'];

            $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

            if (!parse_url($audience)) {
                throw new \ErrorException('Audience "' . $audience . '"" could not be generated.');
            }

            $vapidHeaders = VAPID::getVapidHeaders($audience, $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'], $contentEncoding);

            $headers['Authorization'] = $vapidHeaders['Authorization'];

            if ($contentEncoding === 'aesgcm') {
                if (array_key_exists('Crypto-Key', $headers)) {
                    $headers['Crypto-Key'] .= ';' . $vapidHeaders['Crypto-Key'];
                } else {
                    $headers['Crypto-Key'] = $vapidHeaders['Crypto-Key'];
                }
            } else if ($contentEncoding === 'aes128gcm' && substr($endpoint, 0, strlen(self::FCM_BASE_URL)) === self::FCM_BASE_URL) {
                $endpoint = str_replace('fcm/send', 'wp', $endpoint);
            }
        }

        $requestOptions = new RequestOptions($endpoint, $content, $headers);

        return $requestOptions;
    }

    public function sendCurl(): array
    {
        if (empty($this->notifications)) {
            return [];
        }

        $responses = [];

        $ch = curl_init();

        foreach ($this->notifications as $notification) {
            $request = $this->prepare($notification);

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => $request->content,
                CURLOPT_URL            => $request->endpoint,
                CURLOPT_HTTPHEADER     => $request->headers
            ]);

            $responses[] = curl_exec($ch);
        }

        curl_close($ch);

        return $responses;
    }

    public function sendNotification(Subscription $subscription, ?string $payload = null, bool $flush = false, array $options = [], array $auth = [])
    {
        throw new MethodDisabledException();
    }

}
