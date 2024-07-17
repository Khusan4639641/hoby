@extends('templates.billing.app')

@section('title', __('billing/catalog.header_product_edit'))
@section('class', 'catalog edit')

@section('content')
    <div class="catalog" id="catalog">
        <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('billing.catalog.products.update', $product)}}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="files_to_delete" :value="files_to_delete">


{{--            <div class="form-group">--}}
{{--                <label>{{__('billing/catalog.vendor_code')}}</label>--}}
{{--                <input required value="{{old('vendor_code', $product->vendor_code)}}" name="vendor_code" type="text" class="@error('vendor_code') is-invalid @enderror form-control">--}}
{{--                @error('vendor_code')--}}
{{--                <span class="invalid-feedback" role="alert">--}}
{{--                    <strong>{{ $message }}</strong>--}}
{{--                </span>--}}
{{--                @enderror--}}
{{--            </div>--}}
            {{--                <div class="col-12 col-md-4 form-group">--}}
            {{--                    <label>{{__('billing/catalog.quantity')}}</label>--}}
            {{--                    <input required v-mask="'#################'" value="{{old('quantity', $product->quantity)}}" name="quantity" type="text" class="@error('quantity') is-invalid @enderror form-control">--}}
            {{--                    @error('quantity')--}}
            {{--                    <span class="invalid-feedback" role="alert">--}}
            {{--                        <strong>{{ $message }}</strong>--}}
            {{--                    </span>--}}
            {{--                    @enderror--}}
            {{--                </div>--}}

            {{--                <div class="col-12 col-md-4 form-group">--}}
            {{--                    <label>{{__('billing/catalog.weight')}}</label>--}}
            {{--                    <input required v-mask="'#################'" value="{{old('weight', $product->weight)}}" name="weight" type="text" class="@error('weight') is-invalid @enderror form-control">--}}
            {{--                    @error('weight')--}}
            {{--                    <span class="invalid-feedback" role="alert">--}}
            {{--                        <strong>{{ $message }}</strong>--}}
            {{--                    </span>--}}
            {{--                    @enderror--}}
            {{--                </div>--}}

            <div class="form-row">

                <div class="col-12 col-md-6 col-lg-4 form-group">
                    <label for="exampleFormControlSelect1">{{__('billing/catalog.catalog_categories')}}</label>
                    <select name="categories[]" @change="getCategoryFields()" v-model="category" required class="form-control product-categories" id="exampleFormControlSelect1" type="text" class="@error('categories') is-invalid @enderror form-control">
                        @php
                            $productCategories = $product->categories;
                        @endphp
                        @include('billing.catalog.parts.option_item', ['categories' => $categories, 'productCategories' => $productCategories])
                    </select>
                    @error('categories')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6 col-lg-4 form-group">
                    <label>{{__('billing/catalog.price')}}</label>
                    <input required v-mask="'#################'" value="{{old('price_origin', $product->price_origin)}}" name="price_origin" type="text" class="@error('price_origin') is-invalid @enderror form-control">
                    @error('price_origin')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6 col-lg-4 form-group">
                    <label>{{__('billing/catalog.quantity')}}</label>
                    <input
                        required
                        v-mask="'#################'"
                        value="{{old('quantity', $product->quantity)}}"
                        name="quantity"
                        type="text"
                        class="@error('quantity') is-invalid @enderror form-control">
                    @error('quantity')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>


                {{-- <div class="d-none d-lg-block col-md-6 col-lg-4 form-group"></div> --}}

                @foreach($languages as $language)
                    <div class="col-12 col-md-6 col-lg-4 form-group">
                        <label>{{__('billing/catalog.title')}} {{ strtoupper($language->code) }}</label>
                        <input
                            {{ $language->default ? 'required' : '' }}
                            v-model="title.{{ $language->code }}"
                            value="{{ old($language->code.'_title') }}"
                            @change="changeSlug('{{ $language->code }}')"
                            name="{{ $language->code }}_title" type="text"
                            class="@error($language->code.'_title') is-invalid @enderror form-control"
                        >
                        @error($language->code.'_title')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                @endforeach

                <div class="col-12 col-md-6 col-lg-4 form-group">
                    <label>{{__('billing/catalog.sku')}}</label>
                    <input value="{{old('vendor_code',$product->vendor_code)}}" name="vendor_code" type="text"
                           class="@error('vendor_code') is-invalid @enderror form-control">
                    @error('vendor_code')
                    <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                    @enderror
                </div>

                <div class="d-none d-lg-block col-md-6 col-lg-4 form-group"></div>

                {{--<div class="col-12 col-md-6 col-lg-4 form-group">
                    <label>{{__('billing/catalog.price_discount')}}</label>
                    <input value="{{old('price_discount',$product->price_discount)}}" name="price_discount" type="text"
                           class="@error('price_discount') is-invalid @enderror form-control">
                    @error('price_discount')
                    <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                    @enderror
                </div>--}}

            </div><!-- /.form-row -->

{{--            <div class="form-group">--}}
{{--                <label for="exampleFormControlSelect1">{{__('billing/catalog.catalog_categories')}}</label>--}}
{{--                <select name="categories[]" @change="getCategoryFields()" v-model="category" required class="form-control product-categories" id="exampleFormControlSelect1" type="text" class="@error('categories') is-invalid @enderror form-control">--}}
{{--                    @php--}}
{{--                        $productCategories = $product->categories;--}}
{{--                    @endphp--}}
{{--                    @include('billing.catalog.parts.option_item', ['categories' => $categories, 'productCategories' => $productCategories])--}}
{{--                </select>--}}
{{--                @error('categories')--}}
{{--                    <span class="invalid-feedback" role="alert">--}}
{{--                        <strong>{{ $message }}</strong>--}}
{{--                    </span>--}}
{{--                @enderror--}}
{{--            </div>--}}
{{--            <hr>--}}

{{--            <div class="form-row">--}}
{{--                <div class="form-group" v-for="(image, index) in images">--}}
{{--                    <input @change="updateFile(index)"--}}
{{--                           :ref="'productImage_' + index"--}}
{{--                           accept=".png, .jpg, .jpeg, .gif"--}}
{{--                           name="image[]"--}}
{{--                           type="file"--}}
{{--                           class="d-none"--}}
{{--                           :id="'customFile' + index">--}}
{{--                    <div v-if="image.preview" class="preview">--}}
{{--                        <img :src="image.preview" />--}}
{{--                        <button v-on:click="resetFile(index)" class="btn btn-sm btn-danger">--}}
{{--                            <img src="{{asset('images/icons/icon_close.svg')}}">--}}
{{--                        </button>--}}
{{--                    </div>--}}
{{--                    <div v-else class="no-preview">--}}
{{--                        <div class="img">--}}
{{--                            <div class="dummy">--}}
{{--                                <button v-if="index > 0" v-on:click="resetFile(index)" class="btn btn-sm btn-danger">--}}
{{--                                    <img src="{{asset('images/icons/icon_close.svg')}}">--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <label class="btn btn-outline-primary" :for="'customFile' + index">+ {{__('app.btn_choose_image')}}</label>--}}
{{--                    </div>--}}
{{--                </div><!-- /.form-group -->--}}
{{--                <div class="no-preview">--}}
{{--                    <div class="img">--}}
{{--                        <div class="help">--}}
{{--                            {!! __('billing/catalog.help_image') !!}--}}
{{--                            <button type="button" class="btn btn-primary" v-on:click="addFile">{{__('app.btn_add_image')}}</button>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            @error('image')--}}
{{--            <span class="invalid-feedback" role="alert">--}}
{{--                    <strong>{{ $message }}</strong>--}}
{{--                </span>--}}
{{--            @enderror--}}

{{--            <hr>--}}


{{--            <ul class="nav nav-tabs my-4" id="myTab" role="tablist">--}}
{{--                @foreach($languages as $language)--}}
{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link {{$language->default?'active':''}}" id="{{$language->code}}-tab"--}}
{{--                           data-toggle="tab" href="#language_{{$language->code}}" role="tab"--}}
{{--                           aria-controls="language_{{$language->code}}"--}}
{{--                           aria-selected="{{$language->default?'true':'false'}}">{{$language->name}}</a>--}}
{{--                    </li>--}}
{{--                @endforeach--}}
{{--            </ul>--}}

{{--            <div class="tab-content">--}}
{{--                @foreach($languages as $language)--}}
{{--                    <div data-default="{{$language->default?'true':'false'}}"--}}
{{--                         class="tab-pane {{$language->default?'active':''}}" id="language_{{$language->code}}"--}}
{{--                         role="tabpanel" aria-labelledby="{{$language->code}}-tab">--}}
{{--                        <div class="form-group">--}}
{{--                            <label>{{__('billing/catalog.title')}}</label>--}}
{{--                            <input {{$language->default?'required':''}} v-model="title.{{$language->code}}" value="{{old($language->code.'_title', $product->locales[$language->code]->title??"")}}" @change="changeSlug('{{$language->code}}')" name="{{$language->code}}_title" type="text" class="@error($language->code.'_title') is-invalid @enderror form-control">--}}
{{--                            @error($language->code.'_title')--}}
{{--                            <span class="invalid-feedback" role="alert">--}}
{{--                                    <strong>{{ $message }}</strong>--}}
{{--                                </span>--}}
{{--                            @enderror--}}
{{--                        </div>--}}



{{--                        <div class="form-group">--}}
{{--                            <label>{{__('billing/catalog.slug')}}</label>--}}
{{--                            <input {{$language->default?'required':''}} v-model="slug.{{$language->code}}" value="{{old($language->code.'_slug', $product->locales[$language->code]->slug??'')}}" name="{{$language->code}}_slug" type="text" class="@error($language->code.'_slug') is-invalid @enderror form-control">--}}
{{--                            @error($language->code.'_slug')--}}
{{--                            <span class="invalid-feedback" role="alert">--}}
{{--                                    <strong>{{ $message }}</strong>--}}
{{--                                </span>--}}
{{--                            @enderror--}}
{{--                        </div>--}}

{{--                        <div class="form-group">--}}
{{--                            <label>{{__('billing/catalog.preview_text')}}</label>--}}
{{--                            <textarea {{$language->default?'required':''}} name="{{$language->code}}_preview_text" type="text" class="@error($language->code.'_preview_text') is-invalid @enderror form-control">{{old($language->code.'_preview_text', $product->locales[$language->code]->preview_text??'')}}</textarea>--}}
{{--                            @error($language->code.'_preview_text')--}}
{{--                            <span class="invalid-feedback" role="alert">--}}
{{--                                    <strong>{{ $message }}</strong>--}}
{{--                                </span>--}}
{{--                            @enderror--}}
{{--                        </div>--}}

{{--                        <div class="form-group">--}}
{{--                            <label>{{__('billing/catalog.detail_text')}}</label>--}}
{{--                            <textarea {{$language->default?'required':''}} name="{{$language->code}}_detail_text" type="text" class="@error($language->code.'_detail_text') is-invalid @enderror form-control">{{old($language->code.'_detail_text', $product->locales[$language->code]->detail_text??'')}}</textarea>--}}
{{--                            @error($language->code.'_detail_text')--}}
{{--                            <span class="invalid-feedback" role="alert">--}}
{{--                                    <strong>{{ $message }}</strong>--}}
{{--                                </span>--}}
{{--                            @enderror--}}
{{--                        </div>--}}

{{--                        <template v-if="category_fields.length > 0">--}}
{{--                            <h4>{{__('panel/catalog.product.fields')}}</h4>--}}
{{--                            <div class="product-fields row">--}}
{{--                                <div class="col-12 col-md-6 col-lg-4 field-item" v-for="item of category_fields">--}}
{{--                                    <div class="form-group">--}}
{{--                                        <label :for="'{{$language->code}}_field'+item.id">@{{item.title}}</label>--}}
{{--                                        <input :id="'{{$language->code}}_field'+item.id" :value="getField(item.id, '{{$language->code}}')" :name="'{{$language->code}}_fields['+item.id+']'" type="text" class="@error($language->code . '_fields') is-invalid @enderror nullable form-control">--}}
{{--                                        @error($language->code.'_fields')--}}
{{--                                        <span class="invalid-feedback" role="alert">--}}
{{--                                            <strong>{{ $message }}</strong>--}}
{{--                                        </span>--}}
{{--                                        @enderror--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </template>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}



            <div class="form-controls">
                <a href="{{localeRoute('billing.catalog.index')}}"
                   class="btn btn-peach text-orange">
                    {{__('app.btn_cancel')}}
                </a>
                <button type="submit"
                        class="btn btn-orange">
                    {{__('app.btn_save')}}
                </button>
            </div>
        </form>
    </div><!-- /.news -->

    <script>
        var catalog = new Vue({
            el: '#catalog',
            data: {
                api_token: '{{Auth::user()->api_token}}',
                images: [],
                delete: [],
                files_to_delete: [],
                category: {{$product->categories?$product->categories->first()->id : array_keys($categories)[0]}},
                category_fields: {},
                product_fields: @if($product->escapedFields) JSON.parse('{!! $product->escapedFields !!}')@else {} @endif,
                vendor_code: null,
                slug: {
                        @foreach($languages as $language)
                        {{$language->code}}:
                        '{{old($language->code.'_slug', $product->locales[$language->code]->slug??'')}}',
                        @endforeach
                    },
                title: {
                    @foreach($languages as $language)
                    {{$language->code}}:
                    '{{old($language->code.'_title', $product->locales[$language->code]->title??'')}}',
                    @endforeach
                },
            },
            methods: {
                resetFile(ind = '') {
                    this.images[ind].file = null;
                    this.$refs['productImage_'+ind][0].value = '';
                    this.images[ind].preview = null;

                    if(this.images[ind].id){
                        this.delete[ind] = this.images[ind].id;
                        this.setFilesToDelete();
                    }

                    if(this.images.length > 1)
                        this.images.splice(ind, 1);
                },

                changeSlug(code = '') {
                    this.slug[code] = translit(this.title[code]);
                },

                updateFile(ind = '') {

                    //console.log(this.$refs['productImage_'+ind][0].files);
                    this.images[ind].file = this.$refs['productImage_'+ind][0].files;
                    this.images[ind].preview = URL.createObjectURL(this.images[ind].file[0]);

                },
                addFile(){
                    this.images.push({
                        file: null,
                        id: null,
                        preview: null
                    });
                },
                removeFile(ind = '') {

                    if(this.images[ind].id){
                        this.delete[ind] = this.images[ind].id;
                        this.setFilesToDelete();
                    }

                    this.images.splice(ind, 1);
                },

                setFilesToDelete: function (){
                    this.files_to_delete = [];
                    this.delete.forEach(item => {

                        this.files_to_delete.push(item);
                        //console.log(this.files_to_delete);
                    });
                },
                getCategoryFields: function (){
                    axios.post('/api/v1/catalog/categories/fields/'+ this.category,
                        {
                            api_token: this.api_token
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.category_fields = response.data.data;
                        }
                    })
                },
                getField: function (id, locale = 'ru'){
                    let res = '';
                    if(this.product_fields){
                        let fieldObj = this.product_fields[id];
                        if(fieldObj){
                            if(fieldObj[locale]){
                                res = fieldObj[locale]['value'];
                            }
                        }
                    }


                    return res;
                }
            },
            mounted: function () {

                this.getCategoryFields();

                /* Если заполнено хотя бы одно поле языковой вкладки
                 * то все поля этой владки становятся обязательными
                 */
                $('.form-control').keyup(function () {
                    markAsRequired($(this));
                })

                function markAsRequired(input) {
                    let parent = input.parents('.tab-pane');

                    if (parent.attr('data-default') !== 'true') {
                        let filled = false;
                        $('.form-control', parent).each(function () {
                            if ($(this).val() !== '') filled = true;
                        })
                        if (filled)
                            $('.form-control:not(".nullable")', parent).attr('required', 'required');
                        else
                            $('.form-control', parent).removeAttr('required');
                    }
                }

                @forelse($product->images as $image)
                    this.images.push({
                        file: null,
                        id: '{{$image->id}}',
                        preview: '{!! $image->preview?? null !!}'
                    });
                @empty
                    this.images.push({
                        file: null,
                        id: null,
                        preview: null
                    });
                @endforelse

            }
        })
    </script>
@endsection
