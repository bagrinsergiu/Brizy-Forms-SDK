<?php

namespace BrizyForms\Account;

use BrizyForms\Exception\InvalidAccountDataException;
use BrizyForms\Model\Response;
use MadMimi\Connection;
use MadMimi\CurlRequest;
use MadMimi\Options\Lists\All;

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
            $connection = new Connection($this->email, $this->apiKey, new CurlRequest());
            $options    = new All();
            $connection->request($options);
        } catch (\Exception $e) {
            throw new InvalidAccountDataException();
        }

        return new Response(200, 'Authenticated');
    }
}