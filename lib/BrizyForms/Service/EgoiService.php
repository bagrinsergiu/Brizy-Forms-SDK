<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use BrizyForms\Utils\StringUtils;
use Egoi\Api\RestImpl;
use Egoi\Factory;
use Egoi\Protocol;

class EgoiService extends Service
{

    /**
     * @var RestImpl
     */
    private $egoiNativeService;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $tag = StringUtils::getSlug($fieldLink->getSourceTitle());
                $fieldLink->setTarget($tag);
            }
        }

        return $fieldMap;
    }

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $arguments = [
            "listID" => $group_id,
            "email" => $data->getEmail()
        ];

        $arguments = array_merge($arguments, $this->arguments);
        $arguments = array_merge($arguments, $data->getFields());

        $subscriber = $this->egoiNativeService->addSubscriber($arguments);

        if (isset($subscriber['response'])) {
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return array
     * @throws ServiceException
     */
    protected function internalGetGroups()
    {
        $lists = $this->egoiNativeService->getLists($this->arguments);
        if (isset($lists['response'])) {
            throw new ServiceException($lists['response']);
        }

        $response = [];
        foreach ((array)$lists as $key => $list) {
            if (is_numeric($key)) {
                $group = new Group();
                $group
                    ->setId($list['listnum'])
                    ->setName($list['title']);

                $response[] = $group;
            }
        }

        return $response;
    }

    /**
     * @param Group $group
     *
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        return $this->_getFields();
    }

    /**
     * @return bool
     */
    protected function hasValidAuthenticationData()
    {
        if (!$this->authenticationData) {
            return false;
        }

        $data = $this->authenticationData->getData();
        if (!isset($data['api_key'])) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    protected function initializeNativeService()
    {
        $this->egoiNativeService = Factory::getApi(Protocol::Rest);

        $this->arguments = [
            "apikey" => $this->authenticationData->getData()['api_key']
        ];
    }

    /**
     * @return RedirectResponse|Response|null
     */
    public function authenticate()
    {
        return null;
    }

    public function _getFields()
    {
        return [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('First Name', 'first_name', false),
            new Field('Last Name', 'last_name', false),
            new Field('Birth Date', 'birth_date', false),
            new Field('Telephone', 'telephone', false),
            new Field('Cellphone', 'cellphone', false),
            new Field('Fax', 'fax', false),
        ];
    }
}