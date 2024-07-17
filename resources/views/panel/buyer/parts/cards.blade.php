

<div class="cards list">
    <div >{{__('panel/employee.humo_cards')}}</div>
    <hr>
    <table class="table cards-list hide-balance">
        <thead>

        <tr>
            <th>{{__('panel/employee.owner')}}</th>
            <th>{{__('panel/employee.card_number')}}</th>
            <th>{{__('panel/employee.phone')}}</th>
{{--            <th>{{__('panel/employee.balance')}}</th>--}}
            <th>{{__('panel/employee.type')}}</th>
            <th>{{__('panel/employee.sms_info')}}</th>
            <th>{{__('panel/employee.status')}}</th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
    <hr>

<div >
    <button type="submit" onclick="addCardsHumo()" class="btn btn-primary">{{__('app.btn_add_cards_humo')}}</button>
</div>
<hr>



<div class="modal" id="modalDeleteConfirm" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <input type="hidden" id="deleteID">

            <div class="modal-header">
                <h5 class="modal-title">{{__('panel/employee.header_delete_confirm')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p>{{__('panel/employee.txt_delete_card_confirm')}}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                <button type="submit" onclick="destroy()" class="btn btn-primary">{{__('app.btn_delete')}}</button>
            </div>

        </div>
    </div>
</div>

<div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

<script>
    let cardsTable;
    let buyer_id = {{$buyer->id}};

    $(document).ready(function () {
        //Data tables init
        if($('.cards .cards-list').length > 0){

            cardsTable = $('.cards .cards-list').dataTable( {
                serverSide: true,
                pageLength: 15,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.cards.list')}}', {
                        api_token: Cookies.get('api_token'),
                        user_id: {{$buyer->id}},
                        orderByDesc: 'created_at',
                        list_type: 'data_tables',
                        offset: cardsTable.fnSettings()._iDisplayStart,
                        limit: cardsTable.fnSettings()._iDisplayLength,
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

            $('#dataTablesSearch button').click(function(){
                cardsTable.DataTable().draw();
            })

        }
    });

    //Show hide loader
    function loading(show = false){
        if(show)
            $('.loading').addClass('active');
        else
            $('.loading').removeClass('active');
    }

    //Archive
    function activate(id) {
        loading(true);

        let url = '/api/v1/employee/buyers/activate/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsTable.DataTable().ajax.reload();
            }
        });
    }


    //Archive
    function deactivate(id) {
        loading(true);

        let url = '/api/v1/employee/buyers/deactivate/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsTable.DataTable().ajax.reload();
            }
        });
    }

    // ADD cards Humo
    function addCardsHumo() {
        loading(true);

        let url = '/api/v1/employee/buyers/add-humo/' + buyer_id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            //if (response.data.status === 'success') {
                cardsTable.DataTable().ajax.reload();
            //}
        });
    }

    //Confirm news delete
    function confirmDelete(id) {
        $('#deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    // Delete card
    function destroy(){
        loading(true);

        let id = $('#modalDeleteConfirm #deleteID').val();
        url = '/api/v1/employee/buyers/delete/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }




</script>

