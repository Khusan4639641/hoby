@if(count($partners) > 0)
    <div class="row">
        @foreach($partners as $item)

            @if($item->status == 1)
                <div class="item-wrapper col-12 col-md-6 col-lg-3">
                    <div class="item">
                        @if($item->logo)
                            <a href="{{localeRoute('partners.show', $item->id)}}">
                                <div class="preview" style="background-image: url({{$item->logo->preview}});"></div>
                            </a>
                        @else
                            <div class="preview dummy"></div>
                        @endif

                        <a href="{{localeRoute('partners.show', $item->id)}}" class="name">
                            {{$item->brand != null?$item->brand:$item->name}}
                        </a>

                    </div>
                    <!-- /.item -->
                </div><!-- /.item-wrapper -->
            @endif

        @endforeach
    </div><!-- /.row -->
@else
    {{__('frontend/partner.txt_no_partners')}}
@endif
