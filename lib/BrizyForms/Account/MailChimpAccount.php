<?php

namespace BrizyForms\Account;

use BrizyForms\Model\RedirectResponse;

class MailChimpAccount implements AccountInterface
{
    /**
     * @return RedirectResponse
     */
    public function authenticate()
    {
        $login_url = MAILCHIMP_AUTH_URL."?response_type=code&client_id=%s&redirect_uri=%s";

        return new RedirectResponse(301, "RedirectResponse", sprintf(
            $login_url,
            MAILCHIMP_CLIENT_ID,
            MAILCHIMP_REDIRECT_URI
        ));
    }
}