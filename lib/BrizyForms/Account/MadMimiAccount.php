<?php

namespace BrizyForms\Account;

use BrizyForms\Exception\InvalidAccountDataException;
use BrizyForms\Model\Response;

class MadMimiAccount implements AccountInterface
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $apiKey;

    public function __construct($email, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->email  = $email;
    }

    /**
     * @return Response
     * @throws InvalidAccountDataException
     */
    public function authenticate()
    {
        try {
            //@todo send request to madmimi api for validation credentials
        } catch (\Exception $e) {
            throw new InvalidAccountDataException();
        }

        return new Response(200, 'Authenticated');
    }
}