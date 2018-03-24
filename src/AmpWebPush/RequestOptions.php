<?php declare(strict_types=1);

namespace ekinhbayar\AmpWebPush;

class RequestOptions {

    public $endpoint;

    public $content;

    public $headers;

    public function __construct(string $endpoint, string $content, array $headers)
    {
        $this->endpoint = $endpoint;
        $this->content  = $content;
        $this->headers  = $headers;
    }
}
