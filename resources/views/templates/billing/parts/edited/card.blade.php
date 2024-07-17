<div class="partner-card left-card">
    {{--    <div class="partner-card-header">--}}
    {{--        --}}
    {{--        <div class="lbl">{{__('billing/profile.lbl_partner')}}</div>--}}
    {{--    </div>--}}

    <div class="partner-card-body">
        <div class="top">
            <div class="avatar">
                @if($info['logo'])
                    <div class="img preview" style="background-image: url({{$info['logo']}});"></div>
                @else
                    <div class="img no-preview"></div>
                @endif
            </div>
            <div class="name">
                <span class="font-size-16">{{$info['company_name']}}</span>
                {{--                <div class="id font-weight-normal">ID {{$info['company_id']}}</div>--}}
            </div>
        </div>

        {{--<div class="description"> {!! $info['company_description']!!}</div>--}}

        {{--        <div class="bottom">--}}
        {{--            <a href="{{localeRoute('billing.profile.index')}}">{{__('billing/profile.btn_edit_data')}}</a>--}}
        {{--        </div>--}}
    </div>
</div>
