<?php

namespace BrizyForms\Model;

class FieldLink
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
}