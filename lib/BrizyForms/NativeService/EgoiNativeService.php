<?php

namespace BrizyForms\NativeService;

class EgoiNativeService extends NativeService
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.e-goi.com/v2/rest.php';

    protected function internalGetOptions()
    {
        return [];
    }
}