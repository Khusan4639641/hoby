<script>
    let contractsTable,
        status = '',
        act_status = '';
        imei_status = '';
        cancel_act_status = '';
        client_status = '';
        status__not = '';

    $(document).ready(function () {

        function locationHashChanged() {
            switch (location.hash) {
                case "#all":
                    status = "";
                    break;
                case "#active":
                    status = 1;
                    break;
                case "#debt":
                    status = 4
                    break;
                case "#cancel":
                    status = 5;
                    break;
                case "#complete":
                    status = 9;
                    break;
                /* case "#verification":
                    status = 6;
                    break; */
                case "#act_verify":
                    act_status = 1;
                    status = [1, 3, 4];
                    break;
                case "#act_need":
                    act_status = [0, 2];
                    status = [1, 3, 4];
                    break;
                case "#imei_verify":
                    imei_status = 3;
                    break;
                case "#imei_need":
                    imei_status = 2;
                    break;
                case "#cancel_act_verify":
                    cancel_act_status = [1, 2];
                    break;
                case "#check_client_photo":
                    client_status = 3;
                    break;
                case "#upload_client_photo":
                    status__not = 0;
                    client_status = 0;
                    break;
            }

            $(location.hash).tab('show');

            contractsTable.DataTable().draw();
        }

        //Data tables init
        if($('.contracts .contract-list').length > 0){

            window.onhashchange = locationHashChanged;

            contractsTable = $('.contracts .contract-list').dataTable( {
                serverSide: true,
                pagingType: "input",
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
                        orderByDesc: 'created_at',
                        list_type: 'data_tables',
                        status: status,
                        id: contractsTable.fnSettings().oPreviousSearch.sSearch,
                        act_status: act_status,
                        imei_status: imei_status,
                        client_status: client_status,
                        status__not: status__not,
                        cancel_act_status: cancel_act_status,
                        offset: contractsTable.fnSettings()._iDisplayStart,
                        limit: contractsTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        //console.log(res);
                        //res = JSON.parse(res);
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsTotal,
                            data: res.data.data
                        });
                        loading(false);
                    });
                },
                "language": {
                    "url": "/assets/json/ru.lang",
                },
                "ordering": false,
                    initComplete: function ( settings, json) {

                }
            } );
            //locationHashChanged();
        }

        //Change status
        $('#contractStatus a').click(function() {
            status = $(this).data('status') != '' ? $(this).data('status') : '';

            client_status = $(this).data('client_status') !== '' ? $(this).data('client_status') : '';
            status__not = $(this).data('status__not') !== '' ? $(this).data('status__not') : '';

            // dev_nurlan 30.03.2022
            // act_status = parseInt($(this).data('act_status')) >=0 ? $(this).data('act_status'):'';
            act_status = (Array.isArray($(this).data('act_status'))
                ? (  $(this).data('act_status')  )
                : (
                    (  parseInt($(this).data('act_status')) >= 0  )
                        ? $(this).data('act_status')
                        : ''
                ));

            imei_status = parseInt($(this).data('imei_status')) >=0 ? $(this).data('imei_status'):'';

            cancel_act_status = $(this).data('cancel_act_status') != ''?$(this).data('cancel_act_status'):'';

            contractsTable.DataTable().draw();
        });

        // Search
        $('#dataTablesSearch button').click(function(){
            const value = $('#dataTablesSearch input').val();
            contractsTable.DataTable().search(value).draw();
        })

        //filter by phone and clear
        $('#dataTablesSearch input').keyup((event) => {
            if (event.keyCode === 13) {
                contractsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
            } else if (event.keyCode === 27) {
                contractsTable.DataTable().search('').draw();
                $('#dataTablesSearch input').val('')
            }
        });
       /* setInterval(function (){
            contractsTable.DataTable().ajax.reload( null, false );
        }, {{-- Config::get('test.timer.contracts')*1000--}}); */
    })


    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

</script>
