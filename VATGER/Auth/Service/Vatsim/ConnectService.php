<?php

namespace VATGER\Auth\Service\Vatsim;

use ArrayObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;
use XF\Service\AbstractService;

class ConnectService extends AbstractService {
    protected Client $client;
    protected array $connectOptions;

    public function __construct(\XF\App $app, ArrayObject $options)
    {
        parent::__construct($app);

        $this->client = \XF::app()->http()->client();

        $this->connectOptions = [
            'oauth_auth_endpoint' => $options->oauth_auth_endpoint,
            'oauth_token_endpoint' => $options->oauth_access_token_endpoint,
            'oauth_user_endpoint' => $options->oauth_user_endpoint,
            'client_id' => $options->client_id,
            'client_secret' => $options->client_secret,
            'redirect_url' => $options->redirect_url,
            'scopes' => $options->scopes
        ];
    }

    public function getRedirectURI(): string
    {
        $url = $this->connectOptions["oauth_auth_endpoint"];

        $scopes = urlencode($this->connectOptions["scopes"]);
        $redirectUrl = $this->connectOptions['redirect_url'];
        $clientId = $this->connectOptions['client_id'];

        return $url . '?response_type=code&client_id=' . $clientId . '&scope=' . $scopes . '&redirect_uri=' . $redirectUrl;
    }

    public function getAuthToken(string $code): mixed
    {
        try {
            $tokenResponse = $this->client->post($this->connectOptions['oauth_token_endpoint'], [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->connectOptions["client_id"],
                    'client_secret' => $this->connectOptions["client_secret"],
                    'redirect_uri' => $this->connectOptions["redirect_url"],
                ]
            ]);

            return Utils::jsonDecode($tokenResponse->getBody(), true);
        } catch (RequestException|GuzzleException $e) {
            if ($e->getResponse()->getStatusCode() !== 400) {
                \XF::logError($e->getMessage(), true);
            }

            return null;
        }
    }

    public function getUserDetails(string $accessToken): mixed
    {
        try {
            $userResponse = $this->client->get($this->connectOptions['oauth_user_endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);

            return Utils::jsonDecode($userResponse->getBody(), true);
        } catch (RequestException|GuzzleException $e) {
            \XF::logError($e->getMessage(), true);
            return null;
        }
    }
}