<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
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
     * @param Folder|null $folder
     * @return mixed
     */
    public function getGroups(Folder $folder = null);

    /**
     * @return array
     */
    public function getGroupProperties();

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
     * @return array
     */
    public function getFolders();

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @return FieldMap
     */
    public function createFields(FieldMap $fieldMap, $group_id = null);

    /**
     * @return Account
     */
    public function getAccount();

    /**
     * @return array
     */
    public function getAccountProperties();

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @param bool $confirmation_email
     * @return mixed
     */
    public function createMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false);

    /**
     * @return boolean
     */
    public function hasConfirmation();
}