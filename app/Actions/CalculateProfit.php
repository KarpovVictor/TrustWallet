<?php

namespace App\Actions;

use App\Services\StakingService;
use TCG\Voyager\Actions\AbstractAction;

class CalculateProfit extends AbstractAction
{
    public function getTitle()
    {
        return 'Calculate Daily Profit';
    }

    public function getIcon()
    {
        return 'voyager-dollar';
    }

    public function getPolicy()
    {
        return 'edit';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-warning',
            'data-toggle' => 'modal',
            'data-target' => '#calculate-profit-modal',
        ];
    }

    public function getDefaultRoute()
    {
        return null;
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'stakes';
    }

    public function shouldActionDisplayOnRow($row)
    {
        return false; // Отображаем только как bulk-действие
    }

    public function massAction($ids, $comingFrom)
    {
        $stakingService = app(StakingService::class);
        $updatedCount = $stakingService->calculateDailyProfit();
        
        return redirect($comingFrom)->with([
            'message'    => "{$updatedCount} stakes updated with daily profit.",
            'alert-type' => 'success',
        ]);
    }
}