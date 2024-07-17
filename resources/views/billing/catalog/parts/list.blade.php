<script>

    let productsTable;

    $(document).ready(function () {

        if($('.product-list').length > 0){
            productsTable = $('.product-list').dataTable( {
                serverSide: true,
                pageLength: 10,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                columns: [
                    //null,
                    null,
                    null,
                    null,
                    {class:'item-price'},
                    {class:'item-button text-right'},
                    {class:'item-button text-right'},
                    {class:'item-button text-right pr-0'}
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('billing.catalog.products.list')}}', {
                        api_token: Cookies.get('api_token'),
                        list_type: 'data_tables',
                        user_id: {{Auth::user()->id}},
                        title__like: productsTable.fnSettings().oPreviousSearch.sSearch,
                        offset: productsTable.fnSettings()._iDisplayStart,
                        limit: productsTable.fnSettings()._iDisplayLength,
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
        productsTable.DataTable().search($('#dataTablesSearch input').val()).draw();
    })

    function confirmDelete(id) {
        $('#modalDeleteConfirm #deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    function destroy(){
        let id = $('#modalDeleteConfirm #deleteID').val(),
            url = '/api/v1/catalog/products/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                productsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
