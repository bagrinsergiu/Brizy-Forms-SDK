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
            if (!isset($field['source_id']) || !isset($field['source_title']) || !isset($field['target'])) {
                throw new FieldMapException('"source_id" && "source_title" && "target" are required properties');
            }

            $fieldLink = new FieldLink();
            $fieldLink
                ->setSourceId($field['source_id'])
                ->setSourceTitle($field['source_title'])
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