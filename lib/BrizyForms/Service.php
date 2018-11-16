<?php

namespace BrizyForms;

class Service
{
    const MAILCHIMP = 'mailchimp';
    const MADMIMI   = 'madmimi';

    /**
     * @return array
     */
    static public function getServices()
    {
        return [
            self::MAILCHIMP,
            self::MADMIMI
        ];
    }

}