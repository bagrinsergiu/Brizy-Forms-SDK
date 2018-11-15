<?php

namespace BrizyForms\Services;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;

interface ServiceInterface
{
    public function authenticate();
    public function getGroups();
    public function getFields(Group $group);
    public function createMember(FieldMap $fieldMap);
}