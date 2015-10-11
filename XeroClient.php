<?php

namespace BlackOptic\Bundle\XeroBundle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use BlackOptic\Bundle\XeroBundle\Exception\FileNotFoundException;

class XeroClient extends Client
{
    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $tokenSecret;

    /**
     * {@inheritDoc}
     */
    public function __construct($config = array())
    {
        $required = array(
            'base_uri',
            'consumer_key',
            'consumer_secret',
            'token',
            'token_secret',
        );

        if (!array_key_exists('token', $config)) {
            $config['token'] = & $this->token;
        }

        if (!array_key_exists('token_secret', $config)) {
            $config['token_secret'] = & $this->tokenSecret;
        }

        if (!array_key_exists('base_uri', $config) && !array_key_exists('base_url', $config)) {
            throw new \InvalidArgumentException('base_uri is required in configuration');
        }

        // Use base_uri instead of deprecated base_url configuration.
        if (!array_key_exists('base_uri', $config)) {
            $config['base_uri'] = $config['base_url'];
            unset($config['base_url']);
        }

        // Guzzle no longer supports Collection.
        if (!array_key_exists('consumer_key', $config)) {
            throw new \InvalidArgumentException('consumer_key is required in configuration');
        }

        if (!array_key_exists('consumer_secret', $config)) {
            throw new \InvalidArgumentException('consumer_secret is required in configuration');
        }

        if (empty($config['private_key']) || !file_exists($config['private_key'])) {
            throw new FileNotFoundException('Unable able to find file: ' . $config['private_key']);
        }

        // Do not obliterate a stack that may be passed into the client.
        if (isset($config['handler']) && is_a($config['handler'], '\GuzzleHttp\HandlerStack')) {
            $stack = $config['handler'];
        } else {
            $stack = HandlerStack::create();
        }

        // Create an oauth middleware and push it onto the handler stack.
        $middleware = new Oauth1([
            'consumer_key' => $config['consumer_key'],
            'consumer_secret' => $config['consumer_secret'],
            'token' => $config['token'],
            'token_secret' => $config['token_secret'],
            'private_key_file' => $config['private_key'],
            'private_key_passphrase' => NULL,
            'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
        ]);
        $stack->push($middleware);

        parent::__construct([
            'base_uri' => $config['base_uri'],
            'handler' => $stack,
            'auth' => 'oauth'
        ]);
    }

    public function setToken($token, $tokenSecret)
    {
        $this->token = $token;
        $this->tokenSecret = $tokenSecret;
        return $this;
    }
}
