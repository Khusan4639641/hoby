@extends('templates.panel.app')
@section('content')
    <style>
        .month{
            border:0.5px black solid;
            display: inline-block;
            padding: 10px;
            border-radius: 5px;
            margin: 5px;
        }
        #dataTable{
            border:1px #cccccc solid;
            padding: 10px;
            margin: 5px 0px;
            border-radius: 5px;
        }
        #dataTableDetail{
            border:1px #cccccc solid;
            padding: 10px;
            margin: 5px 0px;
            border-radius: 5px;
        }
        .checkboxBlock{
            border:1px #cccccc solid;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
        }
        .checkboxBlock input{
            margin-right: 5px;
        }
    </style>
    <form action="{{localeRoute('panel.fakeExport')}}" method="POST" name="transactions" target="_blank" >
        @csrf
        <div class="row">
            <div class="col-sm-2">
                <label for="">Details info</label>
                <label for="" style="display: flex">
                    yes:<input type="radio" checked name="details"  value="yes" class="form-control" />
                    no<input type="radio" name="details"  value="no" class="form-control" />
                </label>
            </div>
            <div class="col">
                <label for="">
                    Год:<br>
                    <input type="number" data-type="year" min="1900" max="2099" step="1" value="2022" class="form-control" />
                </label>
            </div>
            <div class="col">
                <label for="">
                    Месяц:
                    <select data-type='month' id="" class="form-control">
                        <option value="01">Январь</option>
                        <option value="02">Февраль</option>
                        <option value="03">Март</option>
                        <option value="04">Апрель</option>
                        <option value="05">Май</option>
                        <option value="06">Июнь</option>
                        <option value="07">Июль</option>
                        <option value="08">Август</option>
                        <option value="09">Сентябрь</option>
                        <option value="10">Октябрь</option>
                        <option value="11">Ноябрь</option>
                        <option value="12">Декабрь</option>
                    </select>
                </label>
            </div>
            <div class="col-sm-2">
                <label for="">
                    Добавит Дату:<br>
                    <a onclick="addMonth()" style="width: 100%" class="btn btn-outline-success">Add</a>
                </label>
            </div>
        </div>
        <hr>
        <h4>Filter:</h4>
        <hr>
        <div id="month"></div>
        <hr>
        <div class="col-sm-12">
            <br>
            <button onclick="createData()" disabled class="form-control">Generate excel files</button>
        </div>
    </form>

    <script type="text/javascript">
        let form = document.forms['transactions'],
            Month = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            transactions = {
                createDate:[],
            }
        function addMonth() {
            let addDiv =  document.getElementById('month'),
                monthDiv = document.createElement('div'),
                monthText = document.createElement('span'),
                monthButton = document.createElement('button'),
                monthButtonText = document.createTextNode('X'),

                textValue = document.querySelector("input[data-type='year']").value+"-"+document.querySelector("select[data-type='month']").value+"-01",
                date = new Date(textValue),
                lastDate = document.querySelector("input[data-type='year']").value+"-"+
                    document.querySelector("select[data-type='month']").value+"-"+
                    (new Date(date.getFullYear(), date.getMonth() + 1, 0)).getDate(),


                monthTextValue = document.createTextNode(Month[(new Date(textValue).getMonth())]+" "+(new Date(textValue).getFullYear())+" "),

                hiddenInput = document.createElement("input"),
                hiddenInputLast = document.createElement("input")
            hiddenInput.setAttribute('type','hidden')
            hiddenInput.setAttribute('name','data[]')
            hiddenInput.setAttribute('value',textValue)

            hiddenInputLast.setAttribute('type','hidden')
            hiddenInputLast.setAttribute('name','data[]')
            hiddenInputLast.setAttribute('value',lastDate)


            monthDiv.setAttribute('class','month')
            monthDiv.setAttribute('data-value',textValue)
            monthButton.setAttribute('class','btn btn-sm btn-outline-danger')
            monthButton.setAttribute('onclick','deleteMonth(this)')

            if(!textValue) return alert("Select month please!")
            if(transactions.createDate.indexOf(textValue)!==-1) return alert("Exist value!")

            monthButton.appendChild(monthButtonText)
            monthText.appendChild(monthTextValue)
            monthDiv.appendChild(hiddenInput)
            monthDiv.appendChild(hiddenInputLast)
            monthDiv.appendChild(monthText)
            monthDiv.appendChild(monthButton)
            addDiv.appendChild(monthDiv)

            transactions.createDate.push(textValue)
            transactions.createDate.push(lastDate)
            disabledButton()
        }
        function deleteMonth(el){
            if (confirm("Are you sure delete?") === true) {
                el.parentNode.remove()
                let deleteValue = el.parentNode.getAttribute('data-value'),
                    index = transactions.createDate.indexOf(deleteValue)
                if (index > -1) transactions.createDate.splice(index, 2)
                disabledButton()
            }
        }
        disabledButton()
        function disabledButton() {
            console.log(transactions.createDate)
            if(transactions.createDate.length === 0)
            {
                document.querySelector("button[onclick='createData()']").setAttribute('disabled','true')
            }
            else
            {
                document.querySelector("button[onclick='createData()']").removeAttribute('disabled')
            }
        }
    </script>
@endsection
