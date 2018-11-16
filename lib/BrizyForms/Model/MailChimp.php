<?php

namespace BrizyForms\Model;

class MailChimp
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @param $apiKey
     */
    public function setApiKey($apiKey)
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
}