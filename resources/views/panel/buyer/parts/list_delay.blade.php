<script>
let buyersTable,
    status = '',
    isOrder = false;

let buyers = []
switch (location.hash) {
    case '#all':
        status = '';
        break;
    case '#verified':
        $('#verified').addClass('active');
        status = 4;
        break;
    case '#photo':
        $('#photo').addClass('active');
        status = 5;
        break;
    case '#denied':
        $('#denied').addClass('active');
        status = 8;
        break;
    case '#not-verified':
        $('#not-verified').addClass('active');
        status = 2;
        break;
}
$(document).ready(function () {
    let $table = $('.buyers .buyers-list');
    const buyerIds = [];

    const div = document.createElement('div');
    const img = document.createElement('img');
    img.setAttribute('src', "{{asset('images/media/loader.svg')}}");

    div.classList.add('loading');
    div.appendChild(img);

    $table.prepend(div);

    function locationHashChanged() {
        switch (location.hash) {
            case '#all':
                status = '';
                break;
            case '#verified':
                status = 4;
                break;
            case '#photo':
                status = 5;
                break;
            case '#denied':
                status = 8;
                break;
            case '#not-verified':
                status = 2;
                break;
        }

        $(location.hash).tab('show');

        buyersTable.DataTable().draw();
    }


    function format(buyer) {
        return '<div class="card" style="margin: 0 50px">' + '<table cellpadding="5" width="100%" cellspacing="0">'
            + '<tr style="font-weight: bold; padding: 0; background-image: unset;">'
            + '<td>номер карты</td>'
            + '<td>владелец</td>'
            + '<td>тип карты</td>'
            // +'<td>SMS Info</td>'
            + '<td>доступность</td>'
            + '<td></td>'
            + '</tr>' +

            buyer.cards_inactive.map(card => {
                return `<tr style="background-image: unset;">
                        <td>${card.public_number || '-----'}</td>
                        <td>${card.public_card_name || '-----'}</td>
                        <td>${card.type}</td>
                        {{--<td>
                          ${ card.sms_info == 0 ? 'ON' : 'OFF' }
                        </td>--}}
                <td>${card.status == 1 ? 'активная' : 'не активная'}</td>
                        <td id="${card.id}">
                          <button onclick="changeStatus(this, '${card.id}')" style="color: ${card.status == 0 ? 'blue' : 'red'}" class="btn btn-sm btn-archive">${card.status == 0 ? 'включить' : 'выключить'}</button>
                        </td>
                      </tr>`;
            }) +
            '</table>' + '</div>';
    }

    // открывает по дефолту все карты - окт
    function collapseAllRows(settings) {
        const api = new $.fn.dataTable.Api(settings);

        api.rows().every(async function () {
            const tr = $(this.node());

            const buyer = this.data();

            buyerIds.push(buyer.id);
            buyers.push(buyer.id)
            // await addCardsHumoById(buyer.id);

            this.child(format(buyer)).show();
            tr.addClass('shown');
        });
    }

    if ($table.length > 0) {


        window.onhashchange = locationHashChanged;

        buyersTable = $table.dataTable({
            draw: 1,
            serverSide: true,
            pageLength: 10,
            pagingType: 'input',
            lengthChange: false,
            sDom: 'lrtip',
            buttons: [],
            columns: [

                {
                    'className': 'dt-control',
                    'orderable': false,
                    'data': null,
                    'defaultContent': '',
                },
                { 'data': 'status_caption' },
                { 'data': 'id' },
                {
                    'data': (row) => `${row.name} ${row.surname} ${row.patronymic}`,
                },
                { 'data': 'phone' },
                { 'data': row => Intl.NumberFormat().format(row.totalDebt) },
            ],
            'ajax': function (data, callback, settings) {

                const formData = {
                    api_token: Cookies.get('api_token'),
                    //orderByDesc: 'id',
                    // list_type: 'data_tables',
                    search: $('#dataTablesSearch input.search-phone').val(),
                    searchID: $('#dataTablesSearch input.search-id').val(),
                    status: status,
                    offset: buyersTable.fnSettings()._iDisplayStart,
                    limit: buyersTable.fnSettings()._iDisplayLength,
                    userType: 'buyer',
                };

                loading(true);
                $.get('{{localeRoute('panel.buyers.delay')}}', formData, function (res) {
                    let recordsTotal = res.response.total
                    callback({
                        recordsTotal: recordsTotal,
                        recordsFiltered: recordsTotal,
                        data: res.data,
                    });

                    loading(false);
                })
                    .then(() => {
                        collapseAllRows(settings);
                    })
                    .then(() => {
                        console.log('humo cards api sending ....');
                        loading(true);
                        const promises = [];
                        // buyerIds.forEach(buyer_id => {
                        //     let url = '/api/v1/employee/buyers/add-humo/' + buyer_id + '?api_token=' + Cookies.get('api_token');
                        //     promises.push(axios.get(url));
                        // });
                        Promise.all(promises)
                            .then(() => {
                                console.log('all humo cards api sent!');
                                loading(false);
                                $('html, body').animate({ scrollTop: 0 }, 500);
                            })
                            .catch(e => {
                                $('#error-alert').css('display', 'block').text('При отправлений запросов возникла ошибка!')
                                loading(false);
                                $('html, body').animate({ scrollTop: 0 }, 500);
                            });
                    })
                    .catch(e => {
                        $('#error-alert').css('display', 'block').text(e.message)
                        loading(false);
                        $('html, body').animate({ scrollTop: 0 }, 500);
                    });
            },
            'language': {
                'url': '/assets/json/ru.lang',
            },
            'ordering': false,
            // initComplete: function (settings, json) {
            //     collapseAllRows(settings);
            // },
        });

        {{--}}setInterval(function () {
            buyersTable.DataTable().ajax.reload(null, false);
        }, {{Config::get('test.timer.buyers')*1000}});*/--}}
    }

    $('.buyers .buyers-list tbody').on('click', 'td.dt-control', function () {
        const tr = $(this).closest('tr');
        const row = buyersTable.DataTable().row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });

    //Change status
    $('#buyerStatus a').click(function () {
        status = $(this).data('status');
        isOrder = $(this).data('order') || false;
        buyersTable.DataTable().draw();
    });

    //Search
    $('#dataTablesSearch button').click(function () {
        buyersTable.DataTable().search('').draw();
    });

    //filter by phone and clear
    $('#dataTablesSearch input.search-phone').keyup((event) => {
        if (event.keyCode === 13 || event.keyCode === 27) {
            buyersTable.DataTable().draw();
        }
    });

    //filter by id and clear
    $('#dataTablesSearch input.search-id').keyup((event) => {
        if (event.keyCode === 13 || event.keyCode === 27) {
            buyersTable.DataTable().draw();
        }
    });

    /*$('.buyers .buyers-list').on('click', 'tbody tr', function () {
        window.location.href = $(this).find('a').attr('href');
    });*/
});

