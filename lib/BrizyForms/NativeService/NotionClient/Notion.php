<?php

namespace Notion;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Notion\Databases\Client as DatabasesClient;
use Notion\Pages\Client as PagesClient;
use Notion\Users\Client as UsersClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class Notion
{
    const NOTION_VERSION = "2021-08-16";

    /** @var ClientInterface */
    private $psrClient;
    /** @var RequestFactoryInterface */
    private $requestFactory;
    /** @var Databases\string */
    private $token;

    /**
     * Notion constructor.
     * @param $psrClient
     * @param $requestFactory
     * @param $token
     */
    private function __construct(
        $psrClient,
        $requestFactory,
        $token
    )
    {
        $this->psrClient = $psrClient;
        $this->requestFactory = $requestFactory;
        $this->token = $token;
    }

    /**
     * @param string $token
     * @return Notion
     */
    public static function create($token)
    {
        $psrClient = Psr18ClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        return new self($psrClient, $requestFactory, $token);
    }

    /**
     * @param ClientInterface $psrClient
     * @param RequestFactoryInterface $requestFactory
     * @param string $token
     * @return Notion
     */
    public static function createWithPsrImplementations(
        $psrClient,
        $requestFactory,
        $token
    )
    {
        return new self($psrClient, $requestFactory, $token);
    }

    /**
     * @return UsersClient
     */
    public function users()
    {
        return new UsersClient(
            $this->psrClient,
            $this->requestFactory,
            $this->token,
            self::NOTION_VERSION
        );
    }

    /**
     * @return PagesClient
     */
    public function pages()
    {
        return new PagesClient(
            $this->psrClient,
            $this->requestFactory,
            $this->token,
            self::NOTION_VERSION
        );
    }

    /**
     * @return DatabasesClient
     */
    public function databases()
    {
        return new DatabasesClient(
            $this->psrClient,
            $this->requestFactory,
            $this->token,
            self::NOTION_VERSION,
        );
    }
}
