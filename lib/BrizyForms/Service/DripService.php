<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;
use DrewM\Drip\Dataset;
use DrewM\Drip\Drip;

class DripService extends Service
{

    /**
     * @var Drip
     */
    protected $dripNativeService;

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @return FieldMap|mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $name = StringUtils::getSlug($fieldLink->getSourceTitle());
                $fieldLink->setTarget($name);
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

        $dataset = new Dataset('subscribers', [
            'email' => $data->getEmail(),
            'custom_fields' => $data->getFields()
        ]);

        $response = $this->dripNativeService->post('subscribers', $dataset);
        if ($response->status != 200) {
            $this->logger->error(json_encode($response), ['service' => ServiceFactory::DRIP, 'method' => 'internalCreateMember']);
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return mixed
     */
    protected function internalGetGroups()
    {
        return null;
    }

    /**
     * @param Group|null $group
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetFields(Group $group = null)
    {
        $response = [];
        foreach ($this->_getFields() as $i => $name) {

            $field = new Field();
            $field
                ->setName($name)
                ->setSlug($name)
                ->setRequired(false);

            $response[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
        ];

        $response = array_merge($defaults, $response);

        return $response;
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
        if (!isset($data['api_key']) || !isset($data['account_id'])) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();

        $this->dripNativeService = new Drip($data['api_key'], $data['account_id']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->dripNativeService) {
            return new Response(400, 'native service was not init');
        }

        $response = $this->dripNativeService->get('campaigns');
        if ($response->status != 200) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @return array
     * @throws ServiceException
     */
    private function _getFields()
    {
        $response = $this->dripNativeService->get('custom_field_identifiers');
        if ($response->status != 200) {
            throw new ServiceException('Fields not found.');
        }

        return $response->custom_field_identifiers;
    }

}