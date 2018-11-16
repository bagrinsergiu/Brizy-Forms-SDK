<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\MadMimi;

class MadMimiService extends Service
{
    /**
     * @var MadMimi
     */
    protected $madMimi;

    public function __construct(MadMimi $madMimi)
    {
        $this->madMimi = $madMimi;
    }

    public function getGroups()
    {
        // TODO: Implement getLists() method.
    }

    public function getFields(Group $group)
    {
        // TODO: Implement getFields() method.
    }

    protected function internalCreateMember(FieldMap $fieldMap, $group_id)
    {
        // TODO: Implement getLists() method.
    }

    protected function mapFields(FieldMap $fieldMap, $group_id)
    {
        // TODO: Implement getLists() method.
    }
}