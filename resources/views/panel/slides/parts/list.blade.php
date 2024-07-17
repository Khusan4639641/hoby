<script>
    let slidesTable;

    $(document).ready(function () {

                //Data tables init
        if($('.slides .slides-list').length > 0){

            slidesTable = $('.slides .slides-list').dataTable( {
                serverSide: true,
                pageLength: 20,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.slides.list')}}', {
                        api_token: Cookies.get('api_token'),
                        orderBy: 'sort',
                        list_type: 'data_tables',
                        search: slidesTable.fnSettings().oPreviousSearch.sSearch,
                        offset: slidesTable.fnSettings()._iDisplayStart,
                        limit: slidesTable.fnSettings()._iDisplayLength,
                        slider_id: {{$slider->id}}
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
            slidesTable.DataTable().search($('#dataTablesSearch input').val()).draw();
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
        url = '/api/v1/slides/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                slidesTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

</script>
