<div class="contracts list">
    <table class="table contract-list">
        <thead>
        <tr>
            <th>{{__('panel/contract.date')}}</th>
            <th>{{__('panel/contract.contract_id')}}</th>
            <th>{{__('panel/contract.partner')}}</th>
            <th>{{__('panel/contract.client')}}</th>
            <th>{{__('cabinet/profile.gender_title')}}</th>
            <th>{{__('cabinet/profile.birthday')}}</th>
            <th>{{__('panel/contract.phone')}}</th>
            <th>{{__('panel/contract.sum')}}</th>
            <th>{{__('panel/contract.paid_off')}}</th>
            <th>{{__('panel/contract.debt')}}</th>
            <th>{{__('panel/contract.day')}}</th>
            <th>{{__('panel/contract.status')}}</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table><!-- /.contract-list -->
</div>

<div class="loading"><img src="{{asset('images/loader.gif')}}"></div>

<script>
    let contractsTable;

    $(document).ready(function () {
        //Data tables init
        if($('.contracts .contract-list').length > 0){

            contractsTable = $('.contracts .contract-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.contracts.list')}}', {
                        api_token: Cookies.get('api_token'),
                        user_id: {{$buyer->id}},
                        // status: [1, 3, 4],
                        orderByDesc: 'created_at',
                        sortingBy: 'status',
                        list_type: 'data_tables',
                        offset: contractsTable.fnSettings()._iDisplayStart,
                        limit: contractsTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsTotal,
                            data: res.data.data
                        });
                        loading(false);
                    });
                },
                "language": {
                    "url": "/assets/json/ru.lang"
                },
                "ordering": false,
                initComplete: function ( settings, json) {

                }
            } );


        }
    });


    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

</script>
