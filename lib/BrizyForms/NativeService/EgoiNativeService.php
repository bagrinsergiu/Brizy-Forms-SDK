<?php

namespace BrizyForms\NativeService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EgoiNativeService
{

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.e-goi.com/v2/rest.php';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

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
        try {
            $response = $this->getHttpClient()->{$method}($path, $data);
            return json_decode($response->getBody());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }
        }

        return false;
    }
}