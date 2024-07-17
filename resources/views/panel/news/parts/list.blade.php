<script>
    let newsTable
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

            newsTable.DataTable().draw();
        }

        //Data tables init
        if($('.news .news-list').length > 0){

            window.onhashchange = locationHashChanged;

            newsTable = $('.news .news-list').dataTable({
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.news.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderByDesc: 'date',
                        list_type: 'data_tables',
                        search: newsTable.fnSettings().oPreviousSearch.sSearch,
                        status: status,
                        offset: newsTable.fnSettings()._iDisplayStart,
                        limit: newsTable.fnSettings()._iDisplayLength,
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
            });
        }

        //Change status
        $('#newsStatus a').click(function() {
            status = $(this).data('status');
            newsTable.DataTable().draw();
        });

        //Search
        $('#dataTablesSearch button').click(function(){
            newsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        })
    })


    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    //Confirm news delete
    function confirmDelete(id) {
        $('#deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    // Delete news
    function destroy(){
        loading(true);

        let id = $('#modalDeleteConfirm #deleteID').val()
        url = '/api/v1/news/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                newsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

    //Publish
    function publish(id) {
        loading(true);

        url = '/api/v1/news/publish/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                newsTable.DataTable().ajax.reload();
            }
        });
    }

    //Archive
    function archive(id) {
        loading(true);

        url = '/api/v1/news/archive/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                newsTable.DataTable().ajax.reload();
            }
        });
    }

</script>
