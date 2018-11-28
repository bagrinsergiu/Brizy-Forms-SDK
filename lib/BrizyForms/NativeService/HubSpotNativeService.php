<?php

namespace BrizyForms\NativeService;

class HubSpotNativeService extends NativeService
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.hubapi.com';

    protected function internalGetOptions()
    {
        return [
            'query' => [
                'hapikey' => $this->getApiKey()
            ]
        ];
    }
}