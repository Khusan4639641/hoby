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
        if ($('.postal-regions .postal-regions-list').length > 0) {

            //window.onhashchange = locationHashChanged;

            postalRegionsTable = $('.postal-regions .postal-regions-list').dataTable({
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.postal-regions.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderBy: 'name',
                        list_type: 'data_tables',
                        search: postalRegionsTable.fnSettings().oPreviousSearch.sSearch,
                        offset: postalRegionsTable.fnSettings()._iDisplayStart,
                        limit: postalRegionsTable.fnSettings()._iDisplayLength,
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
            postalRegionsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
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
        url = '/api/v1/postal-regions/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                postalRegionsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
