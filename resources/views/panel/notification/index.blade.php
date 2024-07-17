@extends('templates.panel.app')

@section('title', __('notification.title'))
@section('class', 'notification list')

@section('content')
    @include('templates.backend.parts.notifications')
@endsection()
