@extends('templates.panel.app')

@section('title', 'Отчет о верификации в MyID')
@section('class', 'buyers list')

@section('center-header-prefix')
    <a class="link-back" href="/{{app()->getLocale()}}/panel/buyers/{{$buyer_id}}"><img
            src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')
<style>
    #myid_report .value{
        font-weight: 600;
    }
    #myid_report .caption{
        color: rgba(0, 0, 0, 0.5);
    }
    .lead #text{
        color: red;
    }
    .skeleton-loader .lead{
        display: inline-block;
        background: rgba(190,190,190,.2);
        width: 200px;
        height: 34px;
        border-radius: 6px;
    }
    .skeleton-loader .lead:after{
       background: transparent;
    }
    .skeleton-loader .caption{
        height: 12px;
        background: rgba(190,190,190,.2);
        border-radius: 4px;
    }
    .skeleton-loader .value{
        height: 12px;
        background: rgba(190,190,190,.2);
        border-radius: 4px;
    }

    .skeleton-loader .skeleton-animation{
        background: linear-gradient(90deg,rgba(190,190,190,.2) 25%,rgba(129,129,129,.24) 37%,rgba(190,190,190,.2) 63%);
        background-size: 400% 100%;
        -webkit-animation: skeleton-animation 1.4s ease infinite;
        animation: skeleton-animation 1.4s ease infinite;
    }
    @keyframes skeleton-animation {
        0%{
            background-position: 100% 50%;
        }
        100%{
            background-position: 0 50%;
        }
    }
</style>

<div id="myid_report">
    <div  v-if="!loading" class="">
        <div v-for="(myIdKey, i) in myIdReportKeys" :key="i">
            <div class="lead">@{{ myIdReportTranslations[myIdKey.groupName]}}</div>
            <table class="table" v-if="myIdReport">
                <tr v-for="(mkey, k) in myIdKey.keys" :key="k">
                    <td  class="p-0" v-if="(mkey === 'permanent_registration') || (mkey === 'temporary_registration') " colspan="2">
                        <table class="w-100 border-0 m-0">
                            <tr v-for="(objvalue, objkey) in myIdReport[myIdKey.groupName][mkey]">
                                <td width="30%"  class="pl-2">
                                    <ul class="pl-3 m-0">
                                        <li style="list-style-type: '- '" class="caption">@{{myIdReportTranslations[objkey]}}</li>
                                    </ul>
                                </td>
                                <td>
                                    <div v-if="objkey === 'gender'" class="value">@{{objvalue | formatGender}}</div>
                                    <div v-else-if="objkey.includes('_update_') || objkey.includes('date')" class="value">@{{objvalue | formatDate }}</div>
                                    <div v-else class="value">@{{objvalue | checkEmpty }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="p-0" v-else colspan="2">
                        <table class="w-100 m-0">
                            <tr>
                                <td :class="{'border-0': k === 0}" width="30%"><div class="caption">@{{myIdReportTranslations[mkey]}}</div></td>
                                <td :class="{'border-0': k === 0}">
                                    <div v-if="mkey === 'gender'" class="value">@{{myIdReport[myIdKey.groupName][mkey] | formatGender}}</div>
                                    <div v-else-if="mkey.includes('_update_')" class="value">@{{myIdReport[myIdKey.groupName][mkey] | formatDate }}</div>
                                    <div v-else class="value">@{{myIdReport[myIdKey.groupName][mkey] | checkEmpty }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <hr>
        </div>
    </div>
   
    <div v-else class="skeleton-loader">
        <div class="lead skeleton-animation"></div>
        <table class="table">
            <tr v-for="(a, k) in 16" :key="k">
                <td width="30%"><div :style="{width: `${RandNum(300, 390)}px`}" class="caption skeleton-animation"></div></td>
                <td><div :style="{width: `${RandNum(100, 300)}px`}" class="value skeleton-animation"></div></td>
            </tr>
        </table>
    </div>
</div> 
    @include('panel.buyer.parts.myid')
    
@endsection
