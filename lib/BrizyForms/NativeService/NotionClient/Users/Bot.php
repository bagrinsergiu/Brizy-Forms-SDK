<?php

namespace Notion\Users;

/**
 * @psalm-type BotJson = array<empty, empty>
 *
 * @psalm-immutable
 */
class Bot
{
    private function __construct()
    {
    }

    /** @param BotJson $array
     * @return self
     */
    public static function fromArray($array)
    {
        return new self();
    }

    /** @return BotJson */
    public function toArray()
    {
        return [];
    }
}
