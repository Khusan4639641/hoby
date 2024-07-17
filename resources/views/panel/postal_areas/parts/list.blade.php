<script>

    let postalAreasTable
        status = '';

    $(document).ready(function () {

        //Data tables init
        if ($('.postal-areas .postal-areas-list').length > 0) {

            postalAreasTable = $('.postal-areas .postal-areas-list').dataTable({
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.postal-areas.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderBy: 'name',
                        list_type: 'data_tables',
                        search: postalAreasTable.fnSettings().oPreviousSearch.sSearch,
                        offset: postalAreasTable.fnSettings()._iDisplayStart,
                        limit: postalAreasTable.fnSettings()._iDisplayLength,
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
            postalAreasTable.DataTable().search($('#dataTablesSearch input').val()).draw();
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
        url = '/api/v1/postal-areas/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                postalAreasTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
