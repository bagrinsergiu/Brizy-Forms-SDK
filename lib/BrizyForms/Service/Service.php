<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AuthenticationDataException;
use BrizyForms\Exception\GroupDataException;
use BrizyForms\FieldMap;
use BrizyForms\Model\AuthenticationData;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Class Service
 * @package BrizyForms\Service
 */
abstract class Service implements ServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AuthenticationData
     */
    protected $authenticationData;

    /**
     * MailChimpService constructor.
     *
     * @param AuthenticationData|null $authenticationData
     */
    public function __construct(AuthenticationData $authenticationData = null)
    {
        if ($authenticationData instanceof AuthenticationData) {
            $this->setAuthenticationData($authenticationData);
        }

        $this->logger = new NullLogger();
    }

    /**
     * @param AuthenticationData $authenticationData
     */
    public function setAuthenticationData(AuthenticationData $authenticationData)
    {
        $this->authenticationData = $authenticationData;

        if ($this->hasValidAuthenticationData()) {
            $this->initializeNativeService();
        }
    }

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws AuthenticationDataException
     */
    public function createMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        if (!$this->hasValidAuthenticationData()) {
            throw new AuthenticationDataException();
        }

        $this->mapFields($fieldMap, $group_id);
        $this->internalCreateMember($fieldMap, $group_id, $data, $confirmation_email);
    }

    /**
     * @return array
     * @throws AuthenticationDataException
     */
    public function getGroups()
    {
        if (!$this->hasValidAuthenticationData()) {
            throw new AuthenticationDataException();
        }

        return $this->internalGetGroups();
    }

    /**
     * @param GroupData $groupData
     * @return Group
     * @throws GroupDataException
     */
    public function createGroup(GroupData $groupData)
    {
        if (!$this->hasValidGroupData($groupData)) {
            throw new GroupDataException('Invalid group data');
        }

        return $this->internalCreateGroup($groupData);
    }

    /**
     * @param Group|null $group
     * @return array|mixed
     * @throws AuthenticationDataException
     */
    public function getFields(Group $group = null)
    {
        if (!$this->hasValidAuthenticationData()) {
            throw new AuthenticationDataException();
        }

        return $this->internalGetFields($group);
    }

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    abstract protected function mapFields(FieldMap $fieldMap, $group_id = null);

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @param bool $confirmation_email
     * @return mixed
     */
    abstract protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false);

    /**
     * @return mixed
     */
    abstract protected function internalGetGroups();

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    abstract protected function internalCreateGroup(GroupData $groupData);

    /**
     * @param Group $group
     *
     * @return mixed
     */
    abstract protected function internalGetFields(Group $group = null);

    /**
     * @return bool
     */
    abstract protected function hasValidAuthenticationData();

    /**
     * @return void
     */
    abstract protected function initializeNativeService();

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    abstract protected function hasValidGroupData(GroupData $groupData);
}