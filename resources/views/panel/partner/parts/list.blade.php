<script>
    let partnersTable,
        status = '';

    $(document).ready(function () {
        function locationHashChanged() {
            switch (location.hash) {
                case "#all":
                    status = "";
                    break;
                case "#verified":
                    status = 1;
                    break;
                case "#verification":
                    status = 0;
                    break;
            }

            $(location.hash).tab('show');

            partnersTable.DataTable().draw();
        }

        if($('.partners .partners-list').length > 0){


            window.onhashchange = locationHashChanged;

            partnersTable = $('.partners .partners-list').dataTable( {
                serverSide: true,
                pagingType: "input",
                pageLength: 18,
                sDom: "lrtip",
                info:false,
                lengthChange :false,
                buttons: [
                ],
                columns: [
                    null,
                    {class: 'd-none d-sm-table-cell'},
                    null,
                    {class: 'd-none d-sm-table-cell'},
                    null,
                    /*{class: 'd-none d-sm-table-cell'},*/
                    null,
                    /*null,*/
                    {className: 'item-readmore'},
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.partners.list')}}', {
                        api_token: Cookies.get('api_token'),
                        list_type: 'data_tables',
                        name__like: partnersTable.fnSettings().oPreviousSearch.sSearch,
                        or__id__: partnersTable.fnSettings().oPreviousSearch.sSearch,
                        status: status,
                        orderBy: 'id desc',
                        offset: partnersTable.fnSettings()._iDisplayStart,
                        limit: partnersTable.fnSettings()._iDisplayLength,
                    }, function(res) {
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

        // Search name and clear
        $('#dataTablesSearch input').keyup((event) => {
            if (event.keyCode === 13) {
                partnersTable.DataTable().search($('#dataTablesSearch input').val()).draw();
            } else if (event.keyCode === 27) {
                partnersTable.DataTable().search('').draw();
                $('#dataTablesSearch input').val('')
            }
        });

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
