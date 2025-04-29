<?php

namespace App\Http\Controllers\Voyager;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class SeedPhraseController extends VoyagerBaseController
{
    
    /**
     * Show form for bulk seed phrases import.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bulkForm()
    {
        return Voyager::view('voyager::seed-phrases.bulk-form');
    }
    
    /**
     * Store bulk seed phrases.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkStore(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'phrases' => 'required|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $phrases = explode("\n", $request->phrases);
        $addedCount = 0;
        
        foreach ($phrases as $phrase) {
            $phrase = trim($phrase);
            
            if (empty($phrase)) {
                continue;
            }

            $words = explode(' ', $phrase);
            if (count($words) !== 12) {
                continue;
            }
            
            $exists = \App\Models\SeedPhrase::where('phrase', $phrase)->exists();
            
            if (!$exists) {
                \App\Models\SeedPhrase::create([
                    'phrase' => $phrase,
                    'is_used' => false
                ]);
                
                $addedCount++;
            }
        }
        
        return redirect()->route('voyager.seed-phrases.index')->with([
            'message'    => "{$addedCount} seed phrases added successfully",
            'alert-type' => 'success',
        ]);
    }
}