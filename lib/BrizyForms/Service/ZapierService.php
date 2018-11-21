<?php

namespace BrizyForms\Service;


use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;

class ZapierService extends Service
{
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
                $fieldLink->setTarget($fieldLink->getSourceTitle());
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
        $data_json = json_encode(array_merge(['Email' => $data->getEmail()], $data->getFields()));

        $auth_data = $this->authenticationData->getData();

        $ch = curl_init($auth_data['webhook_url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_json))
        );

        $result = curl_exec($ch);

        curl_close($ch);

        if (!$result) {
            throw new ServiceException('Can\'t send data to this webhook_url');
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
     * @param Group $group
     *
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        return null;
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
        if (!isset($data['webhook_url']) || !preg_match('/^https:\/\/hooks.zapier.com\/hooks\/catch\//', $data['webhook_url'])) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    protected function initializeNativeService()
    {
    }

    /**
     * @return RedirectResponse|Response|null
     */
    public function authenticate()
    {
        return null;
    }
}