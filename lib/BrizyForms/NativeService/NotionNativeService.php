<?php

namespace BrizyForms\NativeService;

use GuzzleHttp\Client;

//use Notion\NotionClient;

class NotionNativeService
{
    /** @var Client */
    private $client;
    const BASE_URL = 'https://api.notion.com/v1/';
    const API_VERSION = '2021-05-13';

    /**
     * NotionNativeService constructor.
     * @param  $token
     */
    public function __construct($token)
    {
        $this->client = new Client(
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Notion-Version' => self::API_VERSION
                ]
            ]
        );
    }

    public function getDatabase($dbId)
    {
        $response = $this->client->request('get', self::BASE_URL . 'databases/' . $dbId);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createDatabase(){

    }

    public function getPage($pageId){
        $path = self::BASE_URL . 'pages/' . $pageId;
        var_dump(['URL'=>$path]);
        $response = $this->client->request('get', self::BASE_URL . 'pages/' . $pageId);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createPage($content){
        return $this->client->request('post', self::BASE_URL.'pages', ['form_params'=>json_decode($content, true)]);
    }

    public function getDbItems($dbId)
    {
        $response = $this->client->request('post', self::BASE_URL . 'databases/' . $dbId . '/query');

        return json_decode($response->getBody()->getContents(), true);
    }
}
