<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\EgoiNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;

class EgoiService extends Service
{

    /**
     * @var EgoiNativeService
     */
    private $egoiNativeService;

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
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data);

        $status = 1;
        if ($confirmation_email) {
            $status = 0;
        }

        $functionOptions = array(
            'apikey' => $this->authenticationData->getData()['api_key'],
            "listID" => $group_id,
            "email" => $data->getEmail(),
            'status' => $status,
        );

        $functionOptions = array_merge($functionOptions, $data->getFields());

        $options = array(
            'functionOptions' => $functionOptions,
            'type' => 'json',
            'method' => 'addSubscriber'
        );

        $subscriber = $this->egoiNativeService->request('', 'get', $options);
        $subscriber = json_decode(json_encode($subscriber), true);

        if ((isset($subscriber['Egoi_Api']['addSubscriber']['ERROR']) && $subscriber['Egoi_Api']['addSubscriber']['ERROR'] != 'EMAIL_ADDRESS_INVALID') || isset($subscriber['Egoi_Api']['addSubscriber']['response'])) {
            $this->logger->error(json_encode($subscriber), ['service' => ServiceFactory::EGOI, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($subscriber));
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $options = array(
            'functionOptions' => array(
                'apikey' => $this->authenticationData->getData()['api_key']
            ),
            'type' => 'json',
            'method' => 'getLists'
        );

        $lists = $this->egoiNativeService->request('', 'get', $options);

        $lists = json_decode(json_encode($lists), true);

        if (isset($lists['Egoi_Api']['getLists']['ERROR'])) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($lists['Egoi_Api']['getLists'] as $key => $list) {
            if (strpos($key, 'key_') !== false) {
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
        $this->egoiNativeService = new EgoiNativeService();
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->egoiNativeService) {
            return new Response(400, 'native service was not init');
        }

        $options = array(
            'functionOptions' => array(
                'apikey' => $this->authenticationData->getData()['api_key']
            ),
            'type' => 'json',
            'method' => 'getUserData'
        );

        $userData = $this->egoiNativeService->request('', 'get', $options);
        if (!isset($userData->Egoi_Api->getUserData->USER_ID)) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
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

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();

        $functionOptions = array(
            'apikey' => $this->authenticationData->getData()['api_key'],
            'idioma_lista' => 'EN',
            'nome' => $data['name']
        );

        $options = array(
            'functionOptions' => $functionOptions,
            'type' => 'json',
            'method' => 'createList'
        );

        $list = $this->egoiNativeService->request('', 'get', $options);
        if (isset($list->Egoi_Api->createList->ERROR) || !isset($list->Egoi_Api->createList->LIST_ID)) {
            throw new ServiceException('Group was not created.');
        }

        return new Group($list->Egoi_Api->createList->LIST_ID, $data['name']);
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        $data = $groupData->getData();
        if (!isset($data['name'])) {
            return false;
        }

        return true;
    }

    /**
     * @return Account
     * @throws ServiceException
     */
    protected function internalGetAccount()
    {
        $options = array(
            'functionOptions' => array(
                'apikey' => $this->authenticationData->getData()['api_key']
            ),
            'type' => 'json',
            'method' => 'getUserData'
        );

        $userData = $this->egoiNativeService->request('', 'get', $options);
        if ($this->egoiNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $name = null;
        if (isset($userData->Egoi_Api->getUserData->EMAIL)) {
            $name = $userData->Egoi_Api->getUserData->EMAIL;
        }

        return new Account($name);
    }

    /**
     * @return array|null
     */
    protected function internalGetFolders()
    {
        return null;
    }

    /**
     * @return array
     */
    protected function internalGetGroupProperties()
    {
        return [
            [
                'name' => 'name',
                'title' => 'Name',
                'type' => 'input',
                'choices' => null
            ]
        ];
    }

    /**
     * @return array
     */
    protected function internalGetAccountProperties()
    {
        return [
            [
                'name' => 'api_key',
                'title' => 'Api Key'
            ]
        ];
    }

    /**
     * @return boolean
     */
    protected function internalHasConfirmation()
    {
        return true;
    }
}