@extends('templates.panel.app')

@section('title', __('panel/discount.header_create'))
@section('class', 'discount edit')

@section('content')

    <div class="news">
        <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.discounts.store')}}">
            @csrf

            <input type="hidden" name="status" :value="status">

            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                @foreach($languages as $language)
                    <li class="nav-item">
                        <a class="nav-link {{$language->default?'active':''}}" id="{{$language->code}}-tab" data-toggle="tab" href="#language_{{$language->code}}" role="tab" aria-controls="language_{{$language->code}}" aria-selected="{{$language->default?'true':'false'}}">{{$language->name}}</a>
                    </li>
                @endforeach
            </ul>

            <div class="lead">{{__('panel/discount.dates')}}</div>

            <div class="form-row">

                <div class="form-group col-6 col-md-2">
                    <label>{{__('panel/discount.date_start')}}</label>
                    <input placeholder="20.12.2020" name="date_start" value="{{old('date_start')}}" type="text" required class="@error('date_start') is-invalid @enderror form-control"/>

                    {{--<input value="{{old('date_start')}}" required name="date_start" type="text" class="datepicker @error('date_start') is-invalid @enderror form-control">
                    --}}
                    @error('date_start')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                </div>

                <div class="form-group col-6 col-md-2">
                    <label>{{__('panel/discount.time_start')}}</label>
                    <input value="{{old('time_start')}}" required name="time_start" type="text" class="@error('time_start') is-invalid @enderror form-control">
                    @error('time_start')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="dash">&ndash;</div>

                <div class="form-group col-6 col-md-2">
                    <label>{{__('panel/discount.date_end')}}</label>
                    <input placeholder="20.12.2020" type="text" value="{{old('date_end')}}" required name="date_end" class="@error('date_end') is-invalid @enderror form-control" />

                    {{--<input value="{{old('date_end')}}" required name="date_end" type="text" class="datepicker @error('date_end') is-invalid @enderror form-control">
                    --}}
                    @error('date_end')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group col-6 col-md-2">
                    <label>{{__('panel/discount.time_end')}}</label>
                    <input value="{{old('time_end')}}" required name="time_end" type="text" class="@error('time_end') is-invalid @enderror form-control">
                    @error('time_end')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

            </div><!-- /.form-row -->

            <hr>

            <div class="lead">{{__('panel/discount.amount')}}, {{__('app.currency')}}</div>
            <div class="form-row">
                <div class="col-12 col-md-6 col-lg-3">

                    <label>{{__('panel/discount.discount_3')}}</label>
                    <input value="{{old('discount_3')}}" name="discount_3" type="text" class="@error('discount_3') is-invalid @enderror form-control">
                    @error('discount_3')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label>{{__('panel/discount.discount_6')}}</label>
                    <input value="{{old('discount_6')}}" name="discount_6" type="text" class="@error('discount_6') is-invalid @enderror form-control">
                    @error('discount_6')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label>{{__('panel/discount.discount_9')}}</label>
                    <input value="{{old('discount_9')}}" name="discount_9" type="text" class="@error('discount_9') is-invalid @enderror form-control">
                    @error('discount_9')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label>{{__('panel/discount.discount_12')}}</label>
                    <input value="{{old('discount_12')}}" name="discount_12" type="text" class="@error('discount_12') is-invalid @enderror form-control">
                    @error('discount_12')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div><!-- /.form-row -->

            <hr>

            <div class="tab-content">
                <div class="lead">{{__('panel/discount.description')}}</div>
                @foreach($languages as $language)
                    <div data-default="{{$language->default?'true':'false'}}" class="tab-pane {{$language->default?'active':''}}" id="language_{{$language->code}}" role="tabpanel" aria-labelledby="{{$language->code}}-tab">

                        <div class="form-group">
                            <label>{{__('panel/discount.title')}}</label>
                            <input {{$language->default?'required':''}} v-model="title.{{$language->code}}" value="{{old($language->code.'_title')}}" @change="changeSlug('{{$language->code}}')" name="{{$language->code}}_title" type="text" class="@error($language->code.'_title') is-invalid @enderror form-control">
                            @error($language->code.'_title')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/discount.slug')}}</label>
                            <input {{$language->default?'required':''}} v-model="slug.{{$language->code}}" value="{{old($language->code.'_slug')}}" name="{{$language->code}}_slug" type="text" class="@error($language->code.'_slug') is-invalid @enderror form-control">
                            @error($language->code.'_slug')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/discount.preview_text')}}</label>
                            <textarea v-tinymce {{$language->default?'required':''}} name="{{$language->code}}_preview_text" type="text" class="@error($language->code.'_preview_text') is-invalid @enderror form-control tinymce__preview-text">{{old($language->code.'_preview_text')}}</textarea>
                            @error($language->code.'_preview_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/discount.detail_text')}}</label>
                            <textarea v-tinymce {{$language->default?'required':''}} name="{{$language->code}}_detail_text" type="text" class="@error($language->code.'_detail_text') is-invalid @enderror form-control tinymce__detail-text">{{old($language->code.'_detail_text')}}</textarea>
                            @error($language->code.'_detail_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <hr>
                        <div class="lead">{{__('panel/discount.images')}}</div>
                        <div class="form-row">
                            <div class="form-group col">
                                <label>{{__('panel/discount.image_list')}}</label>
                                <input {{$language->default?'required':''}} @change="updateFiles('{{$language->code}}', 'list')" ref="{{$language->code}}_newsImage_list" accept=".png, .jpg, .jpeg, .gif" name="{{$language->code}}_image_list" type="file" class="d-none" id="{{$language->code}}_list_customFile">

                                <div v-if="preview.{{$language->code}}_list" class="preview">
                                    <div class="img-wrapper">
                                        <img :src="preview.{{$language->code}}_list" />
                                    </div>
                                    <button v-on:click="resetFiles('{{$language->code}}', 'list')" class="btn btn-sm btn-danger">
                                        <img src="{{asset('images/icons/icon_close.svg')}}">
                                    </button>
                                </div>
                                <div v-else class="no-preview">
                                    <div class="img">
                                        <div class="dummy"></div>
                                    </div>
                                    <label class="btn btn-outline-primary" for="{{$language->code}}_list_customFile">+ {{__('app.btn_choose_image')}}</label>
                                </div>

                                @error($language->code.'_image_list')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group col">
                                <label>{{__('panel/discount.image_detail')}}</label>
                                <input {{$language->default?'required':''}} @change="updateFiles('{{$language->code}}', 'detail')" ref="{{$language->code}}_newsImage_detail" accept=".png, .jpg, .jpeg, .gif" name="{{$language->code}}_image_detail" type="file" class="d-none" id="{{$language->code}}_detail_customFile">

                                <div v-if="preview.{{$language->code}}_detail" class="preview">
                                    <div class="img-wrapper">
                                        <img :src="preview.{{$language->code}}_detail" />
                                    </div>
                                    <button v-on:click="resetFiles('{{$language->code}}', 'detail')" class="btn btn-sm btn-danger">
                                        <img src="{{asset('images/icons/icon_close.svg')}}">
                                    </button>
                                </div>
                                <div v-else class="no-preview">
                                    <div class="img">
                                        <div class="dummy"></div>
                                    </div>
                                    <label class="btn btn-outline-primary" for="{{$language->code}}_detail_customFile">+ {{__('app.btn_choose_image')}}</label>
                                </div>

                                @error($language->code.'_image_detail')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="from-group col">
                                <label>&nbsp;</label>
                                <div class="no-preview">
                                    <div class="img">
                                        <div class="help">{!! __('app.help_image')!!}</div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /.form-row -->

                        <hr>

                    </div><!-- /.tab-pane -->
                @endforeach
            </div><!-- /.tab-content -->


            <div class="form-controls">
                <a href="{{localeRoute('panel.discounts.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button @click="status=0" type="submit" class="btn btn-outline-primary">{{__('app.btn_save_draft')}}</button>
                <button @click="status=1" type="submit" class="ml-sm-auto btn btn-success">{{__('app.btn_publish')}}</button>
            </div>
        </form>
    </div><!-- /.news -->


    @include('templates.backend.parts.tinymce')

    <script>
        var app = new Vue({
            el: '#app',
            data: {
                date_start: '{{old('date_start')}}',
                date_end: '{{old('date_end')}}',
                status: 0,
                preview: {
                    @foreach($languages as $language)
                        {{$language->code}}_list: null,
                        {{$language->code}}_detail: null,
                    @endforeach
                },
                slug: {
                    @foreach($languages as $language)
                        {{$language->code}}: '{{old($language->code.'_slug', null)}}',
                    @endforeach
                },
                title: {
                    @foreach($languages as $language)
                        {{$language->code}}: '{{old($language->code.'_title', null)}}',
                    @endforeach
                }
            },
            methods: {
                resetFiles(code = '', type = '') {
                    this.preview[code + '_' + type] = null;
                    this.$refs[code + '_newsImage_' + type].value = '';
                },

                changeSlug(code = ''){
                    this.slug[code] = translit(this.title[code]);
                },

                updateFiles(code = '', type = '') {
                    let files = this.$refs[code + '_newsImage_' + type].files;
                    if(files.length > 0)
                        this.preview[code + '_' + type] = URL.createObjectURL(files[0]);
                }
            },
            mounted: function(){



                /* Если заполнено хотя бы одно поле языковой вкладки
                 * то все поля этой владки становятся обязательными
                 */
                $('.form-control').keyup(function(){
                    markAsRequired($(this));
                })

                function markAsRequired(input){
                    let parent = input.parents('.tab-pane');

                    if(parent.attr('data-default') != 'true') {
                        let filled = false;
                        $('.form-control', parent).each(function(){
                            if($(this).val() != '') filled = true;
                        })
                        if(filled)
                            $('.form-control', parent).attr('required', 'required');
                        else
                            $('.form-control', parent).removeAttr('required');
                    }
                }
            }
        })
    </script>
@endsection
