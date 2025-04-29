@extends('voyager::master')

@section('page_title', 'Add Seed Phrases')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-key"></i> Add Multiple Seed Phrases
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form" class="form-edit-add" action="{{ route('voyager.seed-phrases.bulk-store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="phrases">Seed Phrases (one per line, 12 words each)</label>
                                <textarea class="form-control" id="phrases" name="phrases" rows="10" required></textarea>
                                <p class="helper-block">
                                    Enter each 12-word seed phrase on a new line. Invalid phrases will be ignored.
                                </p>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Add Phrases</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop