<?php

namespace Notion\Pages;

use Notion\Blocks\BlockInterface;
use Notion\NotionException;
use Notion\Pages\Properties\PropertyInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @psalm-import-type PageJson from Page
 */
class Client
{
    /**
     * @var ClientInterface
     */
    private  $psrClient;
    /**
     * @var RequestFactoryInterface
     */
    private  $requestFactory;
    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $version;

    /**
     * Client constructor.
     * @internal Use `\Notion\Notion::pages()` instead
     * @param $psrClient
     * @param $requestFactory
     * @param string $token
     * @param string $version
     */
    public function __construct(
         $psrClient,
         $requestFactory,
        string $token,
        string $version
    )
    {
        $this->psrClient = $psrClient;
        $this->requestFactory = $requestFactory;
        $this->token = $token;
        $this->version = $version;
    }

    public function find(string $pageId): Page
    {
        $url = "https://api.notion.com/v1/pages/{$pageId}";
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

        /** @psalm-var PageJson $body */
        return Page::fromArray($body);
    }

    /** @param list<BlockInterface> $content */
    public function create(Page $page, array $content = []): Page
    {
        $data = json_encode([
            "archived" => $page->archived(),
            "icon" => $page->icon()->toArray() ?? null,
            "cover" => $page->cover()->toArray() ?? null,
            "properties" => array_map(function(PropertyInterface $p){return $p->toArray();}, $page->properties()),
            "parent" => $page->parent()->toArray(),
            "children" => array_map(function(BlockInterface $b){ return $b->toArray();}, $content)
        ]);

        $url = "https://api.notion.com/v1/pages";
        $request = $this->requestFactory->createRequest("POST", $url)
            ->withHeader("Authorization", "Bearer {$this->token}")
            ->withHeader("Notion-Version", $this->version)
            ->withHeader("Content-Type", "application/json");

        $request->getBody()->write($data);

        $response = $this->psrClient->sendRequest($request);

        /** @var array */
        $body = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            /** @var array{ message: string, code: string} $body */
            $message = $body["message"];
            $code = $body["code"];

            throw new NotionException($message, $code);
        }

        /** @psalm-var PageJson $body */
        return Page::fromArray($body);
    }

    public function update(Page $page): Page
    {
        $data = json_encode([
            "archived" => $page->archived(),
            "icon" => $page->icon()->toArray() ?? null,
            "cover" => $page->cover()->toArray() ?? null,
            "properties" => array_map(function (PropertyInterface $p) {
                return $p->toArray();
            }, $page->properties()),
            "parent" => $page->parent()->toArray()
        ]);

        $pageId = $page->id();
        $url = "https://api.notion.com/v1/pages/{$pageId}";
        $request = $this->requestFactory->createRequest("PATCH", $url)
            ->withHeader("Authorization", "Bearer {$this->token}")
            ->withHeader("Notion-Version", $this->version)
            ->withHeader("Content-Type", "application/json");

        $request->getBody()->write($data);

        $response = $this->psrClient->sendRequest($request);

        /** @var array */
        $body = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            /** @var array{ message: string, code: string} $body */
            $message = $body["message"];
            $code = $body["code"];

            throw new NotionException($message, $code);
        }

        /** @psalm-var PageJson $body */
        return Page::fromArray($body);
    }

    /**
     * @param Page $page
     * @return Page
     */
    public function delete(Page $page): Page
    {
        $archivedPage = $page->archive();

        return $this->update($archivedPage);
    }
}
