@extends('voyager::master')

@section('page_title', 'Decrypt Seed Phrase')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-key"></i> Decrypt Seed Phrase for Wallet #{{ $wallet->id }}
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.wallets.show-seed-phrase', $wallet->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> Accessing seed phrases is a sensitive operation and should only be done for legitimate administrative purposes.
                            </div>

                            <div class="form-group">
                                <label for="admin_password">Admin Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                @if($errors->has('admin_password'))
                                    <span class="help-block text-danger">{{ $errors->first('admin_password') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Decrypt</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop