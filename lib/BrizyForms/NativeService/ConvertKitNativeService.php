<?php

namespace BrizyForms\NativeService;

class ConvertKitNativeService extends NativeService
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.convertkit.com/v3/';

    protected function internalGetOptions()
    {
        if ($this->getApiSecret() != '') {
            return [
                'query' => [
                    'api_secret' => $this->getApiSecret(),
                    'api_key' => $this->getApiKey()
                ]
            ];
        }

        return [
            'query' => [
                'api_key' => $this->getApiKey()
            ]
        ];
    }
}