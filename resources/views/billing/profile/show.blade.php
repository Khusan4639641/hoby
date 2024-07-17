@extends('templates.billing.app')

@section('class', 'profile show')

@section('title', __('billing/profile.header_profile'))

@section('title-info')
    <div class="id">{{__('billing/profile.company_id')}} {{$partner->company->id}}</div>
@endsection

@section('content')

    <div class="lead">{{__('billing/profile.company_description')}}</div>
    <div class="caption">{!! $partner->company->description !!}</div>

    <hr>

    <div class="lead">{{__('billing/profile.txt_law_info')}}</div>
    <table class="table">
        <tr>
            <td><div class="caption">{{__('billing/profile.company_name')}}</div></td>
            <td><div class="value">{{$partner->company->name}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_inn')}}</div></td>
            <td><div class="value">{{$partner->company->inn}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_address')}}</div></td>
            <td><div class="value">{{$partner->company->address}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_legal_address')}}</div></td>
            <td><div class="value">{{$partner->company->legal_address}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_bank_name')}}</div></td>
            <td><div class="value">{{$partner->company->bank_name}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_payment_account')}}</div></td>
            <td><div class="value">{{$partner->company->payment_account}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_phone')}}</div></td>
            <td><div class="value">{{$partner->company->phone??'—'}}</div></td>
        </tr>
        <tr>
            <td><div class="caption">{{__('billing/profile.company_website')}}</div></td>
            <td>
                <div class="value">
                @if($partner->company->website)
                    <a target="__blank" href="{{$partner->company->website}}">{{$partner->company->website}}</a>
                @else
                    &mdash;
                @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="lead">{{__('billing/profile.txt_contact_info')}}</div>
    <p class="value">{{$partner->surname}} {{$partner->name}} {{$partner->patronymic}} ({{$partner->phone}})</p>

    <div class="controls">
        <a href="{{localeRoute('billing.profile.edit')}}" class="btn btn-outline-success">
            {{__('billing/profile.btn_edit_data')}}
        </a>
    </div>
@endsection
