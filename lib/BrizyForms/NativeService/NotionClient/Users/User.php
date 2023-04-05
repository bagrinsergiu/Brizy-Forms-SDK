<?php

namespace Notion\Users;

/**
 * @psalm-import-type PersonJson from Person
 * @psalm-import-type BotJson from Bot
 *
 * @psalm-type UserJson = array{
 *     id: string,
 *     name: string,
 *     avatar_url: string|null,
 *     type: string,
 *     person?: PersonJson,
 *     bot?: BotJson,
 * }
 *
 * @psalm-immutable
 */
class User
{
    const ALLOWED_TYPES = ["person", "bot"];

    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string|null */
    private $avatarUrl;
    /** @var string */
    private $type;
    /** @var Person|null */
    private $person;
    /** @var Bot|null */
    private $bot;

    /**
     * User constructor.
     * @param string $id
     * @param string $name
     * @param string|null $avatarUrl
     * @param $type
     * @param Person|null $person
     * @param Bot|null $bot
     * @throws
     */
    private function __construct(
        $id,
        $name,
        $avatarUrl,
        $type,
        $person,
        $bot
    )
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new \Exception("Invalid user type: '{$type}'.");
        }
        $this->id = $id;
        $this->name = $name;
        $this->avatarUrl = $avatarUrl;
        $this->type = $type;
        $this->person = $person;
        $this->bot = $bot;
    }

    /**
     * @param UserJson $array
     * @return self
     * @throws \Exception
     */
    public static function fromArray(array $array)
    {
        $person = array_key_exists("person", $array) ? Person::fromArray($array["person"]) : null;
        $bot = array_key_exists("bot", $array) ? Bot::fromArray($array["bot"]) : null;

        return new self(
            $array["id"],
            $array["name"],
            $array["avatar_url"],
            $array["type"],
            $person,
            $bot
        );
    }

    /** @return UserJson */
    public function toArray()
    {
        $array = [
            "id" => $this->id,
            "name" => $this->name,
            "avatar_url" => $this->avatarUrl,
            "type" => $this->type,
        ];

        if ($this->isPerson()) {
            $array["person"] = $this->person->toArray();
        }
        if ($this->isBot()) {
            $array["bot"] = $this->bot->toArray();
        }

        return $array;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function avatarUrl()
    {
        return $this->avatarUrl;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return Person
     */
    public
    function person()
    {
        return $this->person;
    }

    /**
     * @return Bot|null
     */
    public function bot()
    {
        return $this->bot;
    }

    /**
     * @psalm-assert-if-true Person $this->person
     * @psalm-assert-if-true Person $this->person()
     * @return bool
     */
    public function isPerson()
    {
        return $this->type === "person";
    }

    /**
     * @psalm-assert-if-true Bot $this->bot
     * @psalm-assert-if-true Bot $this->bot()
     * @return bool
     */
    public function isBot()
    {
        return $this->type === "bot";
    }
}
