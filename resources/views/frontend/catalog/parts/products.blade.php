@if(count($products) > 0)
    <div class="row products">
        @foreach($products as $product)
            <div class="col-12 col-sm-6 col-md-4 col-xl-3 item">
                <div class="product">
                   {{-- <div class="top">
                        <div class="badge badge-info">{{__('frontend/catalog.badge_new')}}</div>
                    </div><!-- /.top -->--}}

                    <a class="image blink" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                        @if($product->images->first())
                            <img src="{{$product->images->first()->preview}}" alt="">
                        @else
                            <div class="no-image"></div>
                        @endif
                    </a>

                    <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('app.currency')}}</div>
                    <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                    <div class="credit-from">{{__('frontend/catalog.credit')}} <span>- {{__('frontend/catalog.lbl_3_payments')}}</span></div>
                    <div class="product-controls">
                        <button type="button" class="btn btn-success add-to-cart" data-product="{{$product->id}}">
                            {{__('frontend/catalog.lbl_from')}} {{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->credit_from)}} {{__('frontend/catalog.to_month_short')}}
                        </button>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
@else
    {{__('frontend/search.no_results')}}
@endif
