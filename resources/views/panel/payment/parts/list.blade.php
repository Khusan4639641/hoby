<script>
    let paymentTable;

    $(document).ready(function () {
        //Data tables init
        if($('.payment .payment-list').length > 0){

            paymentTable = $('.payment .payment-list').dataTable( {
                serverSide: true,
                pagingType: "input",
                pageLength: 30,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.payments.list')}}', {
                        api_token: Cookies.get('api_token'),
                        search: paymentTable.fnSettings().oPreviousSearch.sSearch,
                        orderByDesc: 'created_at',
                        list_type: 'data_tables',
                        transaction_id: $('#dataTablesSearch input[name="transaction"]').val(),
                        contract_id: $('#dataTablesSearch input[name="contract"]').val(),
                        type: $('#dataTablesSearch select[name="type"]').val(),
                        payment_system: $('#dataTablesSearch select[name="payment_system"]').val(),
                        offset: paymentTable.fnSettings()._iDisplayStart,
                        limit: paymentTable.fnSettings()._iDisplayLength,
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

                }
            } );

            $.fn.DataTable.ext.pager.numbers_length = 21;

            $('#dataTablesSearch button').click(function(){
                paymentTable.DataTable().draw();
            })
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
