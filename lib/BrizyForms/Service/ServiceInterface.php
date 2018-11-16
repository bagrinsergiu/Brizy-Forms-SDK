<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;

interface ServiceInterface
{
    public function getGroups();
    public function getFields(Group $group);
    public function createMember(FieldMap $fieldMap);
}