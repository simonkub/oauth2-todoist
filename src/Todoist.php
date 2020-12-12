<?php

namespace Simonkub\OAuth2\Client;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;

class Todoist extends AbstractProvider
{
    protected static $BASE_AUTH_URL = "https://todoist.com/oauth";

    protected static $BASE_API_URL = "https://api.todoist.com/sync/v8/sync";

    protected $scopes = ["data:read"];

    public function getBaseAuthorizationUrl(): string
    {
        return static::$BASE_AUTH_URL . "/authorize";
    }

    protected function getDefaultHeaders(): array
    {
        return [
            "Content-Type" => "application/x-www-form-urlencoded",
            "Accept" => "application/json",
        ];
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        $queryParams = $this->buildQueryString($params);

        return static::$BASE_AUTH_URL . "/access_token?{$queryParams}";
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return static::$BASE_API_URL . "?token={$token->getToken()}&sync_token=*&resource_types=[\"user\"]";
    }

    protected function getDefaultScopes(): array
    {
        return $this->scopes;
    }

    public function setDefaultScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    public function readResource(string $resource, AccessTokenInterface $token, array $options = [], string $syncToken = "*")
    {
        return $this->readResources([$resource], $token, $options, $syncToken);
    }

    public function readResources(array $resources, AccessTokenInterface $token, array $options = [], string $syncToken = "*")
    {
        $requiredBody = [
            "sync_token" => $syncToken,
            "token" => $token->getToken(),
            "resource_types" => json_encode($resources),
        ];

        $body = array_merge($requiredBody, $options["body"] ?? []);
        $options["body"] = $this->buildQueryString($body);

        $request = $this->getAuthenticatedRequest("POST", static::$BASE_API_URL, $token, $options);

        return $this->getParsedResponse($request);
    }

    public function writeResources(array $commands, AccessTokenInterface $token, array $options = [])
    {
        $requiredBody = [
            "token" => $token->getToken(),
            "commands" => json_encode($commands),
        ];

        $body = array_merge($requiredBody, $options["body"] ?? []);
        $options["body"] = $this->buildQueryString($body);

        $request = $this->getAuthenticatedRequest("POST", static::$BASE_API_URL, $token, $options);

        return $this->getParsedResponse($request);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() == 200) {
            return;
        }

        throw new IdentityProviderException(
            json_encode($data),
            $response->getStatusCode(),
            $response
        );
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new User($response);
    }
}
