<div class="container">
    @if(count($notifications))

        @foreach($notifications as $notification)
            <div class="alert alert-info {{$notification->read_at ? '' : 'alert-warning'}}">
                <div class="date">{{ $notification->data["time"] }}</div>
                <div class="title">{!! $notification->data["title"] ?? '' !!}</div>
                <div class="message">{!! $notification->data["message"] ?? '' !!}</div>
            </div><!-- /.item -->
        @endforeach
        {{$user->unreadNotifications->markAsRead()}}
    @else
        <div class="alert alert-info">
            {{__('notification.list_empty')}}
        </div>
    @endif
</div>
