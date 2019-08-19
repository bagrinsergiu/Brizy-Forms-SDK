
<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create SendinBlue service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"New test field", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"xxxxxxj7j7xdddddddddd@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$sendinBlueService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::SENDINBLUE);

$sendinBlueService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => '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'
]));

//var_dump($sendinBlueService->authenticate());
//
//var_dump($sendinBlueService->getGroupProperties());
//
//var_dump($sendinBlueService->getAccount());
//
//var_dump($sendinBlueService->getFolders());

//$groupData = new \BrizyForms\Model\GroupData([
//    'name' => 'List'.rand(1,1000),
//    'folder' => 8
//]);
//
//var_dump($sendinBlueService->createGroup($groupData));

$groups = $sendinBlueService->getGroups();

$active_group = null;
foreach ($groups as $group) {
  //  var_dump($group);
    $active_group = $group->getId();
}

$fields = $sendinBlueService->getFields();

var_dump($fields);

$sendinBlueService->createFields($fieldMap, $active_group);

var_dump($fieldMap);

$sendinBlueService->createMember($fieldMap, $active_group, $dataArray);