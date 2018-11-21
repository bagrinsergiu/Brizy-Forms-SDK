<?php

namespace BrizyForms\Model;

class Response
{
    /**
     * Response constructor.
     * @param $code
     * @param $message
     */
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $code;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

}
