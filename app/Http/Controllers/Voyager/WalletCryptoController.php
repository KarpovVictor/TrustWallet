<?php

namespace App\Http\Controllers\Voyager;

use App\Models\Crypto;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletCrypto;
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

class WalletCryptoController extends VoyagerBaseController
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

        $wallets = Wallet::with('user')->get()->mapWithKeys(function ($wallet) {
            return [$wallet->id => "ID:{$wallet->id} - {$wallet->name} (User ID:{$wallet->user_id})"];
        })->toArray();
        
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
            'wallets',
            'cryptos'
        ));
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('add', app($dataType->model_name));

        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'crypto_id' => 'required|exists:cryptos,id',
            'balance' => 'required|numeric|min:0',
            'address' => 'required|string',
            'private_key' => 'required|string'
        ]);
        
        $exists = WalletCrypto::where('wallet_id', $request->wallet_id)
            ->where('crypto_id', $request->crypto_id)
            ->exists();
            
        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->with([
                    'message'    => 'This wallet already has this cryptocurrency',
                    'alert-type' => 'error',
                ]);
        }

        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        if ($data->balance > 0) {
            $wallet = Wallet::find($data->wallet_id);
            
            Transaction::create([
                'user_id' => $wallet->user_id,
                'crypto_id' => $data->crypto_id,
                'transaction_type' => 'deposit',
                'amount' => $data->balance,
                'status' => 'completed',
                'tx_hash' => 'admin_' . uniqid(),
                'notes' => 'Initial balance from admin'
            ]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    /**
     * POST BRE(A)D - Update data.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('edit', app($dataType->model_name));

        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        
        $walletCrypto = WalletCrypto::findOrFail($id);
        $oldBalance = $walletCrypto->balance;
        $newBalance = $request->balance ?? $oldBalance;
        $difference = $newBalance - $oldBalance;
        
        try {
            DB::beginTransaction();
            
            $data = $this->insertUpdateData($request, $slug, $dataType->editRows, $dataType->findOrFail($id));
            
            if ($difference != 0) {
                $wallet = Wallet::find($data->wallet_id);
                $transactionType = $difference > 0 ? 'deposit' : 'withdrawal';
                
                Transaction::create([
                    'user_id' => $wallet->user_id,
                    'crypto_id' => $data->crypto_id,
                    'transaction_type' => $transactionType,
                    'amount' => abs($difference),
                    'status' => 'completed',
                    'tx_hash' => 'admin_' . uniqid(),
                    'notes' => 'Balance adjusted by admin'
                ]);
            }
            
            DB::commit();
            
            event(new BreadDataUpdated($dataType, $data));

            if (auth()->user()->can('browse', app($dataType->model_name))) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with([
                    'message'    => $e->getMessage(),
                    'alert-type' => 'error',
                ]);
        }
    }

    /**
     * Edit balance for wallet crypto.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editBalance(Request $request, $id)
    {
        $walletCrypto = WalletCrypto::with(['wallet.user', 'crypto'])->findOrFail($id);
        
        return Voyager::view('voyager::wallet-cryptos.edit-balance', compact('walletCrypto'));
    }

    /**
     * Update balance for wallet crypto.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBalance(Request $request, $id)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        
        $walletCrypto = WalletCrypto::with(['wallet.user', 'crypto'])->findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            $oldBalance = $walletCrypto->balance;
            $newBalance = $request->balance;
            $difference = $newBalance - $oldBalance;
            
            $walletCrypto->balance = $newBalance;
            $walletCrypto->save();
            
            if ($difference != 0) {
                $transactionType = $difference > 0 ? 'deposit' : 'withdrawal';
                
                Transaction::create([
                    'user_id' => $walletCrypto->wallet->user_id,
                    'crypto_id' => $walletCrypto->crypto_id,
                    'transaction_type' => $transactionType,
                    'amount' => abs($difference),
                    'status' => 'completed',
                    'tx_hash' => 'admin_' . uniqid(),
                    'notes' => $request->notes ?? 'Balance adjusted by admin'
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('voyager.wallet-cryptos.index')->with([
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
     * Add tokens to multiple wallets.
     *
     * @param Request $request
     * @param int     $cryptoId
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function addTokensForm(Request $request, $cryptoId)
    {
        $crypto = Crypto::findOrFail($cryptoId);
        $wallets = Wallet::with('user')->get();
        
        return Voyager::view('voyager::wallet-cryptos.add-tokens', compact('crypto', 'wallets'));
    }

    /**
     * Add tokens to multiple wallets.
     *
     * @param Request $request
     * @param int     $cryptoId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addTokens(Request $request, $cryptoId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'wallet_ids' => 'required|array',
            'wallet_ids.*' => 'required|exists:wallets,id',
            'notes' => 'nullable|string'
        ]);
        
        $crypto = Crypto::findOrFail($cryptoId);
        
        try {
            DB::beginTransaction();
            
            $count = 0;
            
            foreach ($request->wallet_ids as $walletId) {
                $walletCrypto = WalletCrypto::where('wallet_id', $walletId)
                    ->where('crypto_id', $cryptoId)
                    ->first();
                    
                if (!$walletCrypto) {
                    $wallet = Wallet::findOrFail($walletId);
                    
                    $walletCrypto = WalletCrypto::create([
                        'wallet_id' => $walletId,
                        'crypto_id' => $cryptoId,
                        'balance' => $request->amount,
                        'address' => $crypto->address,
                        'private_key' => 'admin_generated_' . uniqid()
                    ]);
                } else {
                    $walletCrypto->balance += $request->amount;
                    $walletCrypto->save();
                }
                
                Transaction::create([
                    'user_id' => $walletCrypto->wallet->user_id,
                    'crypto_id' => $cryptoId,
                    'transaction_type' => 'deposit',
                    'amount' => $request->amount,
                    'status' => 'completed',
                    'tx_hash' => 'admin_' . uniqid(),
                    'notes' => $request->notes ?? 'Admin bulk token addition'
                ]);
                
                $count++;
            }
            
            DB::commit();
            
            return redirect()->route('voyager.cryptos.index')->with([
                'message'    => "Added {$request->amount} {$crypto->symbol} to {$count} wallets",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error adding tokens: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Reset balance for wallet crypto.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetBalance(Request $request, $id)
    {
        $walletCrypto = WalletCrypto::with(['wallet.user', 'crypto'])->findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            $oldBalance = $walletCrypto->balance;
            
            $walletCrypto->balance = 0;
            $walletCrypto->save();
            
            if ($oldBalance > 0) {
                Transaction::create([
                    'user_id' => $walletCrypto->wallet->user_id,
                    'crypto_id' => $walletCrypto->crypto_id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $oldBalance,
                    'status' => 'completed',
                    'tx_hash' => 'admin_' . uniqid(),
                    'notes' => 'Balance reset by admin'
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('voyager.wallet-cryptos.index')->with([
                'message'    => "Balance reset successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error resetting balance: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Mass action for wallet cryptos.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massAction(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        
        if (empty($ids) || empty($action)) {
            return redirect()->back()->with([
                'message'    => "No items selected or action not specified",
                'alert-type' => 'error',
            ]);
        }
        
        if ($action === 'reset_balance') {
            try {
                $count = 0;
                
                DB::beginTransaction();
                
                foreach ($ids as $id) {
                    $walletCrypto = WalletCrypto::with(['wallet.user'])->find($id);
                    
                    if ($walletCrypto && $walletCrypto->balance > 0) {
                        $oldBalance = $walletCrypto->balance;
                        
                        $walletCrypto->balance = 0;
                        $walletCrypto->save();
                        
                        Transaction::create([
                            'user_id' => $walletCrypto->wallet->user_id,
                            'crypto_id' => $walletCrypto->crypto_id,
                            'transaction_type' => 'withdrawal',
                            'amount' => $oldBalance,
                            'status' => 'completed',
                            'tx_hash' => 'admin_' . uniqid(),
                            'notes' => 'Balance reset by admin (bulk action)'
                        ]);
                        
                        $count++;
                    }
                }
                
                DB::commit();
                
                return redirect()->route('voyager.wallet-cryptos.index')->with([
                    'message'    => "{$count} wallet cryptos had their balances reset",
                    'alert-type' => 'success',
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                
                return redirect()->back()->with([
                    'message'    => "Error resetting balances: {$e->getMessage()}",
                    'alert-type' => 'error',
                ]);
            }
        } elseif ($action === 'add_tokens') {
            $cryptoIds = WalletCrypto::whereIn('id', $ids)
                ->pluck('crypto_id')
                ->unique()
                ->toArray();
                
            if (count($cryptoIds) !== 1) {
                return redirect()->back()->with([
                    'message'    => "Selected wallet cryptos must be for the same cryptocurrency",
                    'alert-type' => 'error',
                ]);
            }
            
            $walletIds = WalletCrypto::whereIn('id', $ids)
                ->pluck('wallet_id')
                ->toArray();
                
            $crypto = Crypto::findOrFail($cryptoIds[0]);
            $wallets = Wallet::whereIn('id', $walletIds)->with('user')->get();
            
            return Voyager::view('voyager::wallet-cryptos.add-tokens', compact('crypto', 'wallets', 'walletIds'));
        }
        
        return redirect()->back()->with([
            'message'    => "Unknown action: {$action}",
            'alert-type' => 'error',
        ]);
    }
}