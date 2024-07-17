@php
    $buyerPersonals = $buyer->personals ?? new \App\Models\BuyerPersonal();
@endphp

<!-- Detail information -->
<div class="lead">{{__('panel/buyer.detail_info')}}</div>
<table class="table">
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.fio')}}</div>
        </td>
        <td>
            <div
                class="value">{!!$buyer->name . ' ' . $buyer->surname . ' ' . $buyer->patronymic /*!= ""?$buyer->fio:"&mdash;"*/ !!}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.phone')}}</div>
        </td>
        <td>
            <div class="value">{!!$buyer->phone != ""?$buyer->phone:"&mdash;"!!}</div>
        </td>
    </tr>
    {{--<tr>
        <td><div class="caption">{{__('cabinet/profile.home_phone')}}</div></td>
        <td><div class="value">{!! @$buyerPersonals->home_phone != ""?@$buyerPersonals->home_phone:"&mdash;"!!}</div></td>
    </tr>
    <tr>
        <td><div class="caption">{{__('cabinet/profile.birthday')}}</div></td>
        <td><div class="value">{!!@$buyer->birth_date != ""?@$buyer->birth_date:"&mdash;"!!}</div></td>
    </tr>--}}
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.pinfl')}}</div>
        </td>
        <td>
            <div class="value">{!!@$buyerPersonals->pinfl != ""?@$buyerPersonals->pinfl:"&mdash;"!!}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.inn')}}</div>
        </td>
        <td>
            <div class="value">{!!@$buyerPersonals->inn != ""?@$buyerPersonals->inn:"&mdash;"!!}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.gender_title')}}</div>
        </td>
        <td>
            <select name="gender" required class="form-control" id="gender" type="text">
                <option @if(old('gender', $buyer->gender) === 1) selected="selected"
                        @endif value="1">{{__('cabinet/profile.gender.male')}}</option>
                <option @if(old('gender', $buyer->gender) === 2) selected="selected"
                        @endif value="2">{{__('cabinet/profile.gender.female')}}</option>
                <option @if(old('gender', $buyer->gender) == 0) selected="selected"
                        @endif value="0">{{__('cabinet/profile.gender.unknown')}}</option>
            </select>

        </td>
    </tr>
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.birthday')}}</div>
        </td>
        <td>
            <input placeholder="ГГГГ.ММ.ДД" value="{{ @$buyer->birth_date }}" name="birth_date" id="birth_date"
                   type="text" class="form-control">
        </td>
    </tr>
</table>

<hr>

<!-- Address -->
@if(count($buyer->addresses) > 0)
    <div class="lead">{{__('cabinet/profile.addresses')}}</div>
    <table class="table">
        @foreach($buyer->addresses as $address)
            @if($address->type!='residential')
                <tr>
                    <td>
                        <div class="caption">{{__('cabinet/profile.address_'.$address->type)}}</div>
                    </td>
                    <td>
                        <div class="value">
                            {{$address->string}}
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
        @if($katm_region)
            <tr>
                <td>
                    <div class="caption">{{ __('panel/buyer.region') }}</div>
                </td>
                <td>
                    <div class="value">
                        {{ $katm_region->region_name }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="caption">{{__('panel/buyer.local_region')}}</div>
                </td>
                <td>
                    <div class="value">
                        {{ $katm_region->local_region_name }}
                    </div>
                </td>
            </tr>
        @endif
    </table>
    <hr>
@endif

<!-- Work -->
<div class="lead">{{__('cabinet/profile.work_place')}}</div>
<table class="table">
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.work_company')}}</div>
        </td>
        <td>
            <div class="value">{!!$buyerPersonals->work_company!=""?$buyerPersonals->work_company:"&mdash;" !!}</div>
        </td>
    </tr>
    {{-- <tr>
        <td><div class="caption">{{__('cabinet/profile.work_phone')}}</div></td>
        <td><div class="value">{!!$buyerPersonals->work_phone != ""?$buyerPersonals->work_phone:"&mdash;" !!}</div></td>
    </tr> --}}
</table>

<hr>

<!-- Passport -->
<div class="lead">{{__('cabinet/profile.passport')}}</div>
<table class="table">
    <tr>
        <td>
            <div class="caption">{{__('cabinet/profile.passport_number')}}</div>
        </td>
        <td>
            <div
                class="value">{!!$buyerPersonals->passport_number != ""?$buyerPersonals->passport_number:"&mdash;"!!}</div>
        </td>
    </tr>
    {{--<tr>
        <td><div class="caption">{{__('cabinet/profile.passport_date_issue')}}</div></td>
        <td><div class="value">{!!$buyerPersonals->passport_date_issue != ""?$buyerPersonals->passport_date_issue:"&mdash;"!!}</div></td>
    </tr>
    <tr>
        <td><div class="caption">{{__('cabinet/profile.passport_issued_by')}}</div></td>
        <td><div class="value">{!!$buyerPersonals->passport_issued_by != ""?$buyerPersonals->passport_issued_by:"&mdash;"!!}</div></td>
    </tr> --}}
</table>


<div class="lead">{{__('cabinet/profile.guarant')}}</div>
@if($buyer->guarants)
    @foreach($buyer->guarants as $quarant)

        <table class="table">
            <tr>
                <td>
                    <div class="caption">{{ __('cabinet/profile.guarant_fio') }}</div>
                </td>
                <td>
                    <div class="value">{{ $quarant->name }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="caption">{{ __('cabinet/profile.guarant_phone') }}</div>
                </td>
                <td>
                    <div class="value">{{ $quarant->phone }}</div>
                </td>
            </tr>
        </table>

    @endforeach
@endif


{{--<div class="form-controls">
    <a href="{{localeRoute('panel.buyers.edit', $buyer->id)}}" class="btn ml-lg-auto btn-primary">{{__('app.btn_edit')}}</a>
</div>--}}<!-- /.form-controls -->

<script>
$('select#gender').change(function () {

    var gender = $('select#gender').val();

    if (gender >= 0) {

        $.ajax({
            headers: {
                'Content-Language': '{{app()->getLocale()}}',
                'Accept': 'application/json',
            },
            'url': '/api/v1/employee/buyers/set-gender',
            'type': 'post',
            data: { 'buyer_id':{{$buyer->id}}, 'gender': gender, 'api_token': '{{Auth::user()->api_token}}' },
            success: function () {
            },
            error: function (e) {
                alert('error');
            },
        });
    }


});

$('#birth_date').blur(function () {
    console.log($(this).val());
    if (/^(\d{4})-(\d{2})-(\d{2})$/.test($(this).val())) {
        var birthdate = $(this).val();
        $.ajax({
            headers: {
                'Content-Language': '{{app()->getLocale()}}',
                'Accept': 'application/json',
            },
            'url': '/api/v1/employee/buyers/set-birthdate',
            'type': 'post',
            data: { 'buyer_id':{{$buyer->id}}, 'birthdate': birthdate, 'api_token': '{{Auth::user()->api_token}}' },
            success: function (result) {
                console.log(result);
            },
            error: function (e) {
                alert('error');
            },
        });

    } else {
        alert('Неверный формат даты');
    }

});
</script>
