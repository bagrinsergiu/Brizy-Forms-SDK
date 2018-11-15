<?php

namespace BrizyForms\Model;

class RedirectResponse extends Response
{
    /**
     * @var string
     */
    protected $url;

    /**
     * RedirectResponse constructor.
     * @param $code
     * @param $message
     * @param $url
     */
    public function __construct($code, $message, $url)
    {
        $this->url = $url;

        parent::__construct($code, $message);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}