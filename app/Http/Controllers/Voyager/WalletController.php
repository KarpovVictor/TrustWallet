<?php

namespace App\Http\Controllers\Voyager;

use App\Models\Crypto;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCrypto;
use App\Services\CryptoService;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class WalletController extends VoyagerBaseController
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

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
            'users'
        ));
    }
    
    /**
     * Show wallet details with cryptos.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('read', app($dataType->model_name));

        $wallet = Wallet::with('cryptos.crypto')->findOrFail($id);
        $user = User::findOrFail($wallet->user_id);

        // Передаем wallet как dataTypeContent для соответствия шаблону Voyager
        $dataTypeContent = $wallet;
        
        // Проверка на SoftDeletes
        $isSoftDeleted = false;
        if (method_exists($wallet, 'trashed') && $wallet->trashed()) {
            $isSoftDeleted = true;
        }

        // Проверка на переводимость модели
        $isModelTranslatable = is_bread_translatable($wallet);

        $this->eagerLoadRelations($wallet, $dataType, 'read', $isModelTranslatable);

        $walletCryptos = WalletCrypto::where('wallet_id', $id)
            ->with('crypto')
            ->get();

        $view = 'voyager::bread.read';

        if (view()->exists("vendor.voyager.wallets.read")) {
            $view = "vendor.voyager.wallets.read";
        }

        return Voyager::view($view, compact(
            'dataType', 
            'dataTypeContent', 
            'wallet', 
            'user', 
            'walletCryptos', 
            'isSoftDeleted',
            'isModelTranslatable'
        ));
    }

    /**
     * Create wallet for user.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createForUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        return Voyager::view('voyager::wallets.create', compact('user'));
    }

    /**
     * Store wallet for user.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeForUser(Request $request, $userId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'seed_phrase' => 'required|string'
        ]);
        
        $user = User::findOrFail($userId);
        
        try {
            DB::beginTransaction();

            if (!$this->cryptoService->validateSeedPhrase($request->seed_phrase)) {
                return redirect()->back()->withErrors(['seed_phrase' => 'Invalid seed phrase'])->withInput();
            }

            $encryptedSeedPhrase = $this->cryptoService->encryptSeedPhrase($request->seed_phrase, $user->password);
            
            $isDefault = $user->wallets()->count() === 0;
            
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'encrypted_seed_phrase' => $encryptedSeedPhrase,
                'is_default' => $isDefault
            ]);
            
            $cryptos = Crypto::where('is_active', true)->get();
            
            foreach ($cryptos as $crypto) {
                WalletCrypto::create([
                    'wallet_id' => $wallet->id,
                    'crypto_id' => $crypto->id,
                    'balance' => 0,
                    'address' => $crypto->address,
                    'private_key' => $this->cryptoService->generatePrivateKey()
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('voyager.users.show', $user->id)->with([
                'message'    => "Wallet created successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with([
                'message'    => "Error creating wallet: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Set default wallet for user.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);
        $user = User::findOrFail($wallet->user_id);
        
        try {
            Wallet::where('user_id', $user->id)->update(['is_default' => false]);
            
            $wallet->update(['is_default' => true]);
            
            return redirect()->route('voyager.users.show', $user->id)->with([
                'message'    => "Default wallet set successfully",
                'alert-type' => 'success',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => "Error setting default wallet: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Decrypt seed phrase.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function decryptSeedPhrase(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);
        
        return Voyager::view('voyager::wallets.decrypt', compact('wallet'));
    }

    /**
     * Show decrypted seed phrase.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showSeedPhrase(Request $request, $id)
    {
        $request->validate([
            'admin_password' => 'required|string'
        ]);
        
        if (!Hash::check($request->admin_password, Auth::user()->password)) {
            return redirect()->back()->withErrors(['admin_password' => 'Invalid admin password'])->withInput();
        }
        
        $wallet = Wallet::findOrFail($id);
        $user = User::findOrFail($wallet->user_id);
        
        try {
            $seedPhrase = $this->cryptoService->decryptSeedPhrase($wallet->encrypted_seed_phrase, $request->admin_password);
            
            return Voyager::view('voyager::wallets.seed-phrase', compact('wallet', 'user', 'seedPhrase'));
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => "Error decrypting seed phrase: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }
    }
}