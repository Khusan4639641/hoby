@extends('templates.panel.app')

@section('title', __('panel/index.title'))

@section('content')




    <p>Привет, {{Auth::user()->name}} {{Auth::user()->surname}}!</p>
@endsection
