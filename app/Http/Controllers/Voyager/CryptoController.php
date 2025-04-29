<?php

namespace App\Http\Controllers\Voyager;

use App\Models\StakingSetting;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class CryptoController extends VoyagerBaseController
{
    
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
            'symbol' => 'required|string|max:10|unique:cryptos',
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'network_name' => 'required|string|max:255',
            'address' => 'required|string'
        ]);
        
        // Обрабатываем загрузку QR-кода перед добавлением данных в БД
        $qrCodePath = null;
        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $filename = 'qr-' . $request->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('qr-codes', $filename, 'public');
            $qrCodePath = $filePath;
            
            // Временно удаляем файл из запроса, чтобы insertUpdateData не попытался его обработать
            $request->files->remove('qr_code');
        }
        
        // Обрабатываем загрузку иконки криптовалюты
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = 'icon-' . $request->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('cryptoicons', $filename, 'public');
            $iconPath = $filePath;
            
            // Временно удаляем файл из запроса
            $request->files->remove('icon');
        }
        
        // Обрабатываем загрузку иконки сети
        $networkIconPath = null;
        if ($request->hasFile('network_icon')) {
            $file = $request->file('network_icon');
            $filename = 'network-' . $request->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('network-icons', $filename, 'public');
            $networkIconPath = $filePath;
            
            // Временно удаляем файл из запроса
            $request->files->remove('network_icon');
        }
        
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());
        
        // Обновляем запись с правильными путями к файлам
        $updateData = [];
        if ($qrCodePath) {
            $updateData['qr_code'] = $qrCodePath;
        }
        if ($iconPath) {
            $updateData['icon'] = $iconPath;
        }
        if ($networkIconPath) {
            $updateData['network_icon'] = $networkIconPath;
        }
        
        if (!empty($updateData)) {
            $data->update($updateData);
        }

        $users = User::whereNotNull('id')->get();
        
        foreach ($users as $user) {
            StakingSetting::create([
                'user_id' => $user->id,
                'crypto_id' => $data->id,
                'min_stake_amount' => 0.1,
                'apr' => 5.0,
                'lock_time_days' => 7
            ]);
        }

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    /**
     * PUT BRE(A)D - Update data.
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

        // Валидация
        $request->validate([
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'network_name' => 'required|string|max:255',
            'address' => 'required|string'
        ]);

        // Находим криптовалюту напрямую
        $crypto = \App\Models\Crypto::findOrFail($id);
        
        // Обработка QR-кода
        $qrCodePath = $crypto->qr_code;
        if ($request->hasFile('qr_code')) {
            // Удаляем старый файл, если он существует
            if (!empty($crypto->qr_code)) {
                $this->deleteFileIfExists($crypto->qr_code);
            }
            
            // Сохраняем новый файл
            $file = $request->file('qr_code');
            $filename = 'qr-' . $crypto->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('qr-codes', $filename, 'public');
            $qrCodePath = $filePath;
            
            // Удаляем файл из запроса
            $request->files->remove('qr_code');
        }
        
        // Обработка иконки криптовалюты
        $iconPath = $crypto->icon;
        if ($request->hasFile('icon')) {
            // Удаляем старый файл, если он существует
            if (!empty($crypto->icon)) {
                $this->deleteFileIfExists($crypto->icon);
            }
            
            // Сохраняем новый файл
            $file = $request->file('icon');
            $filename = 'icon-' . $crypto->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('cryptoicons', $filename, 'public');
            $iconPath = $filePath;
            
            // Удаляем файл из запроса
            $request->files->remove('icon');
        }
        
        // Обработка иконки сети
        $networkIconPath = $crypto->network_icon;
        if ($request->hasFile('network_icon')) {
            // Удаляем старый файл, если он существует
            if (!empty($crypto->network_icon)) {
                $this->deleteFileIfExists($crypto->network_icon);
            }
            
            // Сохраняем новый файл
            $file = $request->file('network_icon');
            $filename = 'network-' . $crypto->symbol . '-' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('network-icons', $filename, 'public');
            $networkIconPath = $filePath;
            
            // Удаляем файл из запроса
            $request->files->remove('network_icon');
        }
        
        // Обновляем вручную
        $crypto->update([
            'symbol' => $request->symbol,
            'name' => $request->name,
            'full_name' => $request->full_name,
            'network_name' => $request->network_name,
            'address' => $request->address,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'price' => $request->price ?? $crypto->price,
            'icon' => $iconPath,
            'network_icon' => $networkIconPath,
            'qr_code' => $qrCodePath,
        ]);

        event(new BreadDataUpdated($dataType, $crypto));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    /**
     * DELETE BRE(A)D - Delete data.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('delete', app($dataType->model_name));

        $crypto = \App\Models\Crypto::findOrFail($id);
        
        $hasActiveStakes = \App\Models\Stake::where('crypto_id', $id)
            ->where('is_active', true)
            ->exists();
            
        if ($hasActiveStakes) {
            return redirect()->route("voyager.{$dataType->slug}.index")->with([
                'message'    => "Cannot delete cryptocurrency with active stakes",
                'alert-type' => 'error',
            ]);
        }
        
        $hasBalance = \App\Models\WalletCrypto::where('crypto_id', $id)
            ->where('balance', '>', 0)
            ->exists();
            
        if ($hasBalance) {
            return redirect()->route("voyager.{$dataType->slug}.index")->with([
                'message'    => "Cannot delete cryptocurrency with non-zero balances",
                'alert-type' => 'error',
            ]);
        }

        try {
            DB::beginTransaction();
            
            \App\Models\StakingSetting::where('crypto_id', $id)->delete();
            
            \App\Models\WalletCrypto::where('crypto_id', $id)->delete();
            
            $model = app($dataType->model_name);
            $query = $model->findOrFail($id);
            $this->deleteFileIfExists($query->qr_code);
            $this->deleteFileIfExists($query->icon);
            $this->deleteFileIfExists($query->network_icon);
            $query->delete();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route("voyager.{$dataType->slug}.index")->with([
                'message'    => "Error deleting cryptocurrency: {$e->getMessage()}",
                'alert-type' => 'error',
            ]);
        }

        event(new BreadDataDeleted($dataType, $query));

        return redirect()->route("voyager.{$dataType->slug}.index")->with([
            'message'    => __('voyager::generic.successfully_deleted')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    /**
     * Update staking settings.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStakingSettings(Request $request, $id)
    {
        $validated = $request->validate([
            'apr' => 'required|numeric|min:0',
            'apply_to_all' => 'boolean'
        ]);
        
        $crypto = \App\Models\Crypto::findOrFail($id);
        
        try {
            if ($request->has('apply_to_all')) {
                StakingSetting::where('crypto_id', $crypto->id)
                    ->update(['apr' => $validated['apr']]);
                    
                $message = "Staking settings updated for all users";
            } else {
                StakingSetting::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'crypto_id' => $crypto->id
                    ],
                    [
                        'apr' => $validated['apr']
                    ]
                );
                
                $message = "Staking settings updated for admin only";
            }
            
            return redirect()->route('voyager.cryptos.index')->with([
                'message'    => $message,
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
     * Delete file if exists.
     *
     * @param string $path
     *
     * @return void
     */
    public function deleteFileIfExists($path)
    {
        try {
            if (!empty($path) && !is_null($path)) {
                // Remove leading slash if present
                $path = ltrim($path, '/');
                
                // Check if file exists in public storage
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                } elseif (file_exists(public_path($path))) {
                    // Fallback to direct file system deletion
                    unlink(public_path($path));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to delete file: ' . $e->getMessage());
        }
    }
}