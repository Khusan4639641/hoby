@extends('templates.frontend.app')

@section('title', __('frontend/cart.header'))
@section('class', 'cart index')

@section('content')
    <div class="container">
        <div class="cart-products" id="cart">
            <h1>{{__('frontend/cart.header')}}</h1>
            <div class="items" v-if="size(products) > 0">
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="block">
                            <div class="head">
                                <div class="checkbox with-text">
                                    <input type="checkbox" id="selectAll" v-model="allSelected" v-on:click="selectAll()">
                                    <label for="selectAll">{{__('frontend/cart.select_all')}}</label>
                                </div>
                                <a href="javascript:" class="delete" v-on:click="deleteSelected()">{{__('app.btn_delete_selected')}}</a>
                            </div>

                            <div v-if="list.length > 0" class="group" v-for="(list, group) in products">
                                <div class="company">
                                    <div class="top">
                                        <a class="name" href="javascript:" v-on:click="detail( `{{localeRoute('partners.index')}}/${list[0].product.partner.company.id}`)">@{{ list[0].product.partner.company.name }}</a>
                                        <div class="logo" v-if="list[0].product.partner.company.logo != null">
                                            <img :src="'/storage/' + list[0].product.partner.company.logo.path" alt="">
                                        </div>
                                    </div>
                                    <div class="bottom">
                                        <div v-if="list[0].product.partner.company.address != null" class="address">
                                            @{{ list[0].product.partner.company.address }}
                                        </div>
                                        <div v-if="list[0].product.partner.company.working_hours != null" class="working">
                                            @{{ list[0].product.partner.company.working_hours }}
                                        </div>
                                        <div v-else class="working">
                                            {{__('company.working_hours')}}
                                        </div>
                                        <div v-if="list[0].product.partner.company.phone != null" class="phone">
                                            @{{ list[0].product.partner.company.phone }}
                                        </div>
                                    </div>
                                </div>

                                <div  v-for="(item, key) in list" class="item" :data-cart="item.cart_id" :data-product="item.product.id" :data-price="item.product.price">
                                        <div class="checkbox">
                                            <input v-model="productToDelete" :value="{group: group,index: key, id: item.product.id}" type="checkbox" :id="item.cart_id + item.product.id" @change="selectProduct(key)">
                                            <label :for="item.cart_id + item.product.id"></label>
                                        </div>
                                        <div class="image">
                                            <a class="" href="javascript:" v-on:click="detail( routeToJs(`{{localeRoute('catalog.product.show', ['%slug%', '%id%']) }}`, {'slug':item.product.locale.slug, 'id':item.product.id}) )">
                                                <img :src="item.product.preview" alt="">
                                            </a>
                                        </div>
                                        <div class="info">
                                            <div class="title"><a href="javascript:" v-on:click="detail( routeToJs(`{{localeRoute('catalog.product.show', ['%slug%', '%id%']) }}`, {'slug':item.product.locale.slug, 'id':item.product.id}) )">@{{item.product.locale.title}}</a></div>
                                            {{--<div class="credit-min-sum">{{__('frontend/cart.credit_min_sum')}}</div>--}}
                                            {{-- <hr>--}}
                                            <div class="credit-from">{{__('frontend/catalog.credit_from')}} @{{formatPrice(item.product.credit_from)}} {{__('frontend/catalog.to_month')}}</div>

                                            <div class="price">@{{formatPrice(item.product.price)}} {{__('frontend/catalog.sum')}}</div>

                                            <hr>

                                            <div class="buttons">
                                                {{--<a href="javascript:" class="favorite">{{__('app.btn_to_fav')}}</a>--}}
                                                <a href="javascript:" class="delete" v-on:click="deleteProduct(group, key, item.product.id)">{{__('app.btn_delete')}}</a>
                                            </div>
                                        </div>

                                        <div class="quantity">
                                            <input type="text" name="quantity" class="number-input input-qty" readonly pattern="^[0-9]+$" :value="item.quantity">
                                            <div class="wrapper">
                                                <a href="javascript:" @click="changeQuantity(group, key, 'plus')" class="plus change-qty">+</a>
                                                <a href="javascript:" @click="changeQuantity(group, key, 'minus')" class="minus change-qty">-</a>
                                            </div>
                                        </div>

                                    </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="sidebar">
                            <div class="block order">
                                <div class="lead">{{__('frontend/cart.your_order')}}</div>
                                <div class="line">
                                    <div class="label">{{__('frontend/cart.products')}} (@{{ this.countProducts }})</div>
                                    <div class="value">@{{ formatPrice(total) }} {{__('frontend/catalog.sum')}}</div>
                                </div>
                                <hr>
                                <div class="cart-total">
                                    <div class="line">
                                        <div class="label">{{__('frontend/cart.cart_total')}}</div>
                                        <div class="value">@{{ formatPrice(total) }} {{__('frontend/catalog.sum')}}</div>
                                    </div>
                                </div>
                                <div class="subtext">{{__('frontend/cart.text1')}}</div>
                                @role('buyer')
                                    @if($status == 8)
                                        <div class="alert alert-danger mt-3">
                                            {!! __('cabinet/cabinet.txt_you_blocked') !!}
                                        </div>
                                    @else
                                        <a href="{{localeRoute('order.processing', ['type' => 'direct'])}}" class="btn btn-success btn-arrow make-order">{{__('frontend/cart.make_order')}}</a>
                                    @endif
                                @else
                                    <div class="credit-info">
                                        <div class="line ">
                                            <div class="label">
                                                <div class="text">
                                                    <span class="bold">{{__('frontend/cart.zcoin_bonus')}}</span>
                                                    <span class="grey">{{__('frontend/cart.zcoin_text2')}}</span>
                                                </div>
                                            </div>
                                            <div class="value">100&nbsp;zCoin</div>

                                        </div>
                                        <span class="bold">+10 zCoin {{__('frontend/cart.zcoin_every')}}</span>
                                    </div>

                                    <hr>

                                    <div class="alert alert-danger mt-3">{{__('frontend/cart.warning_order')}}</div>
                                    <a class="btn btn-success make-order" data-toggle="modal" data-target="#auth">{{__('app.btn_enter')}}</a>
                                @endrole
                            </div>
                            @role('buyer')
                                <div class="block credit">
                                    <div class="lead">{{__('frontend/cart.make_credit')}}</div>


                                    <div class="credit-info">
                                        <div class="line ">
                                            <div class="label">
                                                <div class="text">
                                                    <span class="bold">{{__('frontend/cart.zcoin_bonus')}}</span>
                                                    <span class="grey">{{__('frontend/cart.zcoin_text2')}}</span>
                                                </div>
                                            </div>
                                            <div class="value">100&nbsp;zCoin</div>

                                        </div>
                                        <span class="bold">+10 zCoin {{__('frontend/cart.zcoin_every')}}</span>
                                    </div>


                                    @if($verified)

                                        @if($plans)
                                            <table class="table">
                                                <tr v-for="(value, key) in plans">
                                                    <td>@{{ key }} <span v-if="key > 3">{{__('app.months')}}</span><span v-if="key == 3">{{__('app.months_1')}}</span></td>
                                                    <td>@{{ formatPrice(Math.ceil(total + total*(value/100))) }} {{__('app.currency')}}</td>
                                                </tr>
                                            </table>
                                        @endif
                                        <hr>
                                        <div class="subtext">{!! __('frontend/cart.zcoin_text1') !!}</div>

                                        <a v-if="total <= limit" href="{{localeRoute('order.processing', ['type' => 'credit'])}}" class="btn btn-success btn-arrow make-order">{{__('frontend/cart.zcoin_make_credit')}}</a>
                                        <div v-if="total > limit" class="alert alert-danger mt-3">
                                            {{__('frontend/cart.err_not_enough_limit')}}
                                        </div>
                                    @else

                                        @if($status == 8)
                                            <div class="alert alert-danger mt-3">
                                                {!! __('cabinet/cabinet.txt_you_blocked') !!}
                                            </div>
                                        @else
                                        <hr>
                                            <div class="subtext">{!! __('frontend/cart.zcoin_text1') !!}</div>
                                            <div class="alert alert-danger mt-2">{{__('frontend/cart.warning_verify')}}</div>
                                            <a href="{{localeRoute('cabinet.profile.verify')}}" class="btn btn-success btn-arrow make-order">{{__('frontend/cart.warning_verify_btn')}}</a>
                                        @endif
                                    @endif



                                </div><!-- /.credit -->
                            @endrole
                        </div>

                    </div>
                </div>
            </div>
            <div v-else>
                <div class="alert alert-info">{{__('frontend/cart.empty')}}</div>
            </div>
        </div>
    </div>
    <script>

        var cart = new Vue({
            el: '#cart',
            data: {
                api_token: '{{Auth::user()->api_token?? null}}',
                _token: '{{ csrf_token() }}',
                products: {!! $cart['products'] ?? '[]'!!},
                cart_id: null,
                loading: false,
                countProducts: 0,
                productToDelete: [],
                allSelected: false,
                total: 0,
                limit: {{$settings['limit']??'null'}},
                @if($plans)
                    plans: {
                        @foreach($plans as $key => $value)
                            {{$key}}: {{$value}},
                        @endforeach
                    }
                @else
                    plans: null,
                @endif
            },
            methods: {
                formatPrice: function(price = null){
                    let separator = ' ';
                    price = price.toString();
                    return price.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g,"\$1"+separator);
                },
                detail: function (link = null){
                    if(link)  window.location.href = link;
                },
                changeQuantity: function (group, key, direction){

                    let quantity = this.products[group][key].quantity;


                    if(direction === 'plus'){
                        quantity++;
                    } else {
                        if(quantity > 1)
                            quantity--;
                        else
                            return false;
                    }

                    this.products[group][key].quantity = quantity;

                    this.updateCart(this.products[group][key].cart_id, this.products[group][key].product.id, this.products[group][key].quantity);
                },
                calcTotal: function (){

                    this.total = 0;
                    this.countProducts = 0;

                    for (const [group, list] of Object.entries(this.products)) {

                        for (let i = 0; i < list.length; i++) {
                            let productSum = list[i].product.price * list[i].quantity;

                            this.total += productSum;
                            this.countProducts ++;
                        }
                    };

                    $('.link-cart span').text(this.countProducts);

                },
                updateCart: function (cartId, productId, quantity){
                    axios.post(`{{localeRoute('cart.update')}}`,
                        {
                            api_token: this.api_token,
                            cart_id: cartId,
                            product_id: productId,
                            quantity: quantity
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {

                    });
                    this.calcTotal();
                },
                deleteProduct: function (group, index, productId){

                    axios.post(`{{localeRoute('cart.delete')}}`,
                        {
                            api_token: this.api_token,
                            cart_id: this.cart_id,
                            product_id: productId
                        }
                    ).then(response => {
                        if (Array.isArray(index)) {
                            for (let i = index.length - 1; i >= 0; i--)
                                this.products[group].splice(index[i], 1);
                        } else {
                            this.products[group].splice(index, 1);
                        }

                        if(this.products[group].length ==0)
                            delete this.products[group];
                        console.log(this.products);

                        this.calcTotal();
                        cart.$forceUpdate();
                    });
                },
                deleteSelected: function (){

                    let sortedProducts = {};

                    $.each(this.productToDelete, function(index, item) {

                        if(sortedProducts[item.group] == undefined)
                            sortedProducts[item.group] = [];

                        sortedProducts[item.group].push(item);

                    });


                    $.each(sortedProducts, function(group, list) {
                        let _arrIndex = [],
                            _arrId = [];

                        $.each(list, function(index, value) {
                            _arrIndex.push(value.index);
                            _arrId.push(value.id);

                        });

                        cart.deleteProduct(group, _arrIndex, _arrId);
                    });




                },
                size: function(obj) {
                    var size = 0,
                        key;
                    for (key in obj) {
                        if (obj.hasOwnProperty(key)) size++;
                    }
                    return size;
                },
                selectProduct: function (){
                    this.allSelected = false;
                    this.productToDelete = sortObject(this.productToDelete, 'index');
                },
                selectAll: function() {
                    this.productToDelete = [];

                    if (!this.allSelected) {
                        for (const [group, list] of Object.entries(this.products)) {
                            for (let i = 0; i < list.length; i++) {
                                let data = {
                                    group: group,
                                    index: i,
                                    id: list[i].product_id
                                };
                                this.productToDelete.push(data);
                            }
                        }
                        this.allSelected = true;
                    }
                },

            },
            created: function () {
                this.calcTotal();
                this.cart_id =  '{{$cart['id']??null}}';
            }

        });

        function sortObject(arr, key) {
            arr.sort((a, b) => a[key] > b[key] ? 1 : -1);
            return arr;
        }

    </script>
@endsection
