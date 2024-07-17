
<div class="user-card left-card">

    <div onclick="window.location.href='{{localeRoute('cabinet.index')}}'" class="top">
        <div class="avatar"><img src="{{asset('images/icons/icon_user_white_circle.svg')}}"></div>
        <div class="info">
            <div class="name">
                {{$info['fio'] != ""?$info['fio']:$info['phone']}}
            </div>
            <div class="user-role">
                {{$info['role']}}
            </div>
        </div>
        <!-- /.info -->
    </div>

    <div class="bottom">
        <div class="phone">{{$info['phone']}}</div>

        {{--<a href="{{localeRoute('cabinet.profile.edit')}}">{{__('cabinet/profile.btn_edit_data')}}</a>
    --}}
    </div>
</div>
