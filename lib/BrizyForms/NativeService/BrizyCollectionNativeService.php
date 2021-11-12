<?php

namespace BrizyForms\NativeService;

use GraphQL\Client;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Variable;

final class BrizyCollectionNativeService
{
    const COLLECTION_STATUS_DRAFT = 'draft';

    /** @var Client */
    private $client;
    /** @var string */
    private $apiUrl;
    /** @var int */
    private $statusCode;
    /** @var string */
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiUrl = 'http://api.office.brizy.org/graphql';
        $this->apiKey = $apiKey;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function getClient(array $headers = [])
    {
        if (!isset($this->client)) {
            $this->client = new Client(
                $this->apiUrl,
                [],
                [
                    'connect_timeout' => 30,
                    'timeout' => 100,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey
                    ]
                ]
            );
        }

        return $this->client;
    }

    /**
     * @param $editor_id
     * @param $title
     * @param $slug
     * @param array $fields
     * @param array $settings
     * @param $priority
     * @return mixed
     * @throws \Exception
     */
    public function createCollectionType($editor_id, $title, $slug, array $fields, array $settings, $priority)
    {
        $mutation = (new Mutation('createCollectionType'))
            ->setOperationName('collectionTypeCreate')
            ->setVariables([new Variable('input', 'createCollectionTypeInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                [
                    (new Query('collectionType'))->setSelectionSet(
                        [
                            'id',
                            'title',
                            'slug',
                            'priority',
                            (new Query('fields'))
                                ->setSelectionSet(
                                    [
                                        'id',
                                        'slug',
                                        'settings',
                                        'label',
                                        'type',
                                        'priority',
                                        'required'
                                    ]
                                ),
                            (new Query('editor'))
                                ->setSelectionSet(
                                    [
                                        'id',
                                        'title',
                                        'url'
                                    ]
                                )
                        ]
                    )
                ]
            );

        $variables = ['input' => [
            'editor' => $editor_id,
            'title' => $title,
            'slug' => $slug,
            'fields' => $fields,
            'settings' => $settings,
            'priority' => $priority
        ]];

        $results = $this->getClient()->runQuery($mutation, true, $variables);
        $this->statusCode = $results->getResponseObject()->getStatusCode();

        return $results->getData()['createCollectionType']['collectionType'];
    }

    public function createCollectionTypeField($collection_id, $fields)
    {
        $mutation = (new Mutation('createCollectionTypeField'))
            ->setOperationName('collectionTypeFieldCreate')
            ->setVariables([new Variable('input', 'createCollectionTypeFieldInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                [
                    (new Query('collectionType'))->setSelectionSet(
                        [
                            'id',
                            'title',
                            'slug',
                            'priority',])
                ]);

        $variables = ['input' => [
            'collectionType' => $collection_id,
            'type' => $fields['type'],
            'label' => $fields['label'],
        ]];

        $results = $this->getClient()->runQuery($mutation, true, $variables);
        $this->statusCode = $results->getResponseObject()->getStatusCode();

        return $results->getData()['createCollectionTypeField']['collectionTypeField'];
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public
    function getCollectionTypes()
    {
        $query = (new Query('collectionTypes'))
            ->setOperationName('collectionTypes')
            ->setSelectionSet(['id', 'title',]);

        $results = $this->getClient()->runQuery($query, true, []);
        $this->statusCode = $results->getResponseObject()->getStatusCode();

        return $results->getData()['collectionTypes'];
    }

    /**
     * @param $collection_type_id
     * @param $slug
     * @param $title
     * @param array $fields
     * @param string $status
     * @return mixed
     * @throws \Exception
     */
    public
    function createCollectionItem($collection_type_id, $slug, $title, array $fields = [], $status = self::COLLECTION_STATUS_DRAFT, $pageData = null)
    {
        $mutation = (new Mutation('createCollectionItem'))
            ->setOperationName('createCollectionItem')
            ->setVariables([new Variable('input', 'createCollectionItemInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                [
                    (new Query('collectionItem'))->setSelectionSet(
                        [
                            'id',
                            'title',
                            'slug',
                            $this->getFieldsSelectionSet(),
                            (new Query('type'))
                                ->setSelectionSet(
                                    [
                                        'slug',
                                        'priority'
                                    ]
                                ),
                        ]
                    )
                ]
            );

        $variables = ['input' => [
            'type' => $collection_type_id,
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'fields' => $fields,
        ]];

        if ($pageData) {
            $variables['input']['pageData'] = $pageData;
        }

        $results = $this->client->runQuery($mutation, true, $variables);
        $this->statusCode = $results->getResponseObject()->getStatusCode();

        return $results->getData()['createCollectionItem']['collectionItem'];
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public
    function getCollectionType($collection_type_id)
    {
        $query = (new Query('collectionType'))
            ->setOperationName('collectionType')
            ->setArguments(['id' => $collection_type_id])
            ->setSelectionSet(
                [
                    'id',
                    'title',
                    'slug',
                    'priority',
                    (new Query('settings'))
                        ->setSelectionSet(
                            [
                                'hidden',
                                'icon',
                                'titlePlural',
                                'titleSingular'
                            ]
                        ),
                    (new Query('editor'))
                        ->setSelectionSet(
                            [
                                'id',
                                'title',
                                'url'
                            ]
                        ),
                    (new Query('fields'))
                        ->setSelectionSet(
                            [
                                'id',
                                'slug',
                                'settings',
                                'label',
                                'type',
                                'priority',
                                'required',
                                'hidden'
                            ]
                        )
                ]
            );

        $results = $this->client->runQuery($query, true, []);
        $this->statusCode = $results->getResponseObject()->getStatusCode();

        return $results->getData()['collectionType'];
    }

    public
    function checkAuthentication()
    {
        $query = (new Query('collectionTypes'))
            ->setOperationName('collectionTypes')
            ->setSelectionSet(['id']);

        return $this->getClient()->runQuery($query);
    }
}
