<script>
    let paymentsTable;
    //let status = 0;
    let params = {
    };

    function locationHashChanged() {
        $(location.hash).tab('show');
        buildParams(location.hash);
        paymentsTable.DataTable().draw();
    }


    function buildParams(hash = '#current'){
        params = {};
        switch (hash) {
            case "#expired":
                //status = 0;
                params['status'] = 0;
                params['contract|status'] = 4;
                params['payment_date__less'] = '{{date("Y-m-d H:i:s")}}';
                break;
            case "#completed":
                params['status'] = 1;
                ///status = 1;
                break;
            default:
                params['status'] = 0;
                params['contract|status'] = 1;
                break;
        }

        params.api_token = Cookies.get('api_token');
        params.orderBy = 'payment_date';
        params.list_type = 'data_tables';

    }

    $(document).ready(function () {


        window.onhashchange = locationHashChanged;
        buildParams();


        //Data tables init
        if($('.payments .payments-list').length > 0){

            paymentsTable = $('.payments .payments-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                columns: [
                    {className: "item-date"},
                    {className: "item-title"},
                    null,
                    null,
                    null,
                    {className: "item-readmore"},
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);

                    params.search = paymentsTable.fnSettings().oPreviousSearch.sSearch;
                    params.offset = paymentsTable.fnSettings()._iDisplayStart;
                    params.limit = paymentsTable.fnSettings()._iDisplayLength;


                    $.get('{{localeRoute('cabinet.payments.list')}}', params, function(res) {
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

                }
            } );

            locationHashChanged();
        }

        //Change status
        $('#paymentStatus a').click(function() {
            buildParams($(this).data('status'));
            paymentsTable.DataTable().draw();
        });



        //Search
        $('#dataTablesSearch button').click(function(){
            paymentsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
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
