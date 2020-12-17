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
    protected static string $BASE_AUTH_URL = "https://todoist.com/oauth";

    protected static string $BASE_API_URL = "https://api.todoist.com/sync/v8/sync";

    protected array $scopes = ["data:read"];

    public function getBaseAuthorizationUrl(): string
    {
        return static::$BASE_AUTH_URL . "/authorize";
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        $queryParams = $this->buildQueryString($params);

        return static::$BASE_AUTH_URL . "/access_token?{$queryParams}";
    }

    public function getResourceOwnerDetailsUrl(AccessTokenInterface $token): string
    {
        return static::$BASE_API_URL . "?token={$token->getToken()}&sync_token=*&resource_types=[\"user\"]";
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

        $request = $this->getAuthenticatedRequest(self::METHOD_POST, static::$BASE_API_URL, $token, $options);

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

        $request = $this->getAuthenticatedRequest(self::METHOD_POST, static::$BASE_API_URL, $token, $options);

        return $this->getParsedResponse($request);
    }

    protected function getDefaultHeaders(): array
    {
        return [
            "Content-Type" => "application/x-www-form-urlencoded",
            "Accept" => "application/json",
        ];
    }

    protected function getDefaultScopes(): array
    {
        return $this->scopes;
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() == 200) {
            return;
        }

        throw new IdentityProviderException(
            json_encode($data),
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new User($response);
    }
}
