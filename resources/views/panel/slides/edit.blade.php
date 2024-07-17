@extends('templates.panel.app')

@section('title', __('panel/slide.header_create'))
@section('class', 'news edit')

@section('content')

    <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.slides.update', $slide)}}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="files_to_delete" :value="files_to_delete">

        <div class="form-group">
            <label>{{__('panel/slide.language_code')}}</label>
            <select class="form-control @error('role') is-invalid @enderror" name="role">
                <option @if("" == old('language_code', $slide->language_code)) selected @endif value="">{{__('panel/slide.all_languages')}}</option>
                @foreach($languages as $language)
                    <option @if($language->code == old('language_code')) selected @endif value="{{$language->code}}">{{$language->name}}</option>
                @endforeach
            </select>
            @error('language_code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <input @change="updateFiles()" ref="slideImage" accept=".png, .jpg, .jpeg, .gif" name="image" type="file" class="d-none" id="customFile">

            <div v-if="preview" class="preview">
                <img :src="preview" />
                <button v-on:click="resetFiles()" class="btn btn-sm btn-danger">
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
                <label class="btn btn-outline-primary" for="customFile">+ {{__('app.btn_choose_image')}}</label>
            </div>

            @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <hr>

        <div class="form-group">
            <label>{{__('panel/slide.sort')}}</label>
            <input value="{{old('sort', $slide->sort)}}" required name="sort" type="text" class="@error('sort') is-invalid @enderror form-control">
            @error('surname')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('panel/slide.title')}}</label>
            <input value="{{old('title', $slide->title)}}" required name="title" type="text" class="@error('title') is-invalid @enderror form-control">
            @error('title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('panel/slide.link')}}</label>
            <input value="{{old('link', $slide->link)}}" name="link" type="text" class="@error('link') is-invalid @enderror form-control">
            @error('link')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('panel/slide.label')}}</label>
            <input value="{{old('label', $slide->label)}}" name="label" type="text" class="@error('label') is-invalid @enderror form-control">
            @error('label')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('panel/slide.text')}}</label>
            <textarea v-tinymce required name="text" type="text" class="tinymce__detail-text @error('text') is-invalid @enderror form-control">{{old('text', $slide->text )}}</textarea>
            @error('text')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>


        <div class="form-controls">
            <a href="{{localeRoute('panel.slides.index', $slider->id)}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
            <button type="submit" class="btn ml-md-auto btn-primary">{{__('app.btn_save')}}</button>
        </div>
    </form>

    @include('templates.backend.parts.tinymce')

    <script>
        var app = new Vue({
            el: '#app',
            data: {
                files_to_delete: null,
                preview: '{!! $slide->image->preview??null!!}',
                files: {
                    new: null,
                    old:  {!! ($slide->image??'null') !!},
                    delete: []
                },
            },
            methods: {
                resetFiles(code = '') {
                    this.preview = null;
                    this.$refs['slideImage'].value = '';
                    this.files.new = null;

                    this.files.delete.push(this.files.old['id']);
                    this.files_to_delete = this.files.delete.join();
                },

                updateFiles(code = '') {
                    this.files.new = this.$refs['slideImage'].files;

                    if(this.files.new.length > 0) {
                        this.preview = URL.createObjectURL(this.files.new[0]);

                        //ПОмечаем старые файлы для удаления
                        this.files.delete.push(this.files.old['id']);
                        this.files_to_delete = this.files.delete.join();

                    }
                }
            },
        });
    </script>

@endsection
