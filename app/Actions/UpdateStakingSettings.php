<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class UpdateStakingSettings extends AbstractAction
{
    public function getTitle()
    {
        return 'Update Staking Settings';
    }

    public function getIcon()
    {
        return 'voyager-edit';
    }

    public function getPolicy()
    {
        return 'edit';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-info pull-right',
            'style' => 'margin-right: 5px',
        ];
    }

    public function getDefaultRoute()
    {
        return null;
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'staking_settings';
    }

    public function shouldActionDisplayOnRow($row)
    {
        return true;
    }

    public function getRoute($key)
    {
        return route('voyager.staking-settings.edit', $key);
    }
}