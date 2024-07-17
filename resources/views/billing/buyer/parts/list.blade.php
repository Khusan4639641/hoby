<script>
    let buyersTable,
        status = '';

    $(document).ready(function () {


        function locationHashChanged() {
            switch (location.hash) {
                case "#all":
                    status = "";
                    break;
                case "#verified":
                    status = 4;
                    break;
                case "#verification":
                    status = 2;
                    break;
            }

            $(location.hash).tab('show');

            buyersTable.DataTable().draw();
        }

        if($('.buyers .buyers-list').length > 0){

            window.onhashchange = locationHashChanged;

            //DataTables init
            buyersTable = $('.buyers .buyers-list').dataTable({
                "serverSide": true,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                pageLength: 10,
                buttons: [
                ],
                columns: [
                    null,
                    null,
                    null,
                    {className: "item-title"},
                    {className: "item-readmore"},
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('billing.buyers.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderByDesc: 'updated_at',
                        list_type: 'data_tables',
                        status: status,
                        offset: buyersTable.fnSettings()._iDisplayStart,
                        limit: buyersTable.fnSettings()._iDisplayLength,
                        search: buyersTable.fnSettings().oPreviousSearch.sSearch,
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
                    $(row).attr( 'data-link', data[5] );
                }
            });

            //Table tr click event
            $('#DataTables_Table_0 tbody').on('click', 'tr', function () {
                window.location.href = $(this).data('link');
            })

            //Change status
            $('#buyerStatus a').click(function() {
                status = $(this).data('status');
                buyersTable.DataTable().draw();
            });

            //Search
            $('#dataTablesSearch button').click(function(){
                buyersTable.DataTable().search($('#dataTablesSearch input').val()).draw();
            });

            /*setInterval(function (){
                buyersTable.DataTable().ajax.reload( null, false );
            }, {{--Config::get('test.timer.buyers')*1000--}}); */
        }
    })

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }
</script>
