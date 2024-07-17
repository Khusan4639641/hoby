@extends('templates.cabinet.app')
@section('title', __('cabinet/profile.header_profile'))
@section('class', 'profile show')


@section('content')

    <div id="app">
        <table class="table">
            <tr>
                <td><div class="caption">{{__('cabinet/profile.fio')}}</div></td>
                <td>
                    <div class="value">
                        @if($buyer->name)
                            {{$buyer->fio}}
                        @else
                            &ndash;
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.phone')}}</div></td>
                <td><div class="value">{{$buyer->phone}}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.home_phone')}}</div></td>
                <td><div class="value">{!!$buyer->personals->home_phone!=""?$buyer->personals->home_phone:'&ndash;'!!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.birthday')}}</div></td>
                <td><div class="value">{!!$buyer->personals->birthday!= ""?$buyer->personals->birthday:'&ndash;' !!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.city_birth')}}</div></td>
                <td><div class="value">{!!$buyer->personals->city_birth!= ""?$buyer->personals->city_birth:'&ndash;'!!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.pinfl')}}</div></td>
                <td><div class="value">{!!$buyer->personals->pinfl!=""?$buyer->personals->pinfl:'&ndash;'!!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.inn')}}</div></td>
                <td><div class="value">{!!$buyer->personals->inn!=""?$buyer->personals->inn:'&ndash;'!!}</div></td>
            </tr>
        </table>

        <hr>

        <div class="lead">{{__('cabinet/profile.addresses')}}</div>
        @if(count($buyer->addresses) > 0)
            <table class="table">
                @foreach($buyer->addresses as $address)
                    <tr>
                        <td><div class="caption">{{__('cabinet/profile.address_'.$address->type)}}</div></td>
                        <td><div class="value">
                            {{$address->string}}
                        </div></td>
                    </tr>
                @endforeach
            </table>
        @else
            <p>&ndash;</p>
        @endif

        <hr>

        <div class="lead">{{__('cabinet/profile.work_place')}}</div>

        <table class="table">
            <tr>
                <td><div class="caption">{{__('cabinet/profile.work_company')}}</div></td>
                <td><div class="value">{!! $buyer->personals->work_company!= ""?$buyer->personals->work_company: '&ndash;' !!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.work_phone')}}</div></td>
                <td><div class="value">{!! $buyer->personals->work_phone!= ""?$buyer->personals->work_phone:'&ndash;' !!}</div></td>
            </tr>
        </table>

        <hr>

        <div class="lead">{{__('cabinet/profile.social_networks')}}</div>

        <table class="table">
            <tr>
                <td><div class="caption">{{__('cabinet/profile.social_vk')}}</div></td>
                <td>
                    @if($buyer->personals->social_vk)
                        <a target="_blank" href="{{$buyer->personals->social_vk}}">{{$buyer->personals->social_vk}}</a>
                    @else
                        &ndash;
                    @endif
                </td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.social_facebook')}}</div></td>
                <td>
                    @if($buyer->personals->social_facebook)
                        <a target="_blank" href="{{$buyer->personals->social_facebook}}">{{{$buyer->personals->social_facebook}}}</a>
                    @else
                        &ndash;
                    @endif
                </td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.social_linkedin')}}</div></td>
                <td>
                    @if($buyer->personals->social_linkedin)
                        <a target="_blank" href="{{$buyer->personals->social_linkedin}}">{{$buyer->personals->social_linkedin}}</a>
                    @else
                        &ndash;
                    @endif
                </td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.social_instagram')}}</div></td>
                <td>
                    @if($buyer->personals->social_instagram)
                        <a target="_blank" href="{{$buyer->personals->social_instagram}}">{{$buyer->personals->social_instagram}}</a>
                    @else
                    &ndash;
                    @endif
                </td>
            </tr>
        </table>

        <hr>

        <div class="lead">{{__('cabinet/profile.passport')}}</div>

        <table class="table">
            <tr>
                <td><div class="caption">{{__('cabinet/profile.passport_number')}}</div></td>
                <td><div class="value">{!!$buyer->personals->passport_number!=""?$buyer->personals->passport_number:'&ndash;'!!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.passport_date_issue')}}</div></td>
                <td><div class="value">{!!$buyer->personals->passport_date_issue!=""?$buyer->personals->passport_date_issue:'&ndash;'!!}</div></td>
            </tr>
            <tr>
                <td><div class="caption">{{__('cabinet/profile.passport_issued_by')}}</div></td>
                <td><div class="value">{!!$buyer->personals->passport_issued_by!=""?$buyer->personals->passport_issued_by:'&ndash;'!!}</div></td>
            </tr>
        </table>

        <hr>

        @if(count($buyer->personals->files) > 0)
            <div class="row mb-4">
            @foreach($buyer->personals->files as $file)
                <div class="col-12 col-sm-6 col-md-4 mb-3 mb-md-0">
                    <div class="preview-file">
                        <div class="value">{{ __('cabinet/profile.' . $file->type)}}</div>
                        <div class="img" style="background-image: url({{'/storage/' . $file->path }})"></div>
                    </div>
                </div>
            @endforeach
            </div>


        @endif

        <div class="form-controls">
            <a class="btn btn-primary ml-lg-auto" href="{{localeRoute('cabinet.profile.edit')}}">{{__('cabinet/profile.btn_edit_data')}}</a>
        </div>
    </div><!-- /#app -->

@endsection
