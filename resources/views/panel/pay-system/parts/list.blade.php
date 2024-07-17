<script>
    let paysystemTable,
        status = '';

    $(document).ready(function () {
        function locationHashChanged() {
            switch (location.hash) {
                case "#all":
                    status = "";
                    break;
                case "#active":
                    status = 1;
                    break;
                case "#draft":
                    status = 0;
                    break;
                case "#archive":
                    status = 0;
                    break;
            }

            $(location.hash).tab('show');

            paysystemTable.DataTable().draw();
        }

        if($('.pay-system .pay-system-list').length > 0){


            window.onhashchange = locationHashChanged;

            paysystemTable = $('.pay-system .pay-system-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                sDom: "lrtip",
                info:false,
                lengthChange :false,
                buttons: [
                ],

                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.pay-system.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderByDesc: 'created_at',
                        list_type: 'data_tables',
                        name__like: paysystemTable.fnSettings().oPreviousSearch.sSearch,
                        status: status,
                        orderBy: 'updated_at',
                        offset: paysystemTable.fnSettings()._iDisplayStart,
                        limit: paysystemTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        //res = JSON.parse(res);
                        console.log(res);
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

                },
                "createdRow": function( row, data, dataIndex ) {
                    $(row).attr( 'data-link', data[7] );
                }
            } );
        }

        //Change status
        $('#partnerStatus a').click(function() {
            status = $(this).data('status');
            partnersTable.DataTable().draw();
        });

        //Search
        $('#dataTablesSearch button').click(function(){
            partnersTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        })

        $('#DataTables_Table_0 tbody').on('click', '.js-action', function (e) {

            let action = $(this).data('action');
            let partner_id = $(this).data('id');
            let url;

            switch (action){
                case 'confirm':
                    url = '/api/v1/employee/partners/action/confirm';
                    break;

                case 'block':
                    url = '/api/v1/employee/partners/action/block';
                    break;
            }

            axios.post(url, {
                api_token: Cookies.get('api_token'),
                partner_id: partner_id
            },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                if (response.data.status === 'success') {
                    partnersTable.DataTable().ajax.reload( null, false );
                }
            })



            return false;
        })

        $('#DataTables_Table_0 tbody').on('click', 'tr', function (e) {
            window.location.href = $(this).data('link');
        })


    })

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }
</script>
