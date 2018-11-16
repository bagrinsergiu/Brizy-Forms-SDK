<?php

namespace BrizyForms\Model;

class MailChimp
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $dc;

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

    /**
     * @param $dc
     */
    public function setDC($dc)
    {
        $this->dc = $dc;
    }

    /**
     * @return string
     */
    public function getDC()
    {
        return $this->dc;
    }
}