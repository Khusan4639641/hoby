<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{__('katm.report_heading')}}</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="container pt-5 pb-5">
<div>
    <h3 class="text-center">{{__('katm.report_heading')}}</h3>
    <h4 class="text-center pt-4">{{__('katm.client')}}</h4>
    <table class="table table-striped table-sm mt-5 mb-5">
        <thead>
        <tr class="text-center">
            <th>{{__('katm.type_information')}}</th>
            <th>{{__('katm.information')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['client'] as $label=>$value)
            <tr>
                <td class="font-weight-bold text-muted">{{$label}}</td>
                <td class="text-right">{{$value}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <h4 class="text-center">{{__('katm.subject_claims')}}</h4>
    <table class="table table-striped table-sm mt-5 mb-5">
        <thead class="text-center">
        <tr>
            <th>{{__('katm.org_type')}}</th>
            <th>{{__('katm.claims_qty')}}</th>
            <th>{{__('katm.granted_qty')}}</th>
            <th>{{__('katm.rejected_qty')}}</th>
        </tr>
        </thead>
        <tbody>
        @if(count($report['subject_claims'])>0)
            @foreach($report['subject_claims'] as $label=>$value)
                <tr>
                    <td class="font-weight-bold text-muted">{{$value['org_type']}}</td>
                    <td class="text-center">{{$value['claims_qty']}}</td>
                    <td class="text-center">{{$value['granted_qty']}}</td>
                    <td class="text-center">{{$value['rejected_qty']}}</td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>

    @if(isset($report['subject_debts']) && $report['subject_debts'] != '')
        <h4 class="text-center">{{__('katm.subject_debts')}}</h4>
        <table class="table table-striped table-sm mt-5 mb-5">
            <thead class="text-center">
            <tr>
                <th>{{__('katm.org_name')}}</th>
                <th>{{__('katm.all_debts')}}</th>
                <th>{{__('katm.curr_debts')}}</th>
                <th>{{__('katm.last_update')}}</th>
            </tr>
            </thead>
            <tbody>
            @if(count($report['subject_debts'])>0)
                @foreach($report['subject_debts'] as $label=>$value)
                    @if(!$value)
                        @continue;
                    @endif

                    <tr>
                        <td class="font-weight-bold text-muted">{{$value['org_name']??""}}</td>
                        <td class="text-center">{{ isset($value['all_debts'])? number_format($value['all_debts']/100,2,'.',' ') : '' }}</td>
                        <td class="text-center">{{ isset($value['curr_debts']) ? number_format($value['curr_debts']/100,2,'.',' ') : '' }}</td>
                        <td class="text-center">{{$value['last_update']??""}}</td>
                    </tr>
                @endforeach
            @endif

            </tbody>
        </table>
    @endif

    @if(isset($report['average_monthly_payment']))
        <table class="table table mt-5 mb-5">
            <tr>
                <th>{{__('katm.average')}}</th>
                <td class="text-right">{{number_format(@$report['average_monthly_payment']/100,2,'.',' ')}} сум.</td>
            </tr>
        </table>
    @endif
</div>
</body>

</html>
