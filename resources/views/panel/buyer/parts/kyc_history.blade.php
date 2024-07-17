<?php

use App\Models\User;
use App\Libs\KycHistoryLibs;

?>
<div class="row">
    <div class="col-12 col-lg-12" id="kyc_history">
        <div class="choose-card">


            <div class="scoring-results">
                <table class="table">
                    <thead>
                    <tr>
                        {{--<th>{{__('panel/buyer.scoring_month_year')}}</th>
                        <th>{{__('panel/buyer.scoring_result')}}</th>--}}
                        <th>Статус</th>
                        <th>Дата</th>
                        {{--<th>Дата завершения</th>--}}
                        <th>KYC оператор</th>
                        <th>Статус KYC</th>

                        <th>{{__('panel/buyer.definition')}}</th>

                        <th>{{__('panel/buyer.old_phone_number')}}</th>
                        <th>{{__('panel/buyer.old_address')}}</th>
                        <th>{{__('panel/buyer.image')}}</th>
                        <th>{{__('panel/buyer.card_number')}}</th>
{{--                        <th>{{__('panel/buyer.acts')}}</th> // dev_nurlan commented on 04.04.2022    --}}


                    </tr>
                    </thead>
                    <tbody>
                    @foreach($history as $item)
                        <tr>
                            <td class="">{{ User::getStatus($item->status) }}</td>
                            <td class="">{{ $item->created_at }}</td>
                            {{--<td class="">{{ $item->created_at!=$item->updated_at ? $item->updated_at : '' }}</td> --}}
                            @if($item->kyc)
                                <td class=""
                                    title="ID: {{ $item->kyc_id }}">{{ $item->kyc->name }} {{ $item->kyc->surname }}</td>
                            @else
                                <td class=""></td>
                            @endif
                            <td class="">{{ KycHistoryLibs::getKycStatus($item->kyc_status) }}</td>
                            <td class="">{{ KycHistoryLibs::getKycReason($item->reason) }}</td>
                            <td class="">{{ $item->old_phone }}</td>
                            <td class="">{{ $item->old_address }}</td>
                            <td class="">
                                @if(!empty($item->image))
                                    <spam style="cursor: pointer;color: #0d95e8"
                                          onclick="initPhotoViewer('{{Storage::url($item->image)}}', null)">{{__('panel/buyer.show')}}
                                    </spam>
                                @endif
                            </td>
                            <td class="">{!! $item->title !!}</td>

{{--                            <td>{{$item->act_number}}</td> // dev_nurlan commented on 04.04.2022    --}}

{{--                            EXTRA CODE START--}}
{{--                            @if($item->status == 206) // dev_nurlan commented on 04.04.2022    --}}
{{--                                @if(isset($acts))--}}
{{--                                    <td>--}}
{{--                                        @foreach($acts as $act)--}}

{{--                                            {{ __('Дата возбуждения дела :date', ['date' => $act->initiation_date]) }}<br>--}}
{{--                                            {{ __('Номер дела :number', ['number' => $act->number ]) }}<br>--}}
{{--                                            {{ __('Ф.И.О исполнителя :observer_name', ['observer_name' => $act->observer_name ]) }}<br>--}}
{{--                                            {{ __('Телефон исполнителя :observer_phone', ['observer_phone' => $act->observer_phone ]) }}<br><br>--}}

{{--                                        @endforeach--}}
{{--                                    </td>--}}
{{--                                @endif--}}
{{--                            @endif--}}
{{--                            EXTRA CODE END--}}

                            {{--<template v-if="scoring === true">
                                <td class="">@{{ index }}</td>
                                <td class="yes">{{__('app.yes')}}</td>
                            </template>
                            <template v-else>
                                <td class="no">@{{ index }}</td>
                                <td class="no">{{__('app.no')}}</td>
                            </template> --}}
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