let cardsTable;

//Archive

function changeStatus(e, id) {
    const $button = $(e);
    $button.text('Выполняется...');

    let url = '/api/v1/employee/buyers/change-status/' + id + '?api_token=' + Cookies.get('api_token');

    axios.get(url).then(response => {
        if (response.data.status === 'success') {

            $button.css({
                color: '#0FBE7B',
                fontWeight: 'bold',
            });
            $button.text('Изменено!');
            // buyersTable.DataTable().draw();
            // alert(response.data.status);
            // window.location.reload();
        } else {
            $button.css('color', 'red');
            $button.text('Ошыбка!');
            alert(response.data.data.message);
        }
    });
}

function refreshDataTable() {
    buyersTable.DataTable().draw();
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
// ADD cards Humo to ALL
function addCardsHumo() {
    if (buyers.length>10) buyers = buyers.slice(buyers.length-10,buyers.length)
    if(buyers.length>0) {
        loading(true);
        recFunctionAddCardHumo()
    }
}
function recFunctionAddCardHumo($num = 0) {
    let pageNumber = buyersTable.DataTable().page.info().page
    if($num < buyers.length) {
        let url = '/api/v1/employee/buyers/add-humo/' + buyers[$num] + '?api_token=' + Cookies.get('api_token');
        axios.get(url).then(response => {});
        $num++
        return recFunctionAddCardHumo($num)
    }else {
        buyersTable.DataTable().draw()
        setTimeout(function (){
            buyersTable.DataTable().page(pageNumber).draw(false);
        },1000)
    }

}
// ADD cards Humo by ID
function addCardsHumoById(buyer_id) {
    loading(true);
    let url = '/api/v1/employee/buyers/add-humo/' + buyer_id + '?api_token=' + Cookies.get('api_token');

    axios.get(url).then(response => {
        //console.log(response.data);
        if (response.data.status === 'success') {
            console.log(buyer_id);
            //cardsTable.DataTable().ajax.reload();
        }
    });
    //window.location.reload();
}

//Confirm news delete
function confirmDelete(id) {
    $('#deleteID').val(id);
    $('#modalDeleteConfirm').modal('show');
}

// Delete card
function destroy() {
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

// $('.show').click(function () {
//     if($(this).has('hide')) {
//         $(this).find('.icon').toggleClass('open');
//         $(this).parent().find('tr.'+$(this).data('item')).toggleClass('hide');
//     }
// })


//Show hide loader
function loading(show = false) {
    if (show)
        $('.loading').addClass('active');
    else
        $('.loading').removeClass('active');
}

</script>
