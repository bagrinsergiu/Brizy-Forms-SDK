<?php

namespace BrizyForms;

use BrizyForms\Exception\FieldMapException;
use BrizyForms\Model\FieldLink;

class FieldMap
{
    /**
     * @var array
     */
    protected $fields = array();

    /**
     * FieldMap constructor.
     * @param array $fields
     * @throws FieldMapException
     */
    public function __construct(array $fields)
    {
        $this->fields = $this->_map($fields);
    }

    /**
     * @param array $fields
     * @return array
     * @throws FieldMapException
     */
    protected function _map(array $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!isset($field['source']) || !isset($field['target'])) {
                throw new FieldMapException('"source" and "target" are required properties');
            }

            $fieldLink = new FieldLink();
            $fieldLink
                ->setSource($field['source'])
                ->setTarget($field['target']);

            $result[] = $fieldLink;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->fields;
    }

    public function transform($data)
    {
        return $data;
    }
}