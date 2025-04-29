<?php

namespace App\Actions;

use App\Models\Crypto;
use App\Models\StakingSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCrypto;
use App\Services\CryptoService;
use TCG\Voyager\Actions\AbstractAction;

class ApproveUser extends AbstractAction
{
    public function getTitle()
    {
        return 'Approve';
    }

    public function getIcon()
    {
        return 'voyager-check';
    }

    public function getPolicy()
    {
        return 'edit';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-success pull-right',
        ];
    }

    public function getDefaultRoute()
    {
        return null;
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'users';
    }

    public function shouldActionDisplayOnRow($row)
    {
        // Показываем кнопку только для неактивированных пользователей
        return !$row->is_approved;
    }

    public function getRoute($key)
    {
        return route('voyager.users.approve', $key);
    }

    /**
     * Get the slug of the BREAD for the action.
     *
     * @return string
     */
    public function getBreadSlug()
    {
        return 'users';
    }
}