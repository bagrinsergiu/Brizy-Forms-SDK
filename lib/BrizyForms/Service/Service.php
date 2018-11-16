<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;

abstract class Service implements ServiceInterface
{
    public function createMember(FieldMap $fieldMap)
    {
        $this->mapFields($fieldMap);
        $this->internalCreateMember();
    }

    abstract protected function mapFields(FieldMap $fieldMap);

    abstract protected function internalCreateMember();
}