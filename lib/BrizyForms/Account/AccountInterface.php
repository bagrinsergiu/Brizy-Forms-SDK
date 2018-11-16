<?php

namespace BrizyForms\Account;

use BrizyForms\Exception\InvalidAccountDataException;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;

interface AccountInterface
{
    /**
     * @throws InvalidAccountDataException
     * @return Response|RedirectResponse
     */
    public function authenticate();
}