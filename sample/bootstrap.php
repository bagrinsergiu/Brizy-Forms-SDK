<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;
require __DIR__ . '/config.php';

//use BrizyForms\ServiceFactory;
//use BrizyForms\Service\MailChimpService;
//use BrizyForms\FieldMap;
//use BrizyForms\Model\RedirectResponse;
//
//$serviceFactory = new ServiceFactory();
//
///**
// * @var MailChimpService $mailchimp
// */
//$mailchimp = $serviceFactory->getInstance('mailchimp');
//$fields    = json_decode('[{"source":"name", "target":"FNAME1"}, {"source":"email", "target":"EMAIL"}]', true);
//$fieldMap  = new FieldMap($fields);
//$fields    = $fieldMap->toArray();
//
//$response = $mailchimp->authenticate();
//if ($response instanceof RedirectResponse) {
//
//}
//
//$groups = $mailchimp->getGroups();
//
//foreach ($groups as $group) {
//    var_dump($mailchimp->getFields($group));
//}
//
//var_dump($groups);
//
//$mailchimp->createMember($fieldMap);

//Create account
$mailChimpAccount = new \BrizyForms\Account\MailChimpAccount();
$response = $mailChimpAccount->authenticate();

//set data from authenticate
$mailChimp = new \BrizyForms\Model\MailChimp();
$mailChimp->setApiKey('67392db60505618fb8d0f6f25db03ec4-us11');

//create MailChimp service
$mailChimpService = new \BrizyForms\Service\MailChimpService($mailChimp);

$groups = $mailChimpService->getGroups();

foreach ($groups as $group) {
    var_dump($mailChimpService->getFields($group));
}