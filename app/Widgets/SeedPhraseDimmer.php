<?php

namespace App\Widgets;

use App\Models\SeedPhrase;
use Illuminate\Support\Str;
use TCG\Voyager\Widgets\BaseDimmer;

class SeedPhraseDimmer extends BaseDimmer
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
        $count = SeedPhrase::where('is_used', false)->count();
        $string = trans_choice('Available Seed Phrase|Available Seed Phrases', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-key',
            'title'  => "{$count} {$string}",
            'text'   => "You have {$count} unused seed phrases in your database. Click on the button below to manage seed phrases.",
            'button' => [
                'text' => 'Manage seed phrases',
                'link' => route('voyager.seed-phrases.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/04.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return auth()->user()->can('browse', app(SeedPhrase::class));
    }
}