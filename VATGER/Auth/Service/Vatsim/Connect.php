<?php

namespace VATGER\Auth\Service\Vatsim;

use ArrayObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use XF\Service\AbstractService;

class Connect extends AbstractService {
    protected Client $client;
    protected array $connectOptions;

    public function __construct(\XF\App $app, ArrayObject $options)
    {
        parent::__construct($app);

        $this->client = \XF::app()->http()->client();

        $this->connectOptions = [
            'base_url' => $options->base_url,
            'client_id' => $options->client_id,
            'client_secret' => $options->client_secret,
            'redirect_url' => $options->redirect_url,
            'scopes' => $options->scopes
        ];
    }

    public function getRedirectURI(): string
    {
        $base_url = $this->connectOptions["base_url"] . "/oauth/authorize";

        $scopes = urlencode($this->connectOptions["scopes"]);
        $redirectUrl = $this->connectOptions['redirect_url'];
        $clientId = $this->connectOptions['client_id'];
        
        return $base_url . '?response_type=code&client_id=' . $clientId . '&scope=' . $scopes . '&redirect_uri=' . $redirectUrl;
    }

    public function getAuthToken(string $code): mixed
    {
        try {
            $tokenResponse = $this->client->post($this->connectOptions['base_url'] . '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->connectOptions["client_id"],
                    'client_secret' => $this->connectOptions["client_secret"],
                    'redirect_uri' => $this->connectOptions["redirect_url"],
                ]
            ]);

            return \GuzzleHttp\json_decode($tokenResponse->getBody(), true);
        } catch (RequestException $e) {
            \XF::logException($e);
            return null;
        }
    }

    public function getUserDetails(string $accessToken): mixed
    {
        try {
            $userResponse = $this->client->get($this->connectOptions['base_url'] . '/api/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);

            return \GuzzleHttp\json_decode($userResponse->getBody(), true);
        } catch (RequestException $e) {
            \XF::logException($e);
            return null;
        }
    }
}
