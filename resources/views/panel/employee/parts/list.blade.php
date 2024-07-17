<script>


let employeeTable,
    status = '';

$(document).ready(function () {
    var local = `/assets/json/{{app()->getLocale()}}.lang`;

    function locationHashChanged() {
        switch (location.hash) {
            case '#all':
                status = '';
                break;
            case '#active':
                status = 1;
                break;
            case '#inactive':
                status = 0;
                break;
        }

        $(location.hash).tab('show');

        employeeTable.DataTable().draw();
    }

    //Data tables init
    if ($('.employee .employee-list').length > 0) {

        //Change status
        window.onhashchange = locationHashChanged;

        employeeTable = $('.employee .employee-list').dataTable({
            serverSide: true,
            pagingType: "input",
            pageLength: 10,
            info: false,
            lengthChange: false,
            sDom: 'lrtip',
            buttons: [],
            'ajax': function (data, callback, settings) {
                loading(true);
                $.get('{{localeRoute('panel.employees.list')}}', {
                    api_token: Cookies.get('api_token'),
                    name__like: employeeTable.fnSettings().oPreviousSearch.sSearch.split(' '),
                    or__patronymic__like: employeeTable.fnSettings().oPreviousSearch.sSearch.split(' '),
                    or__surname__like: employeeTable.fnSettings().oPreviousSearch.sSearch.split(' '),
                    orderByDesc: 'surname',
                    status: status,
                    list_type: 'data_tables',
                    offset: employeeTable.fnSettings()._iDisplayStart,
                    limit: employeeTable.fnSettings()._iDisplayLength,
                }, function (res) {
                    //res = JSON.parse(res);
                    callback({
                        recordsTotal: res.data.recordsTotal,
                        recordsFiltered: res.data.recordsTotal,
                        data: res.data.data,
                    });
                    loading(false);
                });
            },
            'language': {
                'url': local,
            },
            // success(data){
            //
            // },
            'ordering': false,
            initComplete: function (settings, json) {

            },
        });
    }

    $('#employeeStatus a').click(function () {
        status = $(this).data('status');
        employeeTable.DataTable().draw();
    });

    //Search
    $('#dataTablesSearch button').click(function () {
        employeeTable.DataTable().search($('#dataTablesSearch input').val()).draw();
    });
});


//Show hide loader
function loading(show = false) {
    if (show)
        $('.loading').addClass('active');
    else
        $('.loading').removeClass('active');
}

//Confirm news delete
function confirmDelete(id) {
    $('#deleteID').val(id);
    $('#modalDeleteConfirm').modal('show');
}

// Delete news
function destroy() {
    loading(true);

    let id = $('#modalDeleteConfirm #deleteID').val();
    url = '/api/v1/employees/delete/' + id + '?api_token=' + Cookies.get('api_token');

    axios.get(url).then(response => {
        if (response.data.status === 'success') {
            employeeTable.DataTable().ajax.reload();
            $('#modalDeleteConfirm').modal('hide');
        }
    });
}

//Archive
function activate(id) {
    loading(true);

    let url = '/api/v1/employees/activate/' + id + '?api_token=' + Cookies.get('api_token');

    axios.get(url).then(response => {
        if (response.data.status === 'success') {
            employeeTable.DataTable().ajax.reload();
        }
    });
}

//Archive
function deactivate(id) {
    loading(true);

    let url = '/api/v1/employees/deactivate/' + id + '?api_token=' + Cookies.get('api_token');

    axios.get(url).then(response => {
        if (response.data.status === 'success') {
            employeeTable.DataTable().ajax.reload();
        }
    });
}

</script>
