<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\Response;

class MadMimiService extends Service
{

    public function getGroups()
    {
        // TODO: Implement getLists() method.
    }

    public function getFields(Group $group)
    {
        // TODO: Implement getFields() method.
    }

    protected function mapFields(FieldMap $fieldMap, $group_id)
    {
        // TODO: Implement getLists() method.
    }

    protected function hasValidAuthenticationData()
    {
        if (!$this->authenticationData) {
            return false;
        }

        $data = $this->authenticationData->getData();
        if (!isset($data['email']) || !isset($data['api_key'])) {
            return false;
        }

        return true;
    }

    protected function internalGetGroups()
    {
        // TODO: Implement internalGetGroups() method.
    }

    /**
     * @return \BrizyForms\Model\RedirectResponse|Response|null
     */
    public function authenticate()
    {
        return null;
    }

    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param $data
     * @return mixed
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id, array $data)
    {
        // TODO: Implement internalCreateMember() method.
    }

    /**
     * @param Group $group
     * @return mixed
     */
    protected function internalGetFields(Group $group)
    {
        // TODO: Implement internalGetFields() method.
    }

	/**
	 * @return mixed
	 */
	protected function initializeNativeService() {
		// TODO: Implement initializeNativeService() method.
	}
}