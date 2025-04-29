@extends('voyager::master')

@section('page_title', 'Update Balance')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-wallet"></i> Update Balance for {{ $walletCrypto->crypto->name }} ({{ $walletCrypto->crypto->symbol }})
    </h1>
    <p>User: {{ $walletCrypto->wallet->user->id }} | Wallet: {{ $walletCrypto->wallet->name }}</p>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.wallet-cryptos.update-balance', $walletCrypto->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="balance">Current Balance</label>
                                <p class="form-control-static">{{ $walletCrypto->balance }} {{ $walletCrypto->crypto->symbol }}</p>
                            </div>

                            <div class="form-group">
                                <label for="balance">New Balance</label>
                                <input type="number" class="form-control" id="balance" name="balance" step="0.00000001" min="0" value="{{ $walletCrypto->balance }}" required>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes (optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop