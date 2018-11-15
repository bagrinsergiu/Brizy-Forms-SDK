<?php

namespace BrizyForms\Services;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;

class MadMimi extends Service
{
    public function authenticate()
    {
        // TODO: Implement getLists() method.
    }

    public function getGroups()
    {
        // TODO: Implement getLists() method.
    }

    public function getFields(Group $group)
    {
        // TODO: Implement getFields() method.
    }

    protected function internalCreateMember()
    {
        // TODO: Implement getLists() method.
    }

    protected function mapFields(FieldMap $fieldMap)
    {
        $fieldLinks = $fieldMap->toArray();

        foreach ($fieldLinks as $fieldLink)
        {

        }
    }
}