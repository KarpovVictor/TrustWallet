@extends('voyager::master')

@section('page_title', 'Add Tokens')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-wallet"></i> Add {{ $crypto->name }} ({{ $crypto->symbol }}) Tokens to Multiple Wallets
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.wallet-cryptos.add-tokens', $crypto->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="amount">Amount to Add</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.00000001" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="wallet_ids">Select Wallets</label>
                                <select class="form-control select2" id="wallet_ids" name="wallet_ids[]" multiple required>
                                    @foreach($wallets as $wallet)
                                        <option value="{{ $wallet->id }}" {{ isset($walletIds) && in_array($wallet->id, $walletIds) ? 'selected' : '' }}>
                                            ID:{{ $wallet->id }} - {{ $wallet->name }} (User ID:{{ $wallet->user_id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes (optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Add Tokens</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@stop