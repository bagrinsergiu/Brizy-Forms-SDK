<?php

namespace BrizyForms\Model;

class AuthenticationData
{
    /**
     * @var string
     */
    protected $data;

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}