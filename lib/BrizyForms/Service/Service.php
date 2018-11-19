<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AuthenticationDataException;
use BrizyForms\FieldMap;
use BrizyForms\Model\AuthenticationData;
use BrizyForms\Model\Group;

abstract class Service implements ServiceInterface
{
    /**
     * @var AuthenticationData
     */
    protected $authenticationData;

    /**
     * MailChimpService constructor.
     * @param AuthenticationData|null $authenticationData
     */
    public function __construct(AuthenticationData $authenticationData = null)
    {
        $this->authenticationData = $authenticationData;
    }

    /**
     * @param AuthenticationData $authenticationData
     */
    public function setAuthenticationData(AuthenticationData $authenticationData)
    {
        $this->authenticationData = $authenticationData;
    }

    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param array $data
     * @return mixed|void
     * @throws AuthenticationDataException
     */
    public function createMember(FieldMap $fieldMap, $group_id, array $data)
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationDataException();
        }

        $this->mapFields($fieldMap, $group_id);
        $this->internalCreateMember($fieldMap, $group_id, $data);
    }

    /**
     * @return array
     * @throws AuthenticationDataException
     */
    public function getGroups()
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationDataException();
        }

        return $this->internalGetGroups();
    }

    /**
     * @param Group $group
     * @return array
     * @throws AuthenticationDataException
     */
    public function getFields(Group $group)
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationDataException();
        }

        return $this->internalGetFields($group);
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

    /**
     * @return mixed
     */
    abstract protected function internalGetGroups();

    /**
     * @param Group $group
     * @return mixed
     */
    abstract protected function internalGetFields(Group $group);

    /**
     * @return bool
     */
    abstract protected function isAuthenticated();
}