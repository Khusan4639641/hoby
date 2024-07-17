<script>
    let fieldsTable;

    $(document).ready(function () {

                //Data tables init
        if($('.fields .fields-list').length > 0){

            fieldsTable = $('.fields .fields-list').dataTable( {
                serverSide: true,
                pageLength: 20,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.catalog.fields.list')}}', {
                        api_token: Cookies.get('api_token'),
                        list_type: 'data_tables',
                        title__like: fieldsTable.fnSettings().oPreviousSearch.sSearch,
                        offset: fieldsTable.fnSettings()._iDisplayStart,
                        limit: fieldsTable.fnSettings()._iDisplayLength
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



        //Search
        $('#dataTablesSearch button').click(function(){
            fieldsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
        })
    })


    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    //Confirm slide delete
    function confirmDelete(id) {
        $('#deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    // Delete slides
    function destroy(){
        loading(true);

        let id = $('#modalDeleteConfirm #deleteID').val()
        let url = '/api/v1/catalog/fields/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                fieldsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
