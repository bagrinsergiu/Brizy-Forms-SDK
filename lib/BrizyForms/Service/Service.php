<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;

abstract class Service implements ServiceInterface
{
    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     * @return mixed|void
     */
    public function createMember(FieldMap $fieldMap, $group_id)
    {
        $this->mapFields($fieldMap, $group_id);
        $this->internalCreateMember();
    }

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     * @return mixed
     */
    abstract protected function mapFields(FieldMap $fieldMap, $group_id);

    /**
     * @return mixed
     */
    abstract protected function internalCreateMember();
}