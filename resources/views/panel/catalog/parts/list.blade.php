<script>

    let categoryTable;

    $(document).ready(function () {

        if($('.category-list').length > 0){
            categoryTable = $('.category-list').dataTable( {
                //serverSide: true,
                pageLength: 1000,
                info: false,
                lengthChange: false,
                sDom: "lrtip",
                buttons: [
                ],
                columns: [
                    null,
                    null,
                    {class:'item-button'},
                    {class:'item-button'}
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.catalog.categories.list')}}', {
                        api_token: Cookies.get('api_token'),
                        list_type: 'data_tables',
                        title__like: categoryTable.fnSettings().oPreviousSearch.sSearch,
                        offset: categoryTable.fnSettings()._iDisplayStart,
                        limit: categoryTable.fnSettings()._iDisplayLength,
                    }, function(res) {
                        //res = JSON.parse(res);
                        callback({
                            recordsTotal: res.data.recordsTotal,
                            recordsFiltered: res.data.recordsFiltered,
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
    });

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    //Search
    $('#dataTablesSearch button').click(function(){
        categoryTable.DataTable().search($('#dataTablesSearch input').val()).draw();
    })

    $('#dataTablesSearch input').keyup(function(){
        categoryTable.DataTable().search($('#dataTablesSearch input').val()).draw();
    })

    function confirmDelete(id) {
        $('#modalDeleteConfirm #deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    function destroy(){
        let id = $('#modalDeleteConfirm #deleteID').val(),
            url = '/api/v1/catalog/categories/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            $('.alert', $('.category-list').parent()).remove();
            if (response.data.status === 'success') {
                categoryTable.DataTable().ajax.reload();
            } else {
                response.data.response.message.forEach(element => {
                    let alert = '<div class="alert alert-'+element.type+' alert-dismissible fade show" role="alert">'+
                        element.text +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                        '<span aria-hidden="true">&times;</span>'+
                        '</button>'+
                        '</div>';

                    $('.category-list').before(alert);
                });



            }
            $('#modalDeleteConfirm').modal('hide');
        });
    }

</script>
