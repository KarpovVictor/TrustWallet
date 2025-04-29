<?php

namespace App\Http\Controllers\Voyager;

use App\Models\Crypto;
use App\Models\StakingSetting;
use App\Models\Wallet;
use App\Models\WalletCrypto;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CryptoService;
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
use TCG\Voyager\Http\Controllers\VoyagerUserController as BaseVoyagerUserController;

class UserController extends BaseVoyagerUserController
{
    // Наследуем базовую функциональность VoyagerUserController
    
    /**
     * Переопределяем метод index для отображения пользователей на домашней странице
     */
    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
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

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name.'.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
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

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
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

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Загрузим список криптовалют для формы
        $cryptos = Crypto::where('is_active', true)->get();

        $view = 'voyager::bread.browse';

        if (view()->exists("vendor.voyager.users.browse")) {
            $view = "vendor.voyager.users.browse";
        }

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'cryptos'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        // Загрузим данные о кошельках пользователя и криптовалютах
        $wallets = Wallet::where('user_id', $id)->with('cryptos.crypto')->get();
        $cryptos = Crypto::where('is_active', true)->get();
        $transactions = Transaction::where('user_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::users.edit-add")) {
            $view = "voyager::users.edit-add";
        }

        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'wallets',
            'cryptos',
            'transactions'
        ));
    }

    /**
     * Approve user.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            $user->update(['is_approved' => true]);

            $defaultWallet = $user->wallets()->where('is_default', true)->first();
            
            if (!$defaultWallet) {
                throw new Exception('Default wallet not found');
            }

            $cryptos = Crypto::where('is_active', true)->get();
            $cryptoService = app(CryptoService::class);
            
            foreach ($cryptos as $crypto) {
                WalletCrypto::create([
                    'wallet_id' => $defaultWallet->id,
                    'crypto_id' => $crypto->id,
                    'balance' => 0,
                    'address' => $crypto->address,
                    'private_key' => $cryptoService->generatePrivateKey()
                ]);

                StakingSetting::create([
                    'user_id' => $user->id,
                    'crypto_id' => $crypto->id,
                    'min_stake_amount' => 0.1,
                    'apr' => 5.0,
                    'lock_time_days' => 7
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('voyager.users.index')->with([
                'message'    => "User {$user->id} has been approved",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error approving user: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }
    
    /**
     * Update balance.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBalance(Request $request, $userId)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'crypto_id' => 'required|exists:cryptos,id',
            'balance' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:deposit,withdrawal',
            'notes' => 'nullable|string',
            'address' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $walletCrypto = WalletCrypto::where('wallet_id', $request->wallet_id)
                ->where('crypto_id', $request->crypto_id)
                ->firstOrFail();
            
            $oldBalance = $walletCrypto->balance;
            
            if ($request->transaction_type == 'deposit') {
                $walletCrypto->balance += $request->balance;
                $amount = $request->balance;
            } else {
                if ($walletCrypto->balance < $request->balance) {
                    throw new Exception('Insufficient balance');
                }
                $walletCrypto->balance -= $request->balance;
                $amount = -1 * $request->balance;
            }

            $walletCrypto->address = $request->address ?? $walletCrypto->address;
            
            $walletCrypto->save();
            
            // Создаем транзакцию
            Transaction::create([
                'user_id' => $userId,
                'crypto_id' => $request->crypto_id,
                'transaction_type' => $request->transaction_type,
                'amount' => abs($amount),
                'status' => 'completed',
                'notes' => $request->notes ?? 'Admin balance update',
                'details' => json_encode([
                    'old_balance' => $oldBalance,
                    'new_balance' => $walletCrypto->balance,
                    'updated_by' => Auth::id(),
                ]),
            ]);
            
            DB::commit();
            
            return redirect()->back()->with([
                'message'    => "Balance updated successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error updating balance: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Add crypto to wallet.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addCrypto(Request $request, $userId)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'crypto_id' => 'required|exists:cryptos,id',
            'initial_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'address' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Проверяем, существует ли уже такая криптовалюта в кошельке
            $exists = WalletCrypto::where('wallet_id', $request->wallet_id)
                ->where('crypto_id', $request->crypto_id)
                ->exists();
                
            if ($exists) {
                throw new Exception('This cryptocurrency already exists in the wallet');
            }
            
            $crypto = Crypto::findOrFail($request->crypto_id);
            $cryptoService = app(CryptoService::class);
            
            // Создаем запись о криптовалюте в кошельке
            $walletCrypto = WalletCrypto::create([
                'wallet_id' => $request->wallet_id,
                'crypto_id' => $request->crypto_id,
                'balance' => $request->initial_balance,
                'address' => $request->address,
                'private_key' => $cryptoService->generatePrivateKey()
            ]);
            
            // Если начальный баланс больше 0, создаем транзакцию
            if ($request->initial_balance > 0) {
                Transaction::create([
                    'user_id' => $userId,
                    'crypto_id' => $request->crypto_id,
                    'transaction_type' => 'deposit',
                    'amount' => $request->initial_balance,
                    'status' => 'completed',
                    'notes' => $request->notes ?? 'Initial balance',
                    'details' => json_encode([
                        'old_balance' => 0,
                        'new_balance' => $request->initial_balance,
                        'updated_by' => Auth::id(),
                    ]),
                ]);
            }
            
            // Проверяем, есть ли настройки стейкинга для этой криптовалюты
            $stakingExists = StakingSetting::where('user_id', $userId)
                ->where('crypto_id', $request->crypto_id)
                ->exists();
                
            // Если настроек стейкинга нет, создаем их с дефолтными значениями
            if (!$stakingExists) {
                StakingSetting::create([
                    'user_id' => $userId,
                    'crypto_id' => $request->crypto_id,
                    'min_stake_amount' => 0.1,
                    'apr' => 5.0,
                    'lock_time_days' => 7
                ]);
            }
            
            DB::commit();
            
            return redirect()->back()->with([
                'message'    => "Cryptocurrency added successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error adding cryptocurrency: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Добавление новых настроек стейкинга для пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id ID пользователя
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addStakingSettings(Request $request, $id)
    {
        $this->validate($request, [
            'crypto_id' => 'required|exists:cryptos,id',
            'min_stake_amount' => 'required|numeric|min:0',
            'apr' => 'required|numeric|min:0|max:100',
            'lock_time_days' => 'required|integer|min:1',
        ]);
        
        $user = User::findOrFail($id);
        
        // Проверка на существование настроек стейкинга для данной криптовалюты
        $existingSetting = StakingSetting::where('user_id', $id)
            ->where('crypto_id', $request->crypto_id)
            ->first();
        
        if ($existingSetting) {
            return back()->with([
                'message'    => 'Настройки стейкинга для этой криптовалюты уже существуют',
                'alert-type' => 'error',
            ]);
        }
        
        // Создание новых настроек стейкинга
        $stakingSetting = new StakingSetting();
        $stakingSetting->user_id = $id;
        $stakingSetting->crypto_id = $request->crypto_id;
        $stakingSetting->min_stake_amount = $request->min_stake_amount;
        $stakingSetting->apr = $request->apr;
        $stakingSetting->lock_time_days = $request->lock_time_days;
        $stakingSetting->save();
        
        return back()->with([
            'message'    => 'Настройки стейкинга успешно добавлены',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Обновление настроек стейкинга пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id ID пользователя
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStakingSettings(Request $request, $id)
    {
        $this->validate($request, [
            'staking_id' => 'required|exists:staking_settings,id',
            'min_stake_amount' => 'required|numeric|min:0',
            'apr' => 'required|numeric|min:0|max:100',
            'lock_time_days' => 'required|integer|min:1',
        ]);
        
        // Проверка принадлежности настроек стейкинга пользователю
        $stakingSetting = StakingSetting::where('id', $request->staking_id)
            ->where('user_id', $id)
            ->firstOrFail();
        
        // Обновление настроек стейкинга
        $stakingSetting->min_stake_amount = $request->min_stake_amount;
        $stakingSetting->apr = $request->apr;
        $stakingSetting->lock_time_days = $request->lock_time_days;
        $stakingSetting->save();
        
        return back()->with([
            'message'    => 'Настройки стейкинга успешно обновлены',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Удаление настроек стейкинга пользователя
     * 
     * @param int $id ID пользователя
     * @param int $staking_id ID настройки стейкинга
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteStakingSettings($id, $staking_id)
    {
        // Проверка принадлежности настроек стейкинга пользователю
        $stakingSetting = StakingSetting::where('id', $staking_id)
            ->where('user_id', $id)
            ->firstOrFail();
        
        // Удаление настроек стейкинга
        $stakingSetting->delete();
        
        return back()->with([
            'message'    => 'Настройки стейкинга успешно удалены',
            'alert-type' => 'success',
        ]);
    }

    public function getUserDetails($id)
    {
        // Проверка прав доступа (администратор)
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $user = User::findOrFail($id);
        
        // Получаем кошельки пользователя с ограниченным количеством криптовалют
        $userWallets = Wallet::where('user_id', $user->id)
            ->with(['cryptos' => function($query) {
                $query->with('crypto');
                // Ограничиваем количество криптовалют для увеличения производительности
            }])
            ->get();
        
        // Получаем все доступные криптовалюты
        $allCryptos = Crypto::where('is_active', true)->get();
        
        // Получаем настройки стейкинга
        $stakingSettings = StakingSetting::where('user_id', $user->id)
            ->with('crypto')
            ->get();
        
        // Получаем последние транзакции (ограничиваем количество)
        $transactions = Transaction::where('user_id', $user->id)
            ->with('crypto')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Возвращаем частичное представление
        return view('vendor.voyager.users.partials.user-details', compact(
            'user', 
            'userWallets', 
            'allCryptos', 
            'stakingSettings', 
            'transactions'
        ));
    }
}