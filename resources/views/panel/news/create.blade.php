@extends('templates.panel.app')

@section('title', __('panel/news.header_create'))
@section('class', 'news edit')

@section('content')

    <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.news.store')}}">
        @csrf

        <input type="hidden" name="status" :value="status">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            @foreach($languages as $language)
                <li class="nav-item">
                    <a class="nav-link {{$language->default?'active':''}}" id="{{$language->code}}-tab" data-toggle="tab" href="#language_{{$language->code}}" role="tab" aria-controls="language_{{$language->code}}" aria-selected="{{$language->default?'true':'false'}}">{{$language->name}}</a>
                </li>
            @endforeach
        </ul>

        <div class="form-group">
            <label>{{__('panel/news.date')}}</label>
            <date-picker value-type="format" v-model="date" type="date" format="DD.MM.YYYY" required name="date" class="@error('date') is-invalid @enderror">
            </date-picker>

            @error('date')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <hr>

        <div class="tab-content">

            <div class="form-group">
                <label>{{__('panel/news.type')}}</label>
                <select name="is_mobile" class="form-control">
                    <option value="0">{{ __('panel/news.type_simple') }}</option>
                    <option value="1">{{ __('panel/news.type_mobile') }}</option>
                </select>

            </div>

            @foreach($languages as $language)
                <div data-default="{{$language->default?'true':'false'}}" class="tab-pane {{$language->default?'active':''}}" id="language_{{$language->code}}" role="tabpanel" aria-labelledby="{{$language->code}}-tab">

                    <div class="form-group">
                        <label>{{__('panel/news.title')}}</label>
                        <input {{$language->default?'required':''}} v-model="title.{{$language->code}}" value="{{old($language->code.'_title')}}" @change="changeSlug('{{$language->code}}')" name="{{$language->code}}_title" type="text" class="@error($language->code.'_title') is-invalid @enderror form-control">
                        @error($language->code.'_title')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{__('panel/news.slug')}}</label>
                        <input {{$language->default?'required':''}} v-model="slug.{{$language->code}}" value="{{old($language->code.'_slug')}}" name="{{$language->code}}_slug" type="text" class="@error($language->code.'_slug') is-invalid @enderror form-control">
                        @error($language->code.'_slug')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input @change="updateFiles('{{$language->code}}')" ref="{{$language->code}}_newsImage" accept=".png, .jpg, .jpeg, .gif" name="{{$language->code}}_image" type="file" class="d-none" id="{{$language->code}}_customFile">

                        <div v-if="preview.{{$language->code}}" class="preview">
                            <img :src="preview.{{$language->code}}" />
                            <button v-on:click="resetFiles('{{$language->code}}')" class="btn btn-sm btn-danger">
                                <img src="{{asset('images/icons/icon_close.svg')}}">
                            </button>
                        </div>
                        <div v-else class="no-preview">
                            <div class="img">
                                <div class="dummy"></div>
                                <div class="help">
                                    {!! __('app.help_image') !!}
                                </div>
                            </div>
                            <label class="btn btn-outline-primary" for="{{$language->code}}_customFile">+ {{__('app.btn_choose_image')}}</label>
                        </div>

                        @error($language->code.'_image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>{{__('panel/news.preview_text')}}</label>
                        <textarea v-tinymce {{$language->default?'required':''}} name="{{$language->code}}_preview_text" type="text" class="@error($language->code.'_preview_text') is-invalid @enderror tinymce__preview-text form-control">{{ old($language->code.'_preview_text')}}</textarea>
                        @error($language->code.'_preview_text')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{__('panel/news.detail_text')}}</label>
                        <textarea v-tinymce {{$language->default?'required':''}} name="{{$language->code}}_detail_text" type="text" class="@error($language->code.'_detail_text') is-invalid @enderror tinymce__detail-text form-control">{{ old($language->code.'_detail_text')}}</textarea>
                        @error($language->code.'_detail_text')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>



                </div><!-- /.tab-pane -->
            @endforeach
        </div><!-- /.tab-content -->


        <div class="form-group form-controls">
            <a href="{{localeRoute('panel.news.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
            <button @click="status=0" type="submit" class="btn btn-outline-primary">{{__('app.btn_save_draft')}}</button>
            <button @click="status=1" type="submit" class="ml-sm-auto btn btn-success">{{__('app.btn_publish')}}</button>
        </div>
    </form>

    @include('templates.backend.parts.tinymce')

    <script>
        var app = new Vue({
            el: '#app',
            data: {
                date: '{{old('date')}}',
                status: 0,
                preview: {
                    @foreach($languages as $language)
                        {{$language->code}}: null,
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
                resetFiles(code = '') {
                    this.preview[code] = null;
                    this.$refs[code + '_newsImage'].value = '';
                },

                changeSlug(code = ''){
                    this.slug[code] = translit(this.title[code]);
                },

                updateFiles(code = '') {
                    let files = this.$refs[code + '_newsImage'].files;
                    if(files.length > 0)
                        this.preview[code] = URL.createObjectURL(files[0]);
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
