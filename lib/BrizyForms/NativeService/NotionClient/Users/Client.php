<?php

namespace Notion\Users;

use Notion\NotionException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/** @psalm-import-type UserJson from User */
class Client
{
    /**
     * @var ClientInterface
     */
    private $psrClient;
    private $token;
    private $version;

    /**
     * @internal Use `\Notion\Notion::pages()` instead
     */

    /**
     * Client constructor.
     * @param ClientInterface $psrClient
     * @param string $token
     * @param string $version
     */
    public function __construct(
        $psrClient,
        $token,
        $version
    )
    {
        $this->psrClient = $psrClient;
        $this->token = $token;
        $this->version = $version;
    }

    /**
     * @param string $userId
     * @return User
     * @throws NotionException
     */
    public function find($userId)
    {
        $url = "https://api.notion.com/v1/users/{$userId}";
        $request = $this->requestFactory->createRequest("GET", $url)
            ->withHeader("Authorization", "Bearer {$this->token}")
            ->withHeader("Notion-Version", $this->version);

        $response = $this->psrClient->sendRequest($request);

        /** @var array */
        $body = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            /** @var array{ message: string, code: string} $body */
            $message = $body["message"];
            $code = $body["code"];

            throw new NotionException($message, $code);
        }

        /** @psalm-var UserJson $body */
        return User::fromArray($body);
    }

    /**
     * @return User[]
     * @throws NotionException
     */
    public function findAll()
    {
        $url = "https://api.notion.com/v1/users";
        $request = $this->requestFactory->createRequest("GET", $url)
            ->withHeader("Authorization", "Bearer {$this->token}")
            ->withHeader("Notion-Version", $this->version);

        $response = $this->psrClient->sendRequest($request);
        /** @var array $body */
        $body = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            /** @var array{ message: string, code: string} $body */
            $message = $body["message"];
            $code = $body["code"];

            throw new NotionException($message, $code);
        }

        /** @var array{ results: list<UserJson> } $body */
        return array_map(
            function (array $userData) {
                return User::fromArray($userData);
            },
            $body["results"]
        );
    }

    /**
     * @return User
     * @throws NotionException
     */
    public function me()
    {
        $url = "https://api.notion.com/v1/users/me";
        $request = $this->requestFactory->createRequest("GET", $url)
            ->withHeader("Authorization", "Bearer {$this->token}")
            ->withHeader("Notion-Version", $this->version);

        $response = $this->psrClient->sendRequest($request);
        /** @var array $body */
        $body = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            /** @var array{ message: string, code: string } $body */
            $message = $body["message"];
            $code = $body["code"];

            throw new NotionException($message, $code);
        }

        /** @psalm-var UserJson $body */
        return User::fromArray($body);
    }
}
