<?php

namespace App\Widgets;

use App\Models\Stake;
use Illuminate\Support\Str;
use TCG\Voyager\Widgets\BaseDimmer;

class StakeDimmer extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = Stake::where('is_active', true)->count();
        $string = trans_choice('Active Stake|Active Stakes', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-dollar',
            'title'  => "{$count} {$string}",
            'text'   => "You have {$count} active stakes in your database. Click on the button below to view all stakes.",
            'button' => [
                'text' => 'View all stakes',
                'link' => route('voyager.stakes.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/03.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return auth()->user()->can('browse', app(Stake::class));
    }
}