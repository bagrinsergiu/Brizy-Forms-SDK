<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;
require __DIR__ . '/config.php';

//create MailChimp service
$mailChimpService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\Service::MAILCHIMP, new \BrizyForms\Model\AuthenticationData([
    'access_token' => '27abb01297b7832e89cde4ef82ca0051',
    'dc' => 'us13'
]));

$groups = $mailChimpService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

foreach ($groups as $group) {
    var_dump($mailChimpService->getFields($group));
}

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$mailChimpService->createMember($fieldMap, $active_group, $dataArray);