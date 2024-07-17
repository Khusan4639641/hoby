@extends('templates.panel.app')

@section('title', "KATM отчёты по контрактам")

@section('content')


    <div class="col-lg-12">
        <table class="table table-striped no-footer">
            <tbody>

            @foreach($katmReports as $katmReport)

                <tr>
                    <td colspan="8" class="text-right text-uppercase">
                        {{ __('app.' . Str::lower(\Carbon\Carbon::createFromFormat('Y-m', $katmReport['date'])->format('F'))) }}
                        /
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $katmReport['date'])->format('Y') }}
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <th class="text-center">001</th>
                    <th class="text-center">START</th>
                    <th class="text-center">004</th>
                    <th class="text-center">005</th>
                    <th class="text-center">015</th>
                    <th class="text-center">016</th>
                    <th class="text-center">018</th>
                </tr>
                <tr>
                    <th>Успешно</th>
                    <td class="text-center {{ (int) $katmReport['001_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['001_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['start_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['start_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['004_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['004_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['005_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['005_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['015_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['015_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['016_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['016_success'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['018_success'] === 0 ? : 'bg-success text-white' }}">{{ number_format($katmReport['018_success'], 0, '', '`') }}</td>
                </tr>
                <tr>
                    <th>В ожидании</th>
                    <td class="text-center {{ (int) $katmReport['001_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['001_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['start_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['start_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['004_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['004_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['005_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['005_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['015_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['015_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['016_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['016_await'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['018_await'] === 0 ? : 'bg-warning text-white' }}">{{ number_format($katmReport['018_await'], 0, '', '`') }}</td>
                </tr>
                <tr>
                    <th>Не успешно</th>
                    <td class="text-center {{ (int) $katmReport['001_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['001_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['start_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['start_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['004_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['004_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['005_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['005_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['015_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['015_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['016_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['016_fail'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['018_fail'] === 0 ? : 'bg-danger text-white' }}">{{ number_format($katmReport['018_fail'], 0, '', '`') }}</td>
                </tr>
                <tr>
                    <th>Все</th>
                    <td class="text-center {{ (int) $katmReport['001_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['001_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['start_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['start_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['004_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['004_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['005_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['005_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['015_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['015_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['016_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['016_all'], 0, '', '`') }}</td>
                    <td class="text-center {{ (int) $katmReport['018_all'] === 0 ? : 'bg-secondary text-white' }}">{{ number_format($katmReport['018_all'], 0, '', '`') }}</td>
                </tr>

            @endforeach

            </tbody>
        </table>
    </div>


@endsection()
