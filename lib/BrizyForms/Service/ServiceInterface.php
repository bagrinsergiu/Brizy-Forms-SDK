<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;

interface ServiceInterface
{
    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null);

    /**
     * @return array
     */
    public function getGroups();

    /**
     * @param Group $group
     * @return array
     */
    public function getFields(Group $group = null);

    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param $data
     * @return mixed
     */
    public function createMember(FieldMap $fieldMap, $group_id = null, array $data = []);
}