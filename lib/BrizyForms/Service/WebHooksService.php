<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use GuzzleHttp\Client;
class WebHooksService extends Service
{

    private static function curlExec($value)
    {
        $client = new Client();

        $options = array();
        if(array_key_exists('header', $value))
        {
            $options['headers'] = $value['header'] ;
        }

        if(array_key_exists('message', $value)) {
            $options['form_params'] = $value['message'];
        }

        $client->request($value['sendType'], $value['url'], $options);

        return true;
    }

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
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data, false);

        $email = [];
        if ($data->getEmail()) {
            $email = ['Email' => $data->getEmail()];
        }

        $data_json = json_encode(array_merge($email, $data->getFields()));
        $auth_data = $this->authenticationData->getData();

        $sends = ['url' => $auth_data['urlHooks'], 'sendType' => $auth_data['sendType'], 'message' => $email];
        if (array_key_exists( 'header', $auth_data))
        {
            $sends['header'] = $auth_data['header'];
        }

        if (!self::curlExec($sends)) {
            throw new ServiceException('Can\'t send data to this webhook_url');
        }
    }

    protected function curlII($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        $info = curl_getinfo($ch);

        curl_close($ch);

        if ($info['total_time'] >= 3) {
            return false;
        }
        return true;
    }

    /**
     * @param Folder|null $folder
     * @return mixed|null
     */
    protected function internalGetGroups(Folder $folder = null)
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
        if (!isset($data['urlHooks'])) {
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
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        $data = $this->authenticationData->getData();
        var_dump($this->curlII($data['urlHooks']));

        if (!$this->hasValidAuthenticationData() || !$this->curlII($data['urlHooks']))
        {
            return new Response(400, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        return null;
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        return true;
    }

    /**
     * @return Account
     */
    protected function internalGetAccount()
    {
        $data = $this->authenticationData->getData();

        return new Account($data['webhook_url']);
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
        return null;
    }

    /**
     * @return array
     */
    protected function internalGetAccountProperties()
    {
        return [
            [
                'name' => 'webhook_url',
                'title' => 'Webhook URL'
            ]
        ];
    }

    /**
     * @return boolean
     */
    protected function internalHasConfirmation()
    {
        return false;
    }
}