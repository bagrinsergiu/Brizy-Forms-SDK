<?php

namespace BrizyForms\Model;

class FieldLink implements \Serializable, \JsonSerializable
{
    /**
     * @var string
     */
    protected $source_id;

    /**
     * @var string
     */
    protected $source_title;

    /**
     * @var string
     */
    protected $target;

    /**
     * @return string
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * @param $source_id
     * @return $this
     */
    public function setSourceId($source_id)
    {
        $this->source_id = $source_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceTitle()
    {
        return $this->source_title;
    }

    /**
     * @param $source_title
     * @return $this
     */
    public function setSourceTitle($source_title)
    {
        $this->source_title = $source_title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param $target
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->source_id,
            $this->source_title,
            $this->target
        ]);
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list(
            $this->source_id,
            $this->source_title,
            $this->target
            ) = unserialize($serialized);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'source_id' => $this->source_id,
            'source_title' => $this->source_title,
            'target' => $this->target
        ];
    }
}