
<div class="user-card left-card">

    <div onclick="window.location.href='{{localeRoute('cabinet.index')}}'" class="top">
        <div class="avatar">
            @if($info['avatar'])
                <div class="img preview" style="background-image: url({{$info['avatar']}});"></div>
            @else
                <div class="img no-preview"></div>
            @endif
        </div>
        <div class="right">
            <div class="name">
                {{$info['fio'] != ""?$info['fio']:$info['phone']}}
            </div>
            <div class="id">
                ID {{$info['id']}}
            </div>
        </div><!-- /.right -->
    </div>

    <div class="bottom">
        <div class="status status-{{$info['status']}}">
            @if($info['status'] == 4)
                <img src="{{asset('images/icons/icon_ok_circle_green.svg')}}">
            @else
                <img src="{{asset('images/icons/icon_danger_circle.svg')}}">
            @endif

            {{__('user.status_'.$info['status'])}}
        </div>
        <div class="phone">{{$info['phone']}}</div>
        <a href="{{localeRoute('cabinet.profile.edit')}}">{{__('cabinet/profile.btn_fill_data')}}</a>
    </div>
</div>
