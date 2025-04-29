@extends('voyager::master')

@section('page_title', 'Create Wallet')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-wallet"></i> Create Wallet for User #{{ $user->id }}
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.wallets.store-for-user', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="name">Wallet Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="seed_phrase">Seed Phrase (12 words)</label>
                                <textarea class="form-control" id="seed_phrase" name="seed_phrase" rows="3" required></textarea>
                                <p class="helper-block">
                                    Enter a valid 12-word seed phrase separated by spaces.
                                </p>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop