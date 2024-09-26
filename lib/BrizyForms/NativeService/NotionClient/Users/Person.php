<?php

namespace Notion\Users;

/**
 * @psalm-type PersonJson = array{email: string}
 *
 * @psalm-immutable
 */
class Person
{
    /**
     * @var string
     */
    private $email;

    /**
     * Person constructor.
     * @param string $email
     */
    private function __construct( $email)
    {
        $this->email = $email;
    }

    /** @param PersonJson $array */
    public static function fromArray(array $array)
    {
        return new self($array["email"]);
    }

    /** @return PersonJson */
    public function toArray()
    {
        return [
            "email" => $this->email,
        ];
    }

    /**
     * @return string
     */
    public function email()
    {
        return $this->email;
    }
}
