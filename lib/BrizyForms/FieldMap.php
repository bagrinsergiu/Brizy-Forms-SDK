<?php

namespace BrizyForms;

use BrizyForms\Exception\FieldMapException;
use BrizyForms\Model\Data;
use BrizyForms\Model\FieldLink;
use BrizyForms\Model\TransformedData;

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
            if (!isset($field['sourceId']) || !isset($field['sourceTitle']) || !isset($field['target'])) {
                throw new FieldMapException('"source_id" && "source_title" && "target" are required properties');
            }

            $fieldLink = new FieldLink();
            $fieldLink
                ->setSourceId($field['sourceId'])
                ->setSourceTitle($field['sourceTitle'])
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

    /**
     * @param array $data
     * @return array|TransformedData
     * @throws FieldMapException
     */
    public function transform(array $data)
    {
        $mergeFields = [];
        $email = null;
        foreach ($this->fields as $i => $field) {
            if ($field->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                continue;
            }

            foreach ($data as $key => $row) {
                if (!$row instanceof Data) {
                    throw new FieldMapException('Not instanceof Data');
                }

                if ($row->getName() == $field->getSourceId()) {
                    if ($field->getTarget() == ServiceConstant::EMAIL_FIELD && filter_var($row->getValue(), FILTER_VALIDATE_EMAIL)) {
                        $email = $row->getValue();
                    } else {
                        $mergeFields[$field->getTarget()] = $row->getValue();
                    }
                    break;
                }
            }
        }

        if (!$email) {
            throw new FieldMapException('Email was not found.');
        }

        $data = new TransformedData();
        $data
            ->setEmail($email)
            ->setFields($mergeFields);

        return $data;
    }
}