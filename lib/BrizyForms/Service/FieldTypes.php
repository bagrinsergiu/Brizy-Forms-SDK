<?php

namespace BrizyForms\Service;

interface FieldTypes
{
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    const TYPE_SELECT = 'select';

    const TYPE_TEXT = 'input';
    const TYPE_TEXTAREA = 'textarea';

    # special type for services that have authorization code flow.
    const TYPE_REDIRECT_URL = 'redirect_url';
}