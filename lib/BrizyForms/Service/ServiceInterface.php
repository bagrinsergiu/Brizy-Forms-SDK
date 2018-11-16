<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;

interface ServiceInterface
{
    /**
     * @return array
     */
    public function getGroups();

    /**
     * @param Group $group
     * @return array
     */
    public function getFields(Group $group);

    /**
     * @param FieldMap $fieldMap
     * @param string $list
     * @return mixed
     */
    public function createMember(FieldMap $fieldMap, $list);
}