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
use BrizyForms\ServiceConstant;
use BrizyForms\NativeService\BrizyCollectionNativeService;
use BrizyForms\Utils\StringUtils;

final class BrizyCollectionService extends Service
{
    /** @var BrizyCollectionNativeService */
    private $nativeService;

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existingCustomFields = $this->nativeService->getCollectionType($group_id)['fields'];

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $name = strip_tags($fieldLink->getSourceTitle());
                $key_exist = array_search($name, array_column($existingCustomFields, 'title'));
                if ($key_exist === false) {
                    $payload = [

                        'title' => $name,
                        'type' => 'text'
                    ];
                    $newCustomField = $this->nativeService->createCollectionTypeField($group_id, $payload);
                }
                $tag = StringUtils::getSlug($newCustomField['title']);
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
     * @throws
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data, false);

        return $this->nativeService->createCollectionItem(
            $group_id,
            $data['slug'],
            $data['title'],
            $data['fields'],
            $data['status']);
    }

    /**
     * @param Folder|null $folder
     * @return mixed|null
     * @throws
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $collectionTypes = $this->nativeService->getCollectionTypes();
        $result = [];
        foreach ($collectionTypes as $i => $row) {
            $group = new Group();
            $group
                ->setId($row['id'])
                ->setName($row['title']);

            $result[$i] = $group;
        }

        return $result;
    }

    /**
     * @param Group $group
     * @return mixed
     * @throws
     */
    protected function internalGetFields(Group $group = null)
    {
        if (!$group) {
            throw new ServiceException('Group must be defined');
        }


        $result = [];
        $fields = $this->nativeService->getCollectionType($group->getId())['fields'];
        foreach ($fields as $i => $customField) {
            $result[$i] = new Field(
                $customField['label'],
                ($customField['slug'] ?? $customField['label']),
                $customField['required']
            );
        }

        return $result;
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
        $data = $this->authenticationData->getData();
        $this->nativeService = new BrizyCollectionNativeService($data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->hasValidAuthenticationData()
        ) {
            return new Response(400, 'Unauthenticated');
        }

        $response = $this->nativeService->checkAuthentication();
        $statusCode = $response->getResponseObject()->getStatusCode();

        if ($statusCode === 200) {
            return new Response(200, 'Successfully authenticated');
        } else {
            return new Response($statusCode, $response->getResponseObject()->getReasonPhrase());
        }
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     * @throws \Exception
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        try {
            $data = $groupData->getData();
            $collectionType = $this->nativeService->createCollectionType(
                $data['editor_id'],
                $data['title'],
                $data['slug'],
                $data['fields'],
                $data['settings'],
                $data['priority']
            );
        } catch (\Exception $exception) {
            throw new ServiceException('Group was not created');
        }

        return new Group($collectionType['id'], $collectionType['title']);
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

        return new Account($data['api_key']);
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
        return false;
    }
}
