@extends('templates.billing.app')

@section('title', __('billing/catalog.product_import'))
@section('class', 'catalog edit')

@section('center-header-control')
    <a href="{{localeRoute('billing.catalog.index')}}" class="btn btn-orange">{{__('app.btn_back')}}</a>
@endsection

{{--@section('center-header-prefix')--}}
{{--    <a class="link-back" href="{{localeRoute('billing.catalog.index')}}"><img--}}
{{--            src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>--}}
{{--@endsection--}}


@section('content')

    <style>
        .selected-filename{
            background: #f1efef;
            border-radius: 5px;
            padding: 8px 10px;
        }
        .products-count{
            background: #f1efef;
            border-radius: 5px;
            padding: 8px 10px;
            margin-top:15px ;
        }
        .error{
            background: #ffd0d0;
            color: #760000;
            border-radius: 5px;
            padding: 8px 10px;
            margin-top:15px ;

        }
    </style>

    <div class="catalog" id="catalog">

        <div class="register_photo">

            <div class="form_input">

                <div class="file_passport">
                    <input
                        type="file"
                        id="fileUpload"
                        accept=".csv, .txt"
                        style="display: none"
                        ref="fileUpload"
                        @change="changeFile($event)"
                    />

                </div>
            </div>

            <div class="form_input btn-bottom">
                <label
                    for="fileUpload"
                    :disabled="loading"
                    class="btn btn-orange"
                >
                    {{ __('billing/catalog.select_file') }}
                </label>
                <span class="selected-filename" v-if="previewUrl">@{{ previewUrl }}</span>
                <span class="import-instruction" style="position:absolute;right:50px;">
                    <span class="mr-1">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.87 6.22606L7.741 14.3551C6.96 15.1361 6.96 16.4021 7.741 17.1831C8.522 17.9641 9.788 17.9641 10.569 17.1831L18.697 9.05506C20.259 7.49306 20.259 4.96006 18.697 3.39806C17.135 1.83606 14.602 1.83606 13.04 3.39806L4.915 11.5231C2.57 13.8681 2.57 17.6701 4.915 20.0151C7.26 22.3601 11.062 22.3601 13.407 20.0151L20.589 12.8331" stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <a href="https://cabinet.test.uz/ImportInstruction.docx" download>{{__('billing/catalog.download-instruction')}}</a>
                </span>

            </div>

        </div>

        <div class="form-controls">

            <div class="form_input btn-bottom" v-if="load">
                <button
                    :disabled="loading"
                    class="btn btn-orange"
                    @click="loadingHandler"
                >
                    {{ __('billing/catalog.load_file') }}
                </button>
            </div>

        </div>

      {{--  <table class="table">
            <tr>
                <th>Товаров</th>
            </tr>
            <tr>
                <td>2</td>
            </tr>
        </table>--}}

        <div class="form-row" v-if="products_count">
            <div class="form-group col-12">
                <div class="row">{{ __('billing/catalog.products_added_success') }}</div>
            </div>
            <div class="form-group col-12">
                <div class="row products-count">{{ __('billing/catalog.products_added') }} @{{ products_count }}</div>
            </div>
        </div>


        <div class="form-row">
            <div class="form-group col-12">
                <div v-if="errors ? errors.length : 0">
                    <h4 class="error">{{__('billing/catalog.product_import_errors')}}</h4>
                    <div class="error" v-for="error in errors">@{{ error }}</div>
                </div>
            </div>
        </div>


    </div>




    <script>
        var catalog = new Vue({
            el: '#catalog',
            data: {
                api_token: '{{Auth::user()->api_token}}',
                errors: '',
                products_count:0,
                loading: false,
                load: false,
                fileUpload: [],
                previewUrl: null,
                fileSelfie: [],
                active: false,
                selfie: '',
                passport: '',
                isPassportSuccess: false,
                fileUploadHandler: null,

            },
            methods: {
                loadingHandler: function() {

                    if (this.fileUploadHandler) {
                        this.loading = true;

                        const formHeaders = new Headers();
                        formHeaders.append('Authorization', 'Bearer ' + this.api_token);

                        const formData = new FormData();
                        formData.append('file', this.fileUploadHandler);

                        const config = {
                            method: 'POST',
                            headers: formHeaders,
                            body: formData,
                            redirect: 'follow'
                        };
                        var url = 'https://cabinet.test.uz/api/v1/catalog/products/import';
                        //var url = 'http://test.loc/api/v1/catalog/products/import';

                        this.products_count = 0;

                        fetch(url, config)
                            .then((response) => response.json())
                            .then((res) => {
                                this.errors = res.errors;
                                if (res.status === 'success') {
                                    this.isPassportSuccess = true;
                                    //this.loading = false;
                                    this.products_count = res.data.count;

                                } else {
                                    this.$toasted.clear();
                                    //this.loading = false;

                                }
                            }).catch(e => {
                                //this.loading = false;
                                //console.log(e)
                            });

                    } else {
                        this.$toasted.error('error upload', {
                            className: 'error-toasted',
                            action: {
                                text: 'button cancel',
                                onClick: (e, toastObject) => {
                                    toastObject.goAway(0);
                                }
                            }
                        });
                    }

                    this.loading = false;
                    this.load = false;
                },
                changeFile:function(e) {
                    console.log(this.load);

                    this.fileUploadHandler = e.target.files[0];
                    this.previewUrl = e.target.files[0].name;
                    this.load = true;
                    this.loading = false;
                    console.log(this.load);

                },


            },
            mounted: function () {

            }
        })
    </script>
@endsection
