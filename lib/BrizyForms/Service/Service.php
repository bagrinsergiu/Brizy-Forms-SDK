<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AuthenticationDataException;
use BrizyForms\FieldMap;

abstract class Service implements ServiceInterface
{
    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param $data
     * @return mixed|void
     */
    public function createMember(FieldMap $fieldMap, $group_id, array $data)
    {
        $this->mapFields($fieldMap, $group_id);
        $this->internalCreateMember($fieldMap, $group_id, $data);
    }

    /**
     * @return array|void
     * @throws AuthenticationDataException
     */
    public function getGroups()
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationDataException();
        }

        $this->internalGetGroups();
    }

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     * @return mixed
     */
    abstract protected function mapFields(FieldMap $fieldMap, $group_id);

    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param $data
     * @return mixed
     */
    abstract protected function internalCreateMember(FieldMap $fieldMap, $group_id, array $data);

    abstract protected function isAuthenticated();

    abstract protected function internalGetGroups();
}