<script>
let contractsTable,
    status = {{$status}};
    recovery = {{$recovery}};
    action = {{$action}};

$(document).ready(function () {

    function locationHashChanged() {
        status = 4;
        // alert(status);
        $(location.hash).tab('show');
        contractsTable.DataTable().draw();
    }

    // alert('list '+recovery + ' ' + action);
    //Data tables init
    if ($('.contracts .contract-list').length > 0) {

        window.onhashchange = locationHashChanged;
        contractsTable = $('.contracts .contract-list').dataTable({
            serverSide: true,
            pageLength: 15,
            info: false,
            lengthChange: false,
            sDom: 'lrtip',
            buttons: [],
            'ajax': function (data, callback, settings) {
                loading(true);
                $.get('{{localeRoute('panel.recovery.list')}}', {
                    api_token: Cookies.get('api_token'),
                    orderByDesc: 'contracts.created_at',
                    list_type: 'data_tables',
                    status: status,
                    recovery: 0,
                    action: action,
                    id: contractsTable.fnSettings().oPreviousSearch.sSearch,
                    offset: contractsTable.fnSettings()._iDisplayStart,
                    limit: contractsTable.fnSettings()._iDisplayLength,
                }, function (res) {
                    console.log(res);
                    //res = JSON.parse(res);
                    callback({
                        recordsTotal: res.data.recordsTotal,
                        recordsFiltered: res.data.recordsTotal,
                        data: res.data.data,
                    });
                    loading(false);
                });
            },
            'language': {
                'url': '/assets/json/ru.lang',
            },
            'ordering': false,
            initComplete: function (settings, json) {

            },
        });

        //locationHashChanged();

    }

    //Change status
    $('#contractRecovery a').click(function () {
        const recoveryStatus = $(this).data('status');
        const _action = $(this).data('action');
       // recovery = 0;// recoveryStatus != '' ? recoveryStatus : 0;
        action = _action != '' ? _action : 0;
        //act_status = parseInt($(this).data('act_status')) >=0 ? $(this).data('act_status'):'';
        //imei_status = parseInt($(this).data('imei_status')) >=0 ? $(this).data('imei_status'):'';
        //cancel_act_status = $(this).data('cancel_act_status') != ''?$(this).data('cancel_act_status'):'';
        contractsTable.DataTable().draw();
    });

    //Search
    $('#dataTablesSearch button').click(function () {
        const value = $('#dataTablesSearch input').val();
        contractsTable.DataTable().search(value).draw();
    });

    //filter by phone and clear
    $('#dataTablesSearch input').keyup((event) => {
        if (event.keyCode === 13) {
            contractsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        } else if (event.keyCode === 27) {
            contractsTable.DataTable().search('').draw();
            $('#dataTablesSearch input').val('');
        }
    });

    /* setInterval(function (){
         contractsTable.DataTable().ajax.reload( null, false );
     }, {{-- Config::get('test.timer.contracts')*1000--}}); */
});

/*function change(contractId, recovery) {
    console.log('Contract Id', contractId);
    console.log('Recovery', recovery);

    const password = 2500;
    const result = window.prompt("{{ __('auth.placeholder_input_password') }}");

    if (parseInt(result) === password) {
        window.location.href = makeRoute('panel.pretension', contractId);
    }
}*/

//Show hide loader
function loading(show = false) {
    if (show)
        $('.loading').addClass('active');
    else
        $('.loading').removeClass('active');
}

</script>

<style>
    .change-status {
        border: none;
        outline: none;
        border-radius: 8px;
        padding: 12px 24px;
    }

    .change-status:focus {
        outline: none;
    }
</style>
