@extends('voyager::master')

@section('page_title', 'Edit Staking Settings')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-dollar"></i> Edit Staking Settings for {{ $user->name ?? 'User #'.$user->id }} - {{ $crypto->name }}
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.staking-settings.update', [$user->id, $crypto->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="min_stake_amount">Minimum Stake Amount</label>
                                <input type="number" class="form-control" id="min_stake_amount" name="min_stake_amount" step="0.00000001" min="0" value="{{ $stakingSetting->min_stake_amount }}" required>
                            </div>

                            <div class="form-group">
                                <label for="apr">APR (%)</label>
                                <input type="number" class="form-control" id="apr" name="apr" step="0.01" min="0" value="{{ $stakingSetting->apr }}" required>
                            </div>

                            <div class="form-group">
                                <label for="lock_time_days">Lock Time (days)</label>
                                <input type="number" class="form-control" id="lock_time_days" name="lock_time_days" min="1" value="{{ $stakingSetting->lock_time_days }}" required>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop