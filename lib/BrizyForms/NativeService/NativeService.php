<?php

namespace BrizyForms\NativeService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

abstract class NativeService
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var integer
     */
    protected $response_code;

    public function __construct($apiKey = '', $apiSecret = '')
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
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
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client(array(
                'base_uri' => $this->baseUrl,
            ));
        }

        return $this->httpClient;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->response_code;
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
        $options = $this->getOptions();

        if (!empty($this->headers)) {
            $options['headers'] = $this->headers;
        }

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
            $this->response_code = $response->getStatusCode();
            return json_decode($response->getBody());
        } catch (RequestException $e) {
            $this->response_code = $e->getResponse()->getStatusCode();
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }
        }

        return false;
    }

    /**
     * @return array
     */
    abstract protected function internalGetOptions();

    /**
     * @return array
     */
    protected function getOptions()
    {
        return $this->internalGetOptions();
    }
}