<script>
    let financeTable;
    $(document).ready(function () {
        if ($('.finances .finances-list').length > 0) {
            financeTable = $('.finances .finances-list').dataTable({
                serverSide: true,
                pageLength: 15,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [],
                "ajax": function (data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.finances.list')}}', {
                        api_token: '{{Auth::user()->api_token}}',
                        list_type: 'data_tables',
                        search: financeTable.fnSettings().oPreviousSearch.sSearch,
                        //status: status,
                        offset: financeTable.fnSettings()._iDisplayStart,
                        limit: financeTable.fnSettings()._iDisplayLength,
                    }, function (res) {
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
                initComplete: function (settings, json) {
                }
            });
        }



        //Search
        /*$('#dataTablesSearch .search-text .input-icon').click(function () {
            financeTable.DataTable().search($('#dataTablesSearch .search-text input').val()).draw();
        });*/


        var search = new Vue({
            el: '#dataTablesSearch',
            data: {
                loading: false,
                search: {
                    date: '',
                    title: ''
                }
            },
            methods: {
                updateList() {

                    if(!this.search.date[0])
                        this.search.date = '';

                    if (!this.loading) {
                        this.loading = true;
                        financeTable.DataTable().search(JSON.stringify(this.search)).draw();
                        this.loading = false;
                    }
                }
            }
        })

    });

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

</script>
