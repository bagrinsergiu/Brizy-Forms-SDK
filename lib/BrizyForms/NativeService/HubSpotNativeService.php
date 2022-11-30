<?php

namespace BrizyForms\NativeService;

class HubSpotNativeService extends NativeService
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.hubapi.com';

    protected function internalGetOptions()
    {
        $this->headers['Authorization'] = 'Bearer ' . $this->getApiKey();

        return [];
    }
}