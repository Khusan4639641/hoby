@extends('templates.panel.app')

@section('title', $user->name . ' ' . $user->surname . ' ' . $user->patronymic)

@push('css')
    <style>
        .bg-teal {
            background-color: #8ec29a !important;
        }

        .bg-light-blue {
            background-color: #8E91BBFF !important;
        }

        .bg-light-red {
            background-color: #bc7a80 !important;
        }

        .text-title-light {
            color: #a4a4a4;
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
@endpush

@section('content')

    <div class="row">

        @include('panel.monitoring.parts.links')

        @if(count($redisCache) > 0)
            <div class="col-lg-12 text-right">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>
                            {{ __('Redis Cache') }}
                        </th>
                    </tr>
                    @foreach($redisCache as $redisCacheKey => $redisCacheItem)
                        <tr>
                            <td>
                                <span class="badge badge-info">{{ $redisCacheKey }}</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
                <form method="POST" action="{{localeRoute('panel.monitoring.user.cache.clear', $user->id)}}">
                    @csrf
                    <button class="btn btn-success" type="submit">{{ __('Redis Cache clear') }}</button>
                </form>
            </div>
        @endif

        <div class="col-lg-4">
            <h2 class="m-4">{{ __('Платёжные карты') }}</h2>

            <h4 class="m-4">{{ __('Номер телефона: :phone', ['phone' => $user->phone]) }}</h4>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th class="text-left"> {{ __('Тип') }}</th>
                    <th class="text-right">{{ __('Номер телефона') }}</th>
                    <th class="text-right"> {{ __('Номер карты') }}</th>
                </tr>
                @foreach($user->cards as $card)
                    <tr>
                        <td class="text-left">{{ $card->type }}</td>
                        <td class="text-right">{{ $card->phone }}</td>
                        <td class="text-right">{{ $card->public_number }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>

        <div class="col-lg-8">
            <h2 class="m-4">{{ __('Скоринг') }}</h2>

            <table class="table accounts-list dataTable no-footer">
                <tbody>
                <tr>
                    <th class="text-left"> {{ __('Скоринг') }}</th>
                    <th class="text-left"> {{ __('Балл') }}</th>
                    <th class="text-left"> {{ __('') }}</th>
                </tr>
                @foreach($user->cards as $scoringCard)
                    @if($scoringCard->scoring->count() > 0)
                        <tr>
                            <td class="text-left" colspan="3">
                                <span style="background-color: #8ec29a;"
                                      class="border rounded p-1 pl-2 pr-2 text-white">
                                {{ $scoringCard->type }} / {{ $scoringCard->phone }} / {{ $scoringCard->public_number }}
                            </span>
                            </td>
                        </tr>
                        @foreach($scoringCard->scoring as $scoring)
                            <tr>
                                <td class="text-left">{{ $scoring->scoring }}</td>
                                <td class="text-left">{{ $scoring->ball }}</td>
                                <td class="text-left">{!! \App\Http\Controllers\Web\Panel\MonitoringController::toJson($scoring->response) !!}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>

        </div>

    </div>

@endsection()
