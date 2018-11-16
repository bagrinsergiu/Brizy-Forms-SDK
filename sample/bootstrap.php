<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;
require __DIR__ . '/config.php';

//Create account
$mailChimpAccount = new \BrizyForms\Account\MailChimpAccount();
$response = $mailChimpAccount->authenticate();

//set data from authenticate
$mailChimp = new \BrizyForms\Model\MailChimp();
$mailChimp->setApiKey('27abb01297b7832e89cde4ef82ca0051');
$mailChimp->setDC('us13');

//create MailChimp service
$mailChimpService = new \BrizyForms\Service\MailChimpService($mailChimp);

$groups = $mailChimpService->getGroups();

foreach ($groups as $group) {
    var_dump($mailChimpService->getFields($group));
}