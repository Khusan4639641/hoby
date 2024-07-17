@extends('templates.panel.app')

@section('title', __('panel/employee.header_create'))
@section('class', 'employee edit')

@section('content')
    <div class="employee">
        <form class="edit" method="POST" enctype="multipart/form-data" action="{{localeRoute('panel.employees.store')}}">
            @csrf

            <div class="form-group">
                <label>{{__('panel/employee.label_role')}}</label>
                <select class="form-control @error('role') is-invalid @enderror" name="role">
                    @foreach($roles as $role)
                        <option @if($role->name == old('role')) selected @endif value="{{$role->name}}">{{$role->display_name}}</option>
                    @endforeach
                </select>
                @error('role')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-row">
                <div class="col form-group">
                    <label>{{__('panel/employee.label_surname')}}</label>
                    <input value="{{old('surname')}}" required name="surname" type="text" class="@error('surname') is-invalid @enderror form-control">
                    @error('surname')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col form-group">
                    <label>{{__('panel/employee.label_name')}}</label>
                    <input value="{{old('name')}}" required name="name" type="text" class="@error('name') is-invalid @enderror form-control">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col form-group">
                    <label>{{__('panel/employee.label_patronymic')}}</label>
                    <input value="{{old('patronymic')}}" required name="patronymic" type="text" class="@error('patronymic') is-invalid @enderror form-control">
                    @error('patronymic')
                    <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{__('panel/employee.label_phone')}}</label>
                    <input value="{{old('phone')}}" required name="phone" type="text" class="@error('phone') is-invalid @enderror form-control">
                    @error('phone')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>{{ 'Telegram chat_id' }}</label>
                    <input value="{{old('chat_id')}}" name="chat_id" type="text" class="@error('chat_id') is-invalid @enderror form-control">
                    @error('chat_id')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12 col-md-4">
                    <label>
                    <input value="{{old('telegram_status')}}" name="telegram_status" type="checkbox" class="@error('telegram_status') is-invalid @enderror form-control">
                    {{ 'Telegram send message status' }}</label>
                </div>
            </div>

            <div class="form-group">
                <input @change="updateFiles" ref="userAvatar" accept=".png, .jpg, .jpeg, .gif" name="avatar" type="file" class="d-none" id="customFile">

                <div v-if="preview" class="preview">
                    <button v-on:click="resetFiles" class="btn btn-sm btn-danger">x</button>
                    <img :src="preview" />
                </div>
                <div v-else class="no-preview">
                    <div class="img">
                        <div class="dummy"></div>
                        <div class="help">
                            {!! __('app.help_image') !!}
                        </div>
                    </div>
                    <label class="btn btn-outline-primary" for="customFile">+ {{__('app.btn_choose_avatar')}}</label>
                </div>

                @error('avatar')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <hr>

            <div class="form-group">
                <label>{{__('panel/employee.label_password')}}</label>
                <input placeholder="{{__('panel/employee.label_password')}}" value="{{old('password')}}" required name="password" type="password" class="@error('password') is-invalid @enderror form-control">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <input placeholder="{{__('panel/employee.label_password_confirmation')}}" type="password" class="form-control" name="password_confirmation" required>
            </div>

            <div class="form-controls">
                <a href="{{localeRoute('panel.employees.index')}}" class="btn btn-outline-secondary">{{__('app.btn_cancel')}}</a>
                <button type="submit" class="btn ml-md-auto btn-primary">{{__('app.btn_save')}}</button>
            </div>
        </form>
    </div><!-- /.employees -->

    <script>
        var app = new Vue({
            el: '#app',
            data: {
                files: null,
                preview: null
            },
            methods: {
                resetFiles() {
                    this.preview = null;
                    this.$refs.userAvatar.value = '';
                    this.files = null;
                },
                updateFiles() {
                    this.files = this.$refs.userAvatar.files;
                    if(this.files.length > 0)
                        this.preview = URL.createObjectURL(this.files[0]);
                }
            }
        })
    </script>
@endsection
