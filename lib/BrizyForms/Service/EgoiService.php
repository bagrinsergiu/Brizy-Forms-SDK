<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
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
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $functionOptions = array(
            'apikey' => $this->authenticationData->getData()['api_key'],
            "listID" => $group_id,
            "email" => $data->getEmail(),
            'status' => 1,
        );

        $functionOptions = array_merge($functionOptions, $data->getFields());

        $options = array(
            'functionOptions' => $functionOptions,
            'type' => 'json',
            'method' => 'addSubscriber'
        );

        $subscriber = $this->egoiNativeService->request('', 'get', $options);
        $subscriber = json_decode(json_encode($subscriber), true);

        if (isset($subscriber['Egoi_Api']['addSubscriber']['ERROR']) || isset($subscriber['Egoi_Api']['addSubscriber']['response'])) {
            $this->logger->error(json_encode($subscriber), ['service' => ServiceFactory::EGOI]);
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return array
     * @throws ServiceException
     */
    protected function internalGetGroups()
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