<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class AddSeedPhrases extends AbstractAction
{
    public function getTitle()
    {
        return 'Add Seed Phrases';
    }

    public function getIcon()
    {
        return 'voyager-plus';
    }

    public function getPolicy()
    {
        return 'add';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-right',
            'style' => 'margin-right: 5px',
        ];
    }

    public function getDefaultRoute()
    {
        return route('voyager.seed-phrases.bulk-form');
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'seed-phrases';
    }
}