@extends('templates.panel.app')

@section('title', __('panel/catalog.category.edit'))
@section('class', 'catalog category edit')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.catalog.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')
    <div class="catalog" id="catalog">
        <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.catalog.categories.update', $category)}}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="exampleFormControlSelect1">{{__('panel/catalog.category.parent_category')}}</label>
                <select name="parent" required class="form-control category-parent" id="exampleFormControlSelect1" type="text" class="@error('parent') is-invalid @enderror form-control">
                    <option value="0">{{__('panel/catalog.category.no_parent')}}</option>
                    @include('panel.catalog.parts.option_item', ['categories' => $categories, 'selected' => $category->parent_id])
                </select>
                @error('parent')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{__('panel/catalog.category.sort')}}</label>
                <input value="{{old('sort', $category->sort)}}" name="sort" type="text" class="@error('sort') is-invalid @enderror form-control">
                @error('sort')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <input type="hidden" name="files_to_delete" :value="files.delete">

            <div class="form-group">
                <input @change="updateFiles" ref="image" accept=".png, .jpg, .jpeg, .gif" name="image" type="file" class="d-none" id="customFile">

                <div v-if="preview" class="preview">
                    <button v-on:click="resetFiles" class="btn btn-sm btn-danger">
                        <img src="{{asset('images/icons/icon_close.svg')}}">
                    </button>
                    <img :src="preview" />
                </div>
                <div v-else class="no-preview">
                    <label class="btn btn-primary" for="customFile">{{__('panel/catalog.category.image')}}</label>
                    <div class="help">{!! __('app.help_image_short')!!}</div>
                </div>
                @error('image')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <h4>{{__('panel/catalog.field.header')}}</h4>
            <div class="form-group row">
                <div class="col-12 col-sm-6">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">{{__('panel/catalog.field.title')}}</th>
                            <th scope="col">{{__('panel/catalog.field.sort')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($allFields as $field)
                                @if(ceil($loop->count / 2)+1 == $loop->iteration)
                                            </tbody>
                                    </table>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th scope="col">{{__('panel/catalog.field.title')}}</th>
                                                <th scope="col">{{__('panel/catalog.field.sort')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                @endif
                                <tr>
                                    <td>
                                        <div class="checkbox with-text">
                                            <input type="checkbox" id="field{{$field->id}}" name="fields[{{$field->id}}]" value="{{$field->id}}" @if(in_array($field->id, old('fields', $categoryFields->pluck('id')->toArray())))checked @endif>
                                            <label for="field{{$field->id}}">{{$field->title}}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input value="{{old('fields_sort', isset($categoryFields[$field->id]) ? $categoryFields[$field->id]->pivot->sort : '')}}" name="fields_sort[{{$field->id}}]" type="text" class="@error('fields_sort') is-invalid @enderror form-control">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('fields')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <ul class="nav nav-tabs my-4" id="myTab" role="tablist">
                @foreach($languages as $language)
                    <li class="nav-item">
                        <a class="nav-link {{$language->default?'active':''}}" id="{{$language->code}}-tab"
                           data-toggle="tab" href="#language_{{$language->code}}" role="tab"
                           aria-controls="language_{{$language->code}}"
                           aria-selected="{{$language->default?'true':'false'}}">{{$language->name}}</a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($languages as $language)
                    <div data-default="{{$language->default?'true':'false'}}"
                         class="tab-pane {{$language->default?'active':''}}" id="language_{{$language->code}}"
                         role="tabpanel" aria-labelledby="{{$language->code}}-tab">
                        <div class="form-group">
                            <label>{{__('panel/catalog.category.title')}}</label>
                            <input data-required="true" required v-model="title.{{$language->code}}" value="{{old($language->code.'_title', $category->locales[$language->code]->title??"")}}" @change="changeSlug('{{$language->code}}')" name="{{$language->code}}_title" type="text" class="@error($language->code.'_title') is-invalid @enderror form-control">
                            @error($language->code.'_title')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/catalog.category.slug')}}</label>
                            <input data-required="true" required v-model="slug.{{$language->code}}" value="{{old($language->code.'_slug', $category->locales[$language->code]->slug??'')}}" name="{{$language->code}}_slug" type="text" class="@error($language->code.'_slug') is-invalid @enderror form-control">
                            @error($language->code.'_slug')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/catalog.category.preview_text')}}</label>
                            <textarea name="{{$language->code}}_preview_text" class="@error($language->code.'_preview_text') is-invalid @enderror form-control">{{old($language->code.'_preview_text', $category->locales[$language->code]->preview_text??'')}}</textarea>
                            @error($language->code.'_preview_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('panel/catalog.category.detail_text')}}</label>
                            <textarea name="{{$language->code}}_detail_text" class="@error($language->code.'_detail_text') is-invalid @enderror form-control">{{old($language->code.'_detail_text', $category->locales[$language->code]->detail_text??'')}}</textarea>
                            @error($language->code.'_detail_text')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>



            <div class="form-controls">
                <a href="{{localeRoute('panel.catalog.categories.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="btn btn-primary ml-lg-auto">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /.news -->

    <script>
        var catalog = new Vue({
            el: '#catalog',
            data: {
                files: {
                    new: null,
                    old: '{{$category->image->id??null}}',
                    delete: null
                },
                preview: '{{$category->image->preview??null}}',
                slug: {
                        @foreach($languages as $language)
                        {{$language->code}}:
                        '{{old($language->code.'_slug', $category->locales[$language->code]->slug??'')}}',
                        @endforeach
                    },
                    title: {
                        @foreach($languages as $language)
                        {{$language->code}}:
                        '{{old($language->code.'_title', $category->locales[$language->code]->title??'')}}',
                        @endforeach
                    },
            },
            methods: {

                changeSlug(code = '') {
                    this.slug[code] = translit(this.title[code]);
                },
                resetFiles() {
                    this.preview = null;
                    this.$refs.image.value = '';
                    this.files.new = null;
                    this.files.delete = this.files.old;
                },
                updateFiles() {
                    this.files.new = this.$refs.image.files;
                    if(this.files.new.length > 0) {
                        this.preview = URL.createObjectURL(this.files.new[0]);
                        this.files.delete = this.files.old;
                    }
                }
            },
            mounted: function () {

                /* Если заполнено хотя бы одно поле языковой вкладки
                 * то все поля этой владки становятся обязательными
                 */
                /*$('.form-control').keyup(function () {
                    markAsRequired($(this));
                })

                function markAsRequired(input) {
                    let parent = input.parents('.tab-pane');

                    if (parent.attr('data-default') !== 'true') {
                        let filled = false;
                        $('.form-control', parent).each(function () {
                            if ($(this).val() !== '' && $(this).attr('data-required') === 'true') filled = true;
                        });
                        if (filled)
                            $('.form-control', parent).prop('required', true);
                        else
                            $('.form-control', parent).prop('required', false);
                    }
                }*/
            }
        })
    </script>
@endsection
