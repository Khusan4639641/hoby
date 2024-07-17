<script>
    let affiliatesTable,
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

            affiliatesTable.DataTable().draw();
        }

        if($('.affiliates .affiliates-list').length > 0){

            window.onhashchange = locationHashChanged;

            affiliatesTable = $('.affiliates .affiliates-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                sDom: "lrtip",
                info:false,
                lengthChange :false,
                buttons: [
                ],
                columns: [
                    null,
                    null,
                    null,
                    {className: "item-title"},
                    null,
                    null,
                    {className: "item-readmore"},
                    {className: "item-readmore"},
                ],
                "ajax": function(data, callback, settings) {
                    $.get('{{localeRoute('billing.affiliates.list')}}', {
                        api_token: Cookies.get('api_token'),
                        list_type: 'data_tables',
                        name__like:       affiliatesTable.fnSettings().oPreviousSearch.sSearch,
                        status:     status,
                        offset:     affiliatesTable.fnSettings()._iDisplayStart,
                        limit:      affiliatesTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        //res = JSON.parse(res);
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsTotal,
                            data: res.data.data
                        });
                    });
                },
                "language": {
                    "url": "/assets/json/ru.lang"
                },
                "ordering": false,
                initComplete: function ( settings, json) {

                },
                "createdRow": function( row, data, dataIndex ) {
                   $(row).attr( 'data-link', data[8] );
                }
            } );
        }

        //Change status
        $('#affiliateStatus a').click(function() {
            status = $(this).data('status');
            affiliatesTable.DataTable().draw();
        });

        //Search
        $('#dataTablesSearch button').click(function(){
            affiliatesTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        })


        $('#DataTables_Table_0 tbody').on('click', 'tr', function () {
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
