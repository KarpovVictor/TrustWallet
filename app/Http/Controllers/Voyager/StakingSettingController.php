<?php

namespace App\Http\Controllers\Voyager;

use App\Models\Crypto;
use App\Models\StakingSetting;
use App\Models\User;
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

class StakingSettingController extends VoyagerBaseController
{
    /**
     * Browse our Data Type (B)READ.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name.'.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        $isModelTranslatable = is_bread_translatable($model);

        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        $defaultSearchKey = $dataType->default_search_key ?? null;

        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        $users = User::pluck('id', 'id')->toArray();
        $cryptos = Crypto::pluck('name', 'id')->toArray();

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'users',
            'cryptos'
        ));
    }
    
    /**
     * Edit staking settings for specific user and crypto.
     *
     * @param int $userId
     * @param int $cryptoId
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($userId, $cryptoId)
    {
        $user = User::findOrFail($userId);
        $crypto = Crypto::findOrFail($cryptoId);
        
        $stakingSetting = StakingSetting::where('user_id', $userId)
            ->where('crypto_id', $cryptoId)
            ->first();
            
        if (!$stakingSetting) {
            $stakingSetting = new StakingSetting([
                'user_id' => $userId,
                'crypto_id' => $cryptoId,
                'min_stake_amount' => 0.1,
                'apr' => 5.0,
                'lock_time_days' => 7
            ]);
        }
        
        return Voyager::view('voyager::staking-settings.edit', compact('user', 'crypto', 'stakingSetting'));
    }

    /**
     * Update staking settings for specific user and crypto.
     *
     * @param Request $request
     * @param int     $userId
     * @param int     $cryptoId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $userId, $cryptoId)
    {
        $validated = $request->validate([
            'min_stake_amount' => 'required|numeric|min:0',
            'apr' => 'required|numeric|min:0',
            'lock_time_days' => 'required|integer|min:1'
        ]);
        
        try {
            StakingSetting::updateOrCreate(
                [
                    'user_id' => $userId,
                    'crypto_id' => $cryptoId
                ],
                [
                    'min_stake_amount' => $validated['min_stake_amount'],
                    'apr' => $validated['apr'],
                    'lock_time_days' => $validated['lock_time_days']
                ]
            );
            
            return redirect()->route('voyager.users.show', $userId)->with([
                'message'    => "Staking settings updated successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => "Error updating staking settings: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Update staking settings for all users for specific crypto.
     *
     * @param Request $request
     * @param int     $cryptoId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAll(Request $request, $cryptoId)
    {
        $validated = $request->validate([
            'min_stake_amount' => 'required|numeric|min:0',
            'apr' => 'required|numeric|min:0',
            'lock_time_days' => 'required|integer|min:1'
        ]);
        
        try {
            $crypto = Crypto::findOrFail($cryptoId);
            
            StakingSetting::where('crypto_id', $cryptoId)
                ->update([
                    'min_stake_amount' => $validated['min_stake_amount'],
                    'apr' => $validated['apr'],
                    'lock_time_days' => $validated['lock_time_days']
                ]);
            
            return redirect()->route('voyager.cryptos.index')->with([
                'message'    => "Staking settings updated for all users for {$crypto->name}",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => "Error updating staking settings: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }
}