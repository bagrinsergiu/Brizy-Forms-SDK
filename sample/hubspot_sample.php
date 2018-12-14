
<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $hubSpotService service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"My Name dffsssss", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"ss22333csc@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$hubSpotService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::HUBSPOT);

$hubSpotService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => 'demo' //'d18ad803-18f4-41b1-865d-1aa07ed40524' // or 'demo'
]));

var_dump($hubSpotService->getAccount());

$groups = $hubSpotService->getGroups();

$groupData = new \BrizyForms\Model\GroupData([
    'name' => 'List'.rand(1,1000)
]);

$group = $hubSpotService->createGroup($groupData);

var_dump($group);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

//var_dump($hubSpotService->getFields());

$hubSpotService->createFields($fieldMap, $group->getId());

$hubSpotService->createMember($fieldMap, $active_group, $dataArray);