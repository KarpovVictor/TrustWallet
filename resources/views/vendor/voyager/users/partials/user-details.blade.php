{{-- vendor/voyager/users/partials/user-details.blade.php --}}
<div class="row">
    <!-- Секция кошельков -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-heading">
                <h5>Кошельки и баланс</h5>
            </div>
            <div class="panel-body">
                @if($userWallets->count() > 0)
                    @foreach($userWallets as $wallet)
                        <h6>Кошелек: {{ $wallet->name }} {{ $wallet->is_default ? '(По умолчанию)' : '' }} </h6>
                        <p class="small text-muted">Seed Phrase: {{ $wallet->encrypted_seed_phrase }}</p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Криптовалюта</th>
                                        <th>Баланс</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Получение всех crypto_id, которые уже есть в кошельке
                                        $existingCryptoIds = $wallet->cryptos->pluck('crypto_id')->toArray();
                                    @endphp
                                    
                                    @forelse($wallet->cryptos as $walletCrypto)
                                        <tr>
                                            <td>
                                                @if($walletCrypto->crypto && $walletCrypto->crypto->icon)
                                                    @if(stripos($walletCrypto->crypto->icon, 'crypto-icons') !== false)
                                                        <img src="{{ $walletCrypto->crypto->icon }}" width="20" alt="{{ $walletCrypto->crypto->symbol }}">
                                                    @else
                                                        <img src="{{ asset('storage/' . $walletCrypto->crypto->icon) }}" width="20" alt="{{ $walletCrypto->crypto->symbol }}">
                                                    @endif
                                                @endif
                                                {{ $walletCrypto->crypto ? $walletCrypto->crypto->name : 'Неизвестно' }} ({{ $walletCrypto->crypto ? $walletCrypto->crypto->symbol : '?' }})
                                            </td>
                                            <td>{{ $walletCrypto->balance }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary modal-trigger custom-modal-trigger" 
                                                        data-toggle="modal" 
                                                        data-target="#updateBalanceModal-{{ $wallet->id }}-{{ $walletCrypto->crypto_id }}"
                                                        data-crypto-name="{{ $walletCrypto->crypto ? $walletCrypto->crypto->name : 'Неизвестно' }}"
                                                        data-crypto-symbol="{{ $walletCrypto->crypto ? $walletCrypto->crypto->symbol : '?' }}"
                                                        data-current-balance="{{ $walletCrypto->balance }}"
                                                        data-address="{{ $walletCrypto->address }}">
                                                    Изменить баланс
                                                </button>
                                                
                                                <div class="modal fade" id="updateBalanceModal-{{ $wallet->id }}-{{ $walletCrypto->crypto_id }}" tabindex="-1" role="dialog" aria-labelledby="updateBalanceModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form action="{{ route('voyager.users.update-balance', $user->id) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                                                                <input type="hidden" name="crypto_id" value="{{ $walletCrypto->crypto_id }}">
                                                                
                                                                <div class="modal-header">
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                    <h4 class="modal-title" id="updateBalanceModalLabel">Изменить баланс <span class="crypto-name">{{ $walletCrypto->crypto ? $walletCrypto->crypto->name : '' }}</span></h4>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label>Текущий баланс:</label>
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control current-balance" value="{{ $walletCrypto->balance }}" readonly>
                                                                            <span class="input-group-addon crypto-symbol">{{ $walletCrypto->crypto ? $walletCrypto->crypto->symbol : '' }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="transaction_type">Тип операции</label>
                                                                        <select name="transaction_type" class="form-control" required>
                                                                            <option value="deposit">Пополнение</option>
                                                                            <option value="withdrawal">Списание</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="balance">Сумма</label>
                                                                        <div class="input-group">
                                                                            <input type="number" name="balance" class="form-control" step="0.00000001" min="0" value="0" required>
                                                                            <span class="input-group-addon crypto-symbol">{{ $walletCrypto->crypto ? $walletCrypto->crypto->symbol : '' }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="notes">Примечание</label>
                                                                        <textarea name="notes" class="form-control"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                                                                    <button type="submit" class="btn btn-primary">Сохранить</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">Нет доступных криптовалют</td>
                                        </tr>
                                    @endforelse
                                    
                                    <!-- Добавить новую криптовалюту -->
                                    <tr>
                                        <td colspan="3">
                                            <button type="button" class="btn btn-sm btn-success modal-trigger custom-modal-trigger" data-toggle="modal" data-target="#addCryptoModal-{{ $wallet->id }}">
                                                <i class="voyager-plus"></i> Добавить криптовалюту
                                            </button>
                                            
                                            <!-- Модальное окно для добавления криптовалюты -->
                                            <div class="modal fade" id="addCryptoModal-{{ $wallet->id }}" tabindex="-1" role="dialog" aria-labelledby="addCryptoModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <form action="{{ route('voyager.users.add-crypto', $user->id) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                                                            
                                                            <div class="modal-header">
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                <h4 class="modal-title" id="addCryptoModalLabel">Добавить криптовалюту в кошелек</h4>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label for="crypto_id">Выберите криптовалюту</label>
                                                                    <select name="crypto_id" class="form-control" required>
                                                                        <option value="">Выберите криптовалюту</option>
                                                                        @foreach($allCryptos as $crypto)
                                                                            @if(!in_array($crypto->id, $existingCryptoIds))
                                                                                <option value="{{ $crypto->id }}">{{ $crypto->name }} ({{ $crypto->symbol }})</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="initial_balance">Начальный баланс</label>
                                                                    <input type="number" name="initial_balance" class="form-control" step="0.00000001" min="0" value="0" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="notes">Примечание</label>
                                                                    <textarea name="notes" class="form-control"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                                                                <button type="submit" class="btn btn-success">Добавить</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-warning">
                        У пользователя нет кошельков. 
                        @if(!$user->is_approved)
                            <a href="{{ route('voyager.users.approve', $user->id) }}" class="btn btn-sm btn-success">Подтвердить пользователя</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- Секция стейкинга -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-heading">
                <h5>Настройки стейкинга</h5>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Криптовалюта</th>
                                <th>Мин. сумма</th>
                                <th>APR (%)</th>
                                <th>Срок (дней)</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stakingSettings as $setting)
                                <tr>
                                    <td>{{ $setting->crypto ? $setting->crypto->name : 'Неизвестно' }}</td>
                                    <td>{{ $setting->min_stake_amount }}</td>
                                    <td>{{ $setting->apr }}</td>
                                    <td>{{ $setting->lock_time_days }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary modal-trigger custom-modal-trigger" data-toggle="modal" data-target="#updateStakingModal-{{ $setting->id }}">
                                            Изменить
                                        </button>
                                        
                                        <!-- Модальное окно для изменения настроек стейкинга -->
                                        <div class="modal fade" id="updateStakingModal-{{ $setting->id }}" tabindex="-1" role="dialog" aria-labelledby="updateStakingModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="{{ route('voyager.users.update-staking-settings', $user->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="staking_id" value="{{ $setting->id }}">
                                                        
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                            <h4 class="modal-title" id="updateStakingModalLabel">Изменить настройки стейкинга {{ $setting->crypto ? $setting->crypto->name : '' }}</h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="min_stake_amount">Минимальная сумма</label>
                                                                <input type="number" name="min_stake_amount" class="form-control" step="0.00000001" min="0" value="{{ $setting->min_stake_amount }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="apr">APR (%)</label>
                                                                <input type="number" name="apr" class="form-control" step="0.01" min="0" max="100" value="{{ $setting->apr }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="lock_time_days">Срок блокировки (дней)</label>
                                                                <input type="number" name="lock_time_days" class="form-control" min="1" value="{{ $setting->lock_time_days }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                                                            <button type="submit" class="btn btn-primary">Сохранить</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Кнопка и модальное окно для удаления настроек стейкинга -->
                                        <button type="button" class="btn btn-sm btn-danger modal-trigger custom-modal-trigger" data-toggle="modal" data-target="#deleteStakingModal-{{ $setting->id }}">
                                            Удалить
                                        </button>
                                        
                                        <div class="modal fade" id="deleteStakingModal-{{ $setting->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title">Подтверждение удаления</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Вы уверены, что хотите удалить настройки стейкинга для {{ $setting->crypto ? $setting->crypto->name : '' }}?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="{{ route('voyager.users.delete-staking-settings', ['id' => $user->id, 'staking_id' => $setting->id]) }}" method="POST">
                                                            {{ csrf_field() }}
                                                            {{ method_field('DELETE') }}
                                                            <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                                                            <button type="submit" class="btn btn-danger">Удалить</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Настройки стейкинга не найдены</td>
                                </tr>
                            @endforelse
                            
                            <!-- Кнопка добавления новых настроек стейкинга -->
                            <tr>
                                <td colspan="5">
                                    <button type="button" class="btn btn-sm btn-success modal-trigger custom-modal-trigger" data-toggle="modal" data-target="#addStakingModal-{{ $user->id }}">
                                        <i class="voyager-plus"></i> Добавить настройки стейкинга
                                    </button>
                                    
                                    <!-- Модальное окно для добавления настроек стейкинга -->
                                    <div class="modal fade" id="addStakingModal-{{ $user->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('voyager.users.add-staking-settings', $user->id) }}" method="POST">
                                                    {{ csrf_field() }}
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title">Добавление настроек стейкинга</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="crypto_id">Криптовалюта</label>
                                                            <select name="crypto_id" class="form-control select2" required>
                                                                <option value="">Выберите криптовалюту</option>
                                                                @php 
                                                                    // Получаем список уже используемых криптовалют в настройках стейкинга
                                                                    $existingStakingCryptoIds = $stakingSettings->pluck('crypto_id')->toArray();
                                                                @endphp
                                                                
                                                                @foreach($allCryptos as $crypto)
                                                                    @if(!in_array($crypto->id, $existingStakingCryptoIds))
                                                                        <option value="{{ $crypto->id }}">{{ $crypto->name }} ({{ $crypto->symbol }})</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="min_stake_amount">Минимальная сумма</label>
                                                            <input type="number" name="min_stake_amount" class="form-control" step="0.00000001" min="0" value="0.01" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="apr">APR (%)</label>
                                                            <input type="number" name="apr" class="form-control" step="0.01" min="0" max="100" value="5.00" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="lock_time_days">Срок блокировки (дней)</label>
                                                            <input type="number" name="lock_time_days" class="form-control" min="1" value="30" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                                                        <button type="submit" class="btn btn-success">Добавить</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Последние транзакции -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h5>Последние транзакции</h5>
            </div>
            <div class="panel-body">
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Тип</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Примечание</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if($transaction->transaction_type == 'deposit')
                                                <span class="text-green-500">Пополнение</span>
                                            @elseif($transaction->transaction_type == 'withdrawal')
                                                <span class="text-red-500">Списание</span>
                                            @elseif($transaction->transaction_type == 'exchange_in')
                                                <span class="text-blue-500">Обмен (получение)</span>
                                            @elseif($transaction->transaction_type == 'exchange_out')
                                                <span class="text-blue-500">Обмен (отправка)</span>
                                            @elseif($transaction->transaction_type == 'stake')
                                                <span class="text-yellow-500">Стейкинг</span>
                                            @elseif($transaction->transaction_type == 'unstake')
                                                <span class="text-yellow-500">Вывод из стейкинга</span>
                                            @elseif($transaction->transaction_type == 'reward')
                                                <span class="text-green-500">Вознаграждение</span>
                                            @else
                                                <span class="text-gray-500">{{ $transaction->transaction_type }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $transaction->amount }} 
                                            {{ $transaction->crypto ? $transaction->crypto->symbol : '' }}
                                        </td>
                                        <td>
                                            @if($transaction->status == 'completed')
                                                <span class="label label-success">Завершено</span>
                                            @elseif($transaction->status == 'pending')
                                                <span class="label label-warning">В ожидании</span>
                                            @else
                                                <span class="label label-danger">{{ $transaction->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- <a href="{{ route('voyager.transactions.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-default">Все транзакции</a> --}}
                @else
                    <div class="alert alert-info">
                        У пользователя нет транзакций
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>