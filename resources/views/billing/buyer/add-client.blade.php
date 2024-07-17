    @extends('templates.billing.app')
@section('class', 'buyer create')

@section('center-header-control')
    <a href="{{ url()->previous() }}" class="btn btn-orange">{{__('app.btn_back')}}</a>
@endsection

@section('content')

<div class="wrapper" id="addClient">
    <iframe
        v-if="isVisibleIFrame"
        allow="camera;"
        :src="`${webViewLink}?companyId=${user.id}&callback=${callbackUrl()}&lang={{app()->getLocale()}}`"
    ></iframe>

    <div v-else class="requirements">
        <div class="requirements__content">
            <h1 class="requirements__title">
                {{ __('billing/buyer.splash_title')}}
            </h1>

            <div>
                <p>
                    <span>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.55591 10.6706V7.55859C3.55591 5.34926 5.34657 3.55859 7.55591 3.55859H10.6666M21.3332 3.55859H24.4439C26.6532 3.55859 28.4439 5.34926 28.4439 7.55859V10.6693M3.55591 21.3333V24.4439C3.55591 26.6533 5.34657 28.4439 7.55591 28.4439H10.6666M21.3332 28.4439H24.4439C26.6532 28.4439 28.4439 26.6533 28.4439 24.4439V21.3333M10.6666 10.6706V11.7213M21.3332 10.7759V11.8266M17.1039 10.6679V16.4453C17.1039 17.1813 16.5066 17.7786 15.7706 17.7786H14.4026M21.0879 21.2813C19.7759 22.7426 17.8732 23.6626 15.7546 23.6626C13.6346 23.6626 11.7292 22.7413 10.4172 21.2773" stroke="#696969" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="requirements__text">{{ __('billing/buyer.face_id')}} </span>
                </p>
                <p>
                    <span>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.5587 8.89083H19.6707M16.1147 5.33483V12.4468M8.37112 16.8975H23.1098L26.6658 8.8895H23.1098M3.70312 5.3335H7.11113L8.49246 17.7722C8.71779 19.7975 10.4298 21.3308 12.4685 21.3308H22.8765" stroke="#696969" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <ellipse cx="11.6672" cy="26.6674" rx="1.78165" ry="1.78165" fill="#696969"/>
                            <circle cx="20.339" cy="26.6674" r="1.78165" fill="#696969"/>
                        </svg>
                    </span>
                    <span class="requirements__text">{{ __('billing/buyer.fast_register')}}</span>
                </p>
                <p>
                    <span>
                         <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.0866 15.6758L15.2506 18.8385L20.9266 13.1611M26.6639 15.9985C26.6639 21.888 21.8895 26.6625 15.9999 26.6625C10.1104 26.6625 5.33594 21.888 5.33594 15.9985C5.33594 10.1089 10.1104 5.33447 15.9999 5.33447C21.8895 5.33447 26.6639 10.1089 26.6639 15.9985Z" stroke="#696969" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="requirements__text">{{ __('billing/buyer.full_register')}}</span>
                </p>
            </div>
            <button
                class="requirements__button btn btn-orange"
                @click="continueRegistration"
            >
                {{ __('billing/buyer.continue')}}
            </button>
        </div>
    </div>
</div>

<script>
    var addClient = new Vue({
        el: "#addClient",
        data: {
            isVisibleIFrame: false,
            user: @json(Auth::user()),
            webViewLink: @json(env('WEBVIEW_LINK'))
        },
        methods: {
            continueRegistration () {
                this.isVisibleIFrame = true
            },
            callbackUrl () {
                return `${window.location.origin}/orders/create`;
            }
        }

    })


</script>


<style>
    .requirements {
        background-color: rgb(255, 255, 255);
        border-radius: 8px;
        padding: 50px;
    }

    .requirements__title {
        font-size: 25px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .requirements__text {
        font-size: 16px;
        font-weight: 400;
        padding-bottom: 4px;
    }

    .requirements__content {
        text-align: center;
        width: 400px;
        margin: auto;
        margin-bottom: 20px;
    }

    p {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    p:nth-last-child(1) {
        margin-bottom: 0;
    }

    p svg {
        margin-right: 12px;
    }

    .requirements__button {
        margin-top: 30px;
        max-width: 384px;
        width: 100%;
    }

    iframe {
        width: 100%;
        height: calc(100vh - 280px);
        border: none;
    }
</style>


@endsection
