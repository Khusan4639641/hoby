@extends('templates.panel.app')

@section('title', __('monitoring.title'))

@section('content')


    <div class="col-lg-6">
        <table class="table accounts-list dataTable no-footer">
            <tbody>
            <tr>
                <th>{{ __('Списания не соответствующие пополнению') }}:</th>
                <td class="text-right"><a class="btn btn-success"
                                          href="{{ localeRoute('panel.monitoring.accounts') }}">{{ __('Посмотреть') }}</a>
                </td>
            </tr>
            <tr>
                <th>{{ __('Контракты в которых не соответствуют списания') }}:</th>
                <td class="text-right"><a class="btn btn-success"
                                          href="{{ localeRoute('panel.monitoring.contracts') }}">{{ __('Посмотреть') }}</a>
                </td>
            </tr>
            <tr>
                <th>{{ __('Списания не соответствующие пополнению по бонусам') }}:</th>
                <td class="text-right"><a class="btn btn-success"
                                          href="{{ localeRoute('panel.monitoring.bonuses') }}">{{ __('Посмотреть') }}</a>
                </td>
            </tr>
            {{--            <tr>--}}
            {{--                <th>{{ __('Контракты в которых списания и пополнения производятся по старому алгоритму') }}:</th>--}}
            {{--                <td class="text-right"><a class="btn btn-success"--}}
            {{--                                          href="{{ localeRoute('panel.monitoring.contracts.old') }}">{{ __('Посмотреть') }}</a>--}}
            {{--                </td>--}}
            {{--            </tr>            --}}
            {{--            <tr>--}}
            {{--                <th>{{ __('Расхождения в депозитах') }}:</th>--}}
            {{--                <td class="text-right"><a class="btn btn-success"--}}
            {{--                                          href="{{ localeRoute('panel.monitoring.deposits') }}">{{ __('Посмотреть') }}</a>--}}
            {{--                </td>--}}
            {{--            </tr>--}}
            </tbody>
        </table>
    </div>


@endsection()
