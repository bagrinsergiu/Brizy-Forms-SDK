<?php

namespace BrizyForms\NativeService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ConvertKitNativeService
{

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.convertkit.com/v3/';

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $apis = array();

    public function __construct($apiKey = '')
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client(array(
                'base_url' => [$this->baseUrl, []],
            ));
        }

        return $this->httpClient;

    }

    /**
     * @param string $path
     * @param string $method
     * @param array $data
     *
     * @return array|bool|mixed|object|string
     */
    public function request($path = '', $method = 'get', $data = array())
    {

        $options = array(
            'query' => array(
                'api_key' => $this->getApiKey()
            )
        );

        switch ($method) {
            case 'get' :
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        $options['query'][$key] = $value;
                    }
                }
                break;
            case 'post' :
                if (!empty($data)) {
                    $json = array();
                    foreach ($data as $key => $value) {
                        $json[$key] = $value;
                    }
                    $options['json'] = $json;
                }
                break;
        }

        try {
            $response = $this->getHttpClient()->{$method}($path, $options);
            return json_decode($response->getBody());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }
        }

        return false;

    }

}