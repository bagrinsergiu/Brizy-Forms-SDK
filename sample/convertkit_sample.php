
<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $convertKitService service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"My Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar1212@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$convertKitService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::CONVERTKIT);

$convertKitService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key'    => 'dbfzpAvqZJUNeoJh5YXdgA',
    'api_secret' => 'VrZuosovuNFoUmayj6Z8FgEEkQz1R3tfeNXl-5dyruk'
]));

var_dump($convertKitService->authenticate());

var_dump($convertKitService->getAccount());

$groups = $convertKitService->getGroups();

var_dump($groups);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group;
    break;
}

$fields = $convertKitService->getFields($active_group);

var_dump($fields);

$convertKitService->createMember($fieldMap, $active_group->getId(), $dataArray);