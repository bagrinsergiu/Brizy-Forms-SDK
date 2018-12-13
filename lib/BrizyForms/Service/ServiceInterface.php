<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
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
     * @param GroupData $groupData
     * @return Group
     */
    public function createGroup(GroupData $groupData);

    /**
     * @param Group $group
     * @return array
     */
    public function getFields(Group $group = null);

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @param bool $confirmation_email
     * @return mixed
     */
    public function createMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false);
}