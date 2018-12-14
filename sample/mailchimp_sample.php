<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create MailChimp service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"Test Field Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar.brizy@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$mailChimpService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::MAILCHIMP);

$mailChimpService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => 'e28b395504ca7748528dfb42833a36cb-us13'
]));

var_dump($mailChimpService->authenticate());

var_dump($mailChimpService->getAccount());

$groupData = new \BrizyForms\Model\GroupData([
    'name' => 'List777777',
    'reminder_message' => 'sdfdsfsd',
    'from_name' => 'Andrei',
    'from_email' => 'bodnar1212@gmail.com'
]);

$group = $mailChimpService->createGroup($groupData);
var_dump($group);

$groups = $mailChimpService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

foreach ($groups as $group) {
    var_dump($mailChimpService->getFields($group));
}

$mailChimpService->createFields($fieldMap, $group->getId());

$mailChimpService->createMember($fieldMap, $group->getId(), $dataArray, true);