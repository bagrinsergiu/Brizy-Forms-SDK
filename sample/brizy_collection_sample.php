<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create Brizy-Collection service

//Authentication data
const  API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI2M2Y4Y3M1c2tndzBvODA4d3Nzb3MwYzhzc293ODBnZyIsImp0aSI6ImRhNjBmMmE2ZjEzYmI2Yjk2MDczNGRiMDUwYzk3ZTJiYzE5YjM5NWQ0ODRkZDg3NzIxODkzNzQyODgzN2I5N2IyMzUzOWFlNmI1NzEyMDgzIiwiaWF0IjoiMTYwOTE1Mjk3MC40MzY3NDEiLCJuYmYiOiIxNjA5MTUyOTcwLjQzNjc0NSIsImV4cCI6IjE2NDA2ODg5NzAuMzY4ODc2Iiwic3ViIjoiMTU4MDY5Iiwic2NvcGVzIjpbInByb2plY3QiXX0.H8tXVmV2UkzlJJ1ai5XYl3xZ4oYjJfiprJkrfu7Kh7Zd5acYr2KUZGU-Qr8UJi34MDHH4_u9XWLds3TAniLDXz1ViBBd3tVmZq5rKtz7ixhXaZdeB6ksEMMxXBhxguniQR2YK-mMxqrEqy4Syeh7ft48tEHBv4jfr7pr5qxEaVg';
const PROJECT_ID = 'someProjectId';

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}
//INITIALIZATION
/** @var BrizyForms\Service\BrizyCollectionService $brizyCollectionService */
$brizyCollectionService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::BRIZYCOLLECTION);
$brizyCollectionService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => API_KEY
]));
//AUTHENTICATION
//var_dump($brizyCollectionService->authenticate());
//GET GROUPS
var_dump($brizyCollectionService->getGroups());
//CREATE NEW GROUP
$field = [
    'priority' => 1,
    'required' => true,
    'hidden' => false,
    'label' => 'Some label',
    'slug' => 'Some-slug' . rand(1000, 10000),
    'description' => 'Tha description',
    'placement' => 'content',
    'type' => 'text',
    'settings'=>[
        'minLength' => 1,
        'maxLength' => 256,
    ]
];
$collectionType = [
    'id' => 'awesomella' . rand(1000, 10000),
    'slug' => 'awesomella' . rand(1000, 10000),
    'editor' => [
        'id' => 'someeditorid',
        'project'=>['id'=>PROJECT_ID],
    ],
    'fields' => [$field],
    'settings'=>[
        'icon' => '',
        'hidden'=>false,
        'titleSingular' => 'Awesome C-Type',
        'titlePlural' => 'Awesome C-Types',
    ],
    'project'=>['id'=>PROJECT_ID],
    'priority'=>1,
    'title' => 'Awesome C-Type',
];
$groupData = new \BrizyForms\Model\GroupData($collectionType);
var_dump($brizyCollectionService->createGroup($groupData));
//GET GROUP FIELDS
var_dump($brizyCollectionService->getFields());
// CREATE GROUP FIELDS
var_dump($brizyCollectionService->createFields());
//CREATE MEMBER
$brizyCollectionService->createMember($fieldMap, null, $dataArray);
