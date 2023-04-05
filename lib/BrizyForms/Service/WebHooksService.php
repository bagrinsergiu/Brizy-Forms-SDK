<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use GuzzleHttp\Client;
class WebHooksService
{

    private $urlHooks;
    private $sendType;
    private static $initParamer;

    public function init(array $param)
    {
        if (!array_key_exists('urlHooks', $param) ) return false;

        if (!array_key_exists('sendType', $param))
        {
            $param['sendType'] = 'POST';
        }

        self::$initParamer = $param;
        return true;
    }
    public static function events($message)
    {

        $sends = [
            'url' => self::$initParamer['urlHooks'],
            'sendType' => self::$initParamer['sendType'],
            'message' => $message
        ];

        if (array_key_exists( 'header', self::$initParamer))
        {
            $sends['header'] = self::$initParamer['header'];
        }

        self::curlExec($sends);
    }

    public function getInitParametrs()
    {
        return self::$initParamer;
    }

    private static function curlExec($value)
    {
        $client = new Client();

        $options = array();
        if(array_key_exists('header', $value))
        {
            $options['headers'] = $value['header'] ;
        }

        if(array_key_exists('message', $value)) {
            $options['form_params'] = $value['message'];
        }

        $client->request($value['sendType'], $value['url'], $options);
    }

}