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
        return [
            'query' => [
                'api_key' => $this->getApiKey()
            ]
        ];
    }
}