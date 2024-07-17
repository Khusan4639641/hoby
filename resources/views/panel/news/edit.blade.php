@extends('templates.panel.app')

@section('title', __('panel/news.header_edit'))
@section('class', 'news edit')

@section('content')

    <div class="news">

        <form @submit="checkForm" class="edit" method="POST" enctype="multipart/form-data"
              action="{{localeRoute('panel.news.update', $news)}}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="files_to_delete" :value="files_to_delete">
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
                        <a class="nav-link {{$language->default?'active':''}}" id="{{$language->code}}-tab"
                           data-toggle="tab" href="#language_{{$language->code}}" role="tab"
                           aria-controls="language_{{$language->code}}"
                           aria-selected="{{$language->default?'true':'false'}}">{{$language->name}}</a>
                    </li>
                @endforeach
            </ul>

            <div class="form-group">
                <label>{{__('panel/news.date')}}</label>
                <date-picker value-type="format" v-model="date" type="date" format="DD.MM.YYYY" name="date" required
                             class="@error('date') is-invalid @enderror"></date-picker>
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
                        <option
                            value="0" {{ $news->is_mobile == 0 ? 'selected':'' }}>{{ __('panel/news.type_simple') }}</option>
                        <option
                            value="1" {{ $news->is_mobile == 1 ? 'selected':'' }}>{{ __('panel/news.type_mobile') }}</option>
                    </select>

                </div>

                @foreach($languages as $language)

                    <div data-default="{{$language->default?'true':'false'}}"
                         class="tab-pane {{$language->default?'active':''}}" id="language_{{$language->code}}"
                         role="tabpanel" aria-labelledby="{{$language->code}}-tab">

                        <div class="form-group">
                            <label>{{__('panel/news.title')}}</label>
                            <input {{$language->default?'required':''}} v-model="title.{{$language->code}}"
                                   value="{{old($language->code.'_title', $news->locales[$language->code]['title']??"")}}"
                                   @change="changeSlug('{{$language->code}}')" name="{{$language->code}}_title"
                                   type="text"
                                   class="@error($language->code.'_title') is-invalid @enderror form-control">
                            @error($language->code.'_title')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/news.slug')}}</label>
                            <input {{$language->default?'required':''}} v-model="slug.{{$language->code}}"
                                   value="{{old($language->code.'_slug', $news->locales[$language->code]['slug']??"")}}"
                                   name="{{$language->code}}_slug" type="text"
                                   class="@error($language->code.'_slug') is-invalid @enderror form-control">
                            @error($language->code.'_slug')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <input @change="updateFiles('{{$language->code}}')" ref="{{$language->code}}_newsImage"
                                   accept=".png, .jpg, .jpeg, .gif" name="{{$language->code}}_image" type="file"
                                   class="d-none" id="{{$language->code}}_customFile">

                            <div v-if="preview.{{$language->code}}" class="preview">
                                <img :src="preview.{{$language->code}}"/>
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
                                <label class="btn btn-outline-primary"
                                       for="{{$language->code}}_customFile">+ {{__('app.btn_choose_image')}}</label>
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
                            <textarea v-tinymce
                                      {{$language->default?'required':''}} name="{{$language->code}}_preview_text"
                                      type="text"
                                      class="@error($language->code.'_preview_text') is-invalid @enderror tinymce__preview-text form-control">{!! old($language->code.'_preview_text',$news->locales[$language->code]['preview_text']??"")!!}</textarea>
                            @error($language->code.'_preview_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/news.detail_text')}}</label>
                            <textarea v-tinymce
                                      {{$language->default?'required':''}} name="{{$language->code}}_detail_text"
                                      type="text"
                                      class="@error($language->code.'_detail_text') is-invalid @enderror tinymce__detail-text form-control">{!! old($language->code.'_detail_text', $news->locales[$language->code]['detail_text']??"")!!}</textarea>
                            @error($language->code.'_detail_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div><!-- /.tab-pane -->

                @endforeach


            </div><!-- /.tab-content -->

            <div class="form-controls">
                <a href="{{localeRoute('panel.news.index')}}"
                   class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>

                @if($news->status == 1)
                    <button type="submit" class="ml-sm-auto btn btn-success">{{__('app.btn_save')}}</button>
                @else
                    <button @click="status=0" type="submit"
                            class="btn btn-outline-primary">{{__('app.btn_save_draft')}}</button>
                    <button @click="status=1" type="submit"
                            class="ml-sm-auto btn btn-success">{{__('app.btn_publish')}}</button>
                @endif
            </div>
        </form>
    </div><!-- /.news -->

    @include('templates.backend.parts.tinymce')
    @php

        if($news->locales[$language->code]['image']){
                $url = "http://cabinet.test.uz/storage/news-language/" .$news->locales[$language->code]['image']->element_id. "/" . $news->locales[$language->code]['image']->name;

    }else{
                $url = "http://cabinet.test.uz/storage/news-language/" .$news->locales[$language->code]. "/" . $news->locales[$language->code];

    }
    @endphp
    <script>

        var app = new Vue({
            el: '#app',
            data: {
                date: '{{old('date', $news->date)}}',
                status: {{$news->status}},
                files_to_delete: null,
                required: {
                    @foreach($languages as $language)
                        {{$language->code}}: null,
                    @endforeach
                },
                files: {
                    new: {
                        @foreach($languages as $language)
                            {{$language->code}}: null,
                        @endforeach
                    },
                    old: {
                        @foreach($languages as $language)
                            {{$language->code}}: {!! ($news->locales[$language->code]['image']??'null') !!},
                        @endforeach
                    },
                    delete: []
                },
                preview: {
                    @foreach($languages as $language)
                        {{--{{$language->code}}:{!! "'".($news->locales[$language->code]['image']->preview??null)."'"!!},--}}
                        {{$language->code}}: {!! "'".($url)."'"!!},
                    @endforeach
                },
                slug: {
                    @foreach($languages as $language)
                        {{$language->code}}: '{{old($language->code.'_slug', $news->locales[$language->code]['slug']??"")}}',
                    @endforeach
                },
                title: {
                    @foreach($languages as $language)
                        {{$language->code}}: '{{old($language->code.'_title', $news->locales[$language->code]['title']??"")}}',
                    @endforeach
                }
            },
            methods: {
                resetFiles(code = '') {
                    //Обнуляем значения, связанные с фалами
                    this.preview[code] = null;
                    this.$refs[code + '_newsImage'].value = '';
                    this.files.new[code] = null;
                    ;
                    //Помечаем поле обязательным
                    this.required[code] = true;

                    //ПОмечаем старые файлы для удаления
                    this.files.delete.push(this.files.old[code]['id']);
                    this.files_to_delete = this.files.delete.join();
                },

                changeSlug(code = '') {
                    this.slug[code] = translit(this.title[code]);
                },

                updateFiles(code = '') {
                    this.files.new[code] = this.$refs[code + '_newsImage'].files;

                    if (this.files.new[code].length > 0) {
                        this.preview[code] = URL.createObjectURL(this.files.new[code][0]);

                        //ПОмечаем старые файлы для удаления
                        this.files.delete.push(this.files.old[code]['id']);
                        this.files_to_delete = this.files.delete.join();

                    }
                },
                checkForm: function (e) {

                    let error = false,
                        files = this.files;

                    if (files.delete.length > 0)
                        $.each(files.new, function (code, value) {
                            if (value == null && $.inArray(files.old[code], files.delete) > -1) {
                                error = true;
                            }
                        })

                    if (!error)
                        return true;

                    e.preventDefault();
                }
            },
            mounted: function () {

                /* Если заполнено хотя бы одно поле языковой вкладки
                 * то все поля этой владки становятся обязательными
                 */
                $('.form-control').keyup(function () {
                    markAsRequired($(this));
                })

                function markAsRequired(input) {
                    let parent = input.parents('.tab-pane');

                    if (parent.attr('data-default') != 'true') {
                        let filled = false;
                        $('.form-control', parent).each(function () {
                            if ($(this).val() != '') filled = true;
                        })
                        if (filled)
                            $('.form-control', parent).attr('required', 'required');
                        else
                            $('.form-control', parent).removeAttr('required');
                    }
                }
            }
        })
    </script>
@endsection
