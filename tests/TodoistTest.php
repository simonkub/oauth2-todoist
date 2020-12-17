<?php

namespace Simonkub\OAuth2\Client\Test;

use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Simonkub\OAuth2\Client\Todoist;

class TodoistTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private static string $CLIENT_ID = "CLIENT_ID";

    private static string $CLIENT_SECRET = "CLIENT_SECRET";

    private static string $REDIRECT_URI = "https://example.com/redirect";

    private static string $ACCESS_TOKEN = "ACCESS_TOKEN";

    private string $baseApiUrl = "https://api.todoist.com/sync/v8/sync";

    /** @test */
    public function it_uses_correct_auth_base_url()
    {
        $subject = new Todoist();

        $this->assertEquals(
            "https://todoist.com/oauth/authorize",
            $subject->getBaseAuthorizationUrl()
        );
    }

    /** @test */
    public function it_uses_correct_resource_owner_details_url()
    {
        $subject = new Todoist();
        $resourceOwnerDetailsUrl = $subject->getResourceOwnerDetailsUrl($this->getAccessToken());

        $accessToken = self::$ACCESS_TOKEN;
        $expectedResourceOwnerDetailsUrl = $this->baseApiUrl . "?token={$accessToken}&sync_token=*&resource_types=[\"user\"]";

        $this->assertEquals($expectedResourceOwnerDetailsUrl, $resourceOwnerDetailsUrl);
    }

    /** @test */
    public function it_sets_the_correct_scopes()
    {
        $subject = new Todoist();
        $scopes = ["foo", "bar"];

        $subject->setDefaultScopes($scopes);
        parse_str($subject->getAuthorizationUrl(), $result);

        $this->assertEquals(
            implode(",", $scopes),
            $result["scope"]
        );
    }

    /** @test */
    public function it_generates_access_tokens()
    {
        $subject = new Todoist();

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn('{"access_token": "MOCK_TOKEN","token_type": "Bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $subject->setHttpClient($client);

        $token = $subject->getAccessToken('authorization_code', ['code' => 'AUTHORIZATION_CODE']);
        $this->assertEquals('MOCK_TOKEN', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    /** @test */
    public function it_reads_resources()
    {
        $subject = new Todoist();

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn(file_get_contents(__DIR__."/projects_response.json"));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $subject->setHttpClient($client);

        $resource = $subject->readResource("projects", $this->getAccessToken());

        $this->assertEquals(396936926, $resource["projects"][0]["id"]);
    }

    /** @test */
    public function it_throws_an_exception_when_response_is_not_successfull()
    {
        $subject = new Todoist();

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn(file_get_contents(__DIR__."/failed_response_single.json"));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getStatusCode')->andReturn(400);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $subject->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $subject->readResource("projects", $this->getAccessToken());
    }

    private function getAccessToken(): AccessTokenInterface
    {
        return new AccessToken(["access_token" => static::$ACCESS_TOKEN]);
    }
}
