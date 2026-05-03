@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-lg')

@section('title')
    <span class="icon play" style="margin-right:6px"></span>
    {{ $device->name }} &mdash; Video en vivo
@endsection

@section('body')
    @if ($error)
        <div class="alert alert-danger">
            <strong>{{ trans('global.error') }}:</strong> {{ $error }}
        </div>
    @elseif ($streamUrl)
        <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; background:#000;">
            <iframe src="{{ $streamUrl }}" allowfullscreen allow="autoplay; fullscreen" frameborder="0"
                style="position:absolute; top:0; left:0; width:100%; height:100%; min-height:480px;"></iframe>
        </div>
    @else
        <div class="alert alert-warning">
            {{ trans('global.no_data') }}
        </div>
    @endif
@endsection

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@endsection
