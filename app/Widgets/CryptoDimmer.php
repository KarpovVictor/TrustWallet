<?php

namespace App\Widgets;

use App\Models\Crypto;
use Illuminate\Support\Str;
use TCG\Voyager\Widgets\BaseDimmer;

class CryptoDimmer extends BaseDimmer
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
        $count = Crypto::count();
        $string = trans_choice('Cryptocurrency|Cryptocurrencies', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-wallet',
            'title'  => "{$count} {$string}",
            'text'   => "You have {$count} cryptocurrencies in your database. Click on the button below to view all cryptocurrencies.",
            'button' => [
                'text' => 'View all cryptocurrencies',
                'link' => route('voyager.cryptos.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return auth()->user()->can('browse', app(Crypto::class));
    }
}