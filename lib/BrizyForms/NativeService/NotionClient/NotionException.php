<?php

namespace Notion;

use Throwable;

class NotionException extends \Exception
{
    /**
     * @var string
     */
    private $errorCode;

    /**
     * NotionException constructor.
     * @param string $message
     * @param string $errorCode
     */
    public function __construct($message, $errorCode)
    {
        $this->errorCode = $errorCode;

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function errorCode()
    {
        return $this->errorCode;
    }
}
