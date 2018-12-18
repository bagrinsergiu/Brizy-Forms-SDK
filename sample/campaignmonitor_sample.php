
<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $campaignMonitorService service

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

$campaignMonitorService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::CAMPAIGNMONITOR);

$campaignMonitorService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key'  => 'EvjzjXOpziCS0pkcyah6UKUHpTlQ7SOWuB+3hK2FqP+o+g+C1wyMFr2EOXeOxAmwO5zuSevNBsRjyQdHV40c1pJrfqa4ZdpENR6U9J76GMS7fZID5a/oQyvMePiFgKfXSbrDFNsm0guM1uND8OJ5rQ=='
]));

var_dump($campaignMonitorService->authenticate());

var_dump($campaignMonitorService->getAccount());

$folders = $campaignMonitorService->getFolders();

var_dump($folders);

$groupData = new \BrizyForms\Model\GroupData([
    'name' => 'List'.rand(1,1000),
    'folder' => $folders[0]->getId()
]);

$group = $campaignMonitorService->createGroup($groupData);

var_dump($group);

$groups = $campaignMonitorService->getGroups($folders[0]);

var_dump($groups);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group;
    break;
}

$fields = $campaignMonitorService->getFields($active_group);

var_dump($fields);

$campaignMonitorService->createMember($fieldMap, $active_group->getId(), $dataArray);