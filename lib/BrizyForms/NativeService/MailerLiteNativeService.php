<?php

namespace BrizyForms\NativeService;

class MailerLiteNativeService extends NativeService
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.mailerlite.com/api/v2/';

    protected function internalGetOptions()
    {
        return [
            'headers' => [
                'X-MailerLite-ApiKey' => $this->getApiKey()
            ]
        ];
    }
}