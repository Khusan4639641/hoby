<script>

    let postalRegionsTable
        status = '';

    $(document).ready(function () {

        /*function locationHashChanged() {
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

            postalRegionsTable.DataTable().draw();
        }*/

        //Data tables init
        if ($('.report-files .report-files-list').length > 0) {

            //window.onhashchange = locationHashChanged;

            reportFilesTable = $('.report-files .report-files-list').dataTable({
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.report-files.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderBy: 'created_at',
                        list_type: 'data_tables',
                        search: reportFilesTable.fnSettings().oPreviousSearch.sSearch,
                        offset: reportFilesTable.fnSettings()._iDisplayStart,
                        limit: reportFilesTable.fnSettings()._iDisplayLength,
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

        // Search
        $('#dataTablesSearch button').click(function(){
            reportFilesTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        })
    })

    // Toggle loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    // Confirm item delete
    function confirmDelete(id) {
        $('#deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    // Delete item
    function destroy() {
        loading(true);

        let id = $('#modalDeleteConfirm #deleteID').val()
        url = '/api/v1/report-files/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                reportFilesTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
