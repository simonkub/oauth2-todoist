<?php


namespace Simonkub\OAuth2\Client;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class User implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function getId(): string
    {
        return $this->userData["id"];
    }

    public function getFullName(): string
    {
        return $this->userData["full_name"];
    }

    public function getEmail(): string
    {
        return $this->userData["email"];
    }

    public function toArray(): array
    {
        return $this->userData;
    }
}
