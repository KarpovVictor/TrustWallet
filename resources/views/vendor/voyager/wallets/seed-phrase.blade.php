@extends('voyager::master')

@section('page_title', 'Seed Phrase')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-key"></i> Seed Phrase for Wallet #{{ $wallet->id }}
    </h1>
    <p>User: {{ $user->id }}</p>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="alert alert-danger">
                            <strong>Warning:</strong> This seed phrase provides full access to the wallet. Keep it secure and do not share it.
                        </div>

                        <div class="form-group">
                            <label>Seed Phrase:</label>
                            <div class="well">
                                <code>{{ $seedPhrase }}</code>
                            </div>
                        </div>
                    </div>

                    <div class="panel-footer">
                        <a href="{{ route('voyager.wallets.index') }}" class="btn btn-default">Back to Wallets</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop