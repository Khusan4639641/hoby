<script>

    let accountsTable,
        status = '';

    $(document).ready(function () {

        //Data tables init
        if($('.accounts .accounts-list').length > 0){

            // window.onhashchange = locationHashChanged;

            accountsTable = $('.accounts .accounts-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.monitoring.accounts.list')}}', {
                        api_token: Cookies.get('api_token'),
                        search: accountsTable.fnSettings().oPreviousSearch.sSearch,
                        // orderByDesc: 'surname',
                        // status: status,
                        list_type: 'data_tables',
                        offset: accountsTable.fnSettings()._iDisplayStart,
                        limit: accountsTable.fnSettings()._iDisplayLength,
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
