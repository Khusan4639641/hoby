
<div class="cards_pnfl list">
    <div >{{__('panel/employee.uzcard_cards')}}</div>
    <hr>
    <table class="table cards_pnfl-list">
        <thead>

        <tr>

            <th>{{__('panel/employee.owner')}}</th>
            <th>{{__('panel/employee.card_number')}}</th>
            <th>{{__('panel/employee.phone')}}</th>
            <th>{{__('panel/employee.balance')}}</th>
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
    <button type="submit" onclick="addCards()" class="btn btn-primary">{{__('app.btn_add_cards_uzcard')}}</button>
</div>
<hr>


<div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

<script>
    let cardsPnflTable;


    $(document).ready(function () {
        //alert(buyer_id);
        //Data tables init
        if($('.cards_pnfl .cards_pnfl-list').length > 0){

            cardsPnflTable = $('.cards_pnfl .cards_pnfl-list').dataTable( {
                serverSide: true,
                pageLength: 15,
                info:false,
                lengthChange :false,
                sDom: "lrtip",
                buttons: [
                ],
                "ajax": function(data, callback, settings) {
                    loading(true);
                    $.get('{{localeRoute('panel.cards-pnfl.list')}}', {
                        api_token: Cookies.get('api_token'),
                        user_id: {{$buyer->id}},
                        orderByDesc: 'created_at',
                        list_type: 'data_tables',
                        offset: cardsPnflTable.fnSettings()._iDisplayStart,
                        limit: cardsPnflTable.fnSettings()._iDisplayLength,
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
                cardsPnflTable.DataTable().draw();
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
    function activatePnflCard(id) {
        loading(true);

        let url = '/api/v1/employee/buyers/activatePnflCard/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsPnflTable.DataTable().ajax.reload();
            }
        });
    }


    //Archive
    function deactivatePnflCard(id) {
        loading(true);

        let url = '/api/v1/employee/buyers/deactivatePnflCard/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsPnflTable.DataTable().ajax.reload();
            }
        });
    }

    //Confirm news delete
    function confirmDeletePnflCard(id) {
        $('#deleteID').val(id);
        $('#modalDeleteConfirm').modal('show');
    }

    // Delete card
    function destroyPnflCard(){
        loading(true);

        let id = $('#modalDeleteConfirm #deleteID').val();
        url = '/api/v1/employee/buyers/deletePnflCard/' + id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            if (response.data.status === 'success') {
                cardsTable.DataTable().ajax.reload();
                $('#modalDeleteConfirm').modal('hide');
            }
        });
    }

    // ADD cards uzcard
    function addCards() {
        loading(true);

        let url = '/api/v1/employee/buyers/add-uzcard-pnfl/' + buyer_id + '?api_token=' + Cookies.get('api_token');

        axios.get(url).then(response => {
            console.log(response.data);
            if (response.data.status === 'success') {

            }else{
                if (response.data.status === 'error') {
                    alert(response.data.message);
                    location.reload();
                }

            }
            cardsPnflTable.DataTable().ajax.reload();

        });
    }

    // ADD cards HUMO by phone
    function addCardsPhone(card_phone) {
        loading(true);
        let url = '/api/v1/employee/buyers/add-humo/' + buyer_id + '?api_token=' + Cookies.get('api_token');

        axios.post(url, {
            card_phone: card_phone,
        }).then(response => {
            if (response.data.status === 'success') {
                alert('карты HUMO с номером '+card_phone+' успешно загружены');
                location.reload();
            }else{
                if (response.data.status === 'error') {
                    alert(response.data.message);
                    location.reload();
                }
            }
            cardsPnflTable.DataTable().ajax.reload();
        });
    }



</script>

