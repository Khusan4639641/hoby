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
    <form onsubmit="return false;" name="transactions" >
        <h3>Progress: <span id="progress">0</span> %</h3>
        <div class="row">
            <div class="col-sm-3">
                <label for="">Имя компания
                    <select  id="" name="company" class="select2 form-control" required>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name_ru }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            {{--      type: identifing, datafinding и registrating      --}}
            <div class="col-sm-5">
                <label for="">Тип транзакции: </label>
                <div class="checkboxBlock">
                    <div>
                        <label for="">
                            <input type="checkbox" checked name="transaction['type'][0]" value="identifing">Identifing
                        </label>
                        <input type="number" name="transaction['num'][0]" value="0" min="0" class="form-control">
                    </div>
                    <div>
                        <label for="">
                            <input type="checkbox" checked name="transaction['type'][1]" value="datafinding">Datafinding
                        </label>
                        <input type="number" name="transaction['num'][1]" value="0" min="0" class="form-control">
                    </div>
                    <div>
                        <label for="">
                            <input type="checkbox" checked name="transaction['type'][2]" value="registrating">Registrating
                        </label>
                        <input type="number" name="transaction['num'][2]" value="0" min="0" class="form-control">
                    </div>
                </div>
            </div>
            <div class="col">
                <label for="">
                    Год:<br>
                    <input type="number" name="year" min="1900" max="2099" step="1" value="2022" class="form-control" />
                </label>
            </div>
            <div class="col">
                <label for="">
                    Месяц:
                    <select name="month" id="" class="form-control">
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
            <div class="col-sm-12">
                <br>
                <button onclick="createData()" class="form-control">Generate</button>
            </div>
        </div>
    </form>
    <hr>
    <div class="row">
        <div class="col-12">
            <button class="btn btn-outline-success" onclick="ajaxTable.sendData()">Save all to DB</button>
            <a target="_blank" class="btn btn-outline-info" href="{{localeRoute('panel.fake.import.transaction')}}" >Go to excel-export page</a>
        </div>
    </div>
    <hr>
    <h5>Details info</h5>
    <div id="dataTableDetail"></div>
    <hr>
    <script>
        let form = document.forms['transactions'],
            Types = {
                identifing: "Удаленная идентификация и оценка платежеспособности потенциальных Клиентов на основе данных ГНК, КБ «КИАЦ», Платежных систем и НИББД",
                datafinding: "Автоматизированное распознавание данных в идентифицирующих документах потенциальных Клиентов",
                registrating: "Регистрация и последующее сопровождение договора рассрочки в системе биллинга"
            },

            Month = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],

            transactions = {
                type:[],
                counts:[],
                createDate:[],
                companyId:[],
                companyNames:[],
                companyName:"",
            },
            transactionDetails = [],
            transactionOptionDetails = [],
            chunkSize = 0
        function createData(){
            if(transactions.companyName.length===0){
                ajaxTable.restartValues()
                transactions.companyName = ajaxTable.getCompanyName(form.elements['company'],form.elements['company'].value)
                transactions.companyNames.push(ajaxTable.getCompanyName(form.elements['company'],form.elements['company'].value))
                for(let $i=0;$i<=2;$i++){
                    if(form.elements["transaction['type']["+$i+"]"].checked) {
                        if(form.elements["transaction['num'][" + $i + "]"].value!=0 && form.elements["transaction['num'][" + $i + "]"].value!='') {
                            transactions.type.push(form.elements["transaction['type'][" + $i + "]"].value)
                            transactions.counts.push(form.elements["transaction['num'][" + $i + "]"].value)
                        }else{
                            document.querySelector("input[name=\"transaction['num']["+$i+"]\"]").setAttribute('class',"form-control is-invalid")
                            location.reload()
                        }

                    }
                }
                let createDate = Table.getDates(form.elements['year'].value,form.elements['month'].value)
                Table.v1(createDate[0], createDate[1])
                chunkSize = getDays(createDate[0], createDate[1])
                document.querySelector("button[onclick='createData()']").setAttribute('disabled','true')
                document.querySelectorAll("input[type='number'],select").forEach(function (input){
                    input.setAttribute('readonly',true)
                })
            }else{
                alert("You are allredy generate!")
            }
        }

        let ajaxTable = {
            restartValues: function () {
                transactions.type = []
                transactions.counts = []
                transactions.company = ''
            },
            sendData: function () {
                if (confirm("Are you sure save?") === true) {
                    if(transactions.counts.length>0)
                        sendData.chunk(transactionOptionDetails, chunkSize)
                    else
                        alert("Please first Generate date!")
                }
            },
            getCompanyName: function (obj, id) {
                let result = []
                obj.textContent.split("\n").forEach(function (el) {
                    let companyName
                    if((companyName = el.replace(/\s+/g," "))!=="")
                        result.push(companyName)
                })
                return result[Number(id)-1]
            },
            creatData: function ($header = "", $types = [], $month = [], $companyId) {
                let  typeIndex = 0
                $types.forEach(function ($type) {
                    let indexRandom = 0
                    transactionDetails.push([
                        $type,
                        $month,
                        transactions.counts[typeIndex],
                        $header,
                        $companyId
                    ])
                    indexRandom++
                    typeIndex++
                })
            },
            createDataDetail: function () {
                //Clear details Place
                document.getElementById('dataTableDetail').innerHTML = ""

                let addDiv = document.getElementById('dataTableDetail')

                let div = document.createElement('div')
                div.setAttribute('class','data')

                let table = document.createElement('table')
                table.setAttribute('class','table')

                let tr_1 = document.createElement('tr')

                let th = document.createElement('th')
                let th_text = document.createTextNode("№")
                th.appendChild(th_text)
                tr_1.appendChild(th)

                let th2 = document.createElement('th')
                let th2_text = document.createTextNode("Дата")
                th2.appendChild(th2_text)
                tr_1.appendChild(th2)

                let th3 = document.createElement('th')
                let th3_text = document.createTextNode("Тип")
                th3.appendChild(th3_text)
                tr_1.appendChild(th3)

                let th4 = document.createElement('th')
                let th4_text = document.createTextNode("Компания")
                th4.appendChild(th4_text)
                tr_1.appendChild(th4)

                let th5 = document.createElement('th')
                let th5_text = document.createTextNode("Количество тронзакции")
                th5.appendChild(th5_text)
                tr_1.appendChild(th5)

                table.appendChild(tr_1)
                addDiv.appendChild(table)
                let numberRows = 1

                let td_data = transactionDetails[0][1][0]
                let td_data2 = transactionDetails[0][1][1]
                let days = getDays(td_data,td_data2)
                let DaysX = randomData(days)
                transactionDetails.forEach(function (elements) {
                    let td_type = elements[0]

                    let td_count = elements[2]
                    let td_company = elements[3]
                    let td_companyId = elements[4], $sumType = 0

                    for (let $i=0;$i<days;$i++){
                        let tr = document.createElement('tr'),
                            td = document.createElement('td'),
                            td_text = document.createTextNode(numberRows)

                        td.appendChild(td_text)
                        td.setAttribute('style','width:100px;')
                        tr.appendChild(td)
                        let tdData = document.createElement('td'),
                            tdData_text = document.createTextNode(nextDay(td_data,$i)),

                            tdType = document.createElement('td'),
                            tdType_text = document.createTextNode(Types[td_type]),

                            tdCompany = document.createElement('td'),
                            tdCompany_text = document.createTextNode(td_company),

                            tdCount = document.createElement('td'), tdCount_text
                        if($i===days-1)
                            tdCount_text = document.createTextNode(td_count - $sumType)
                        else
                            tdCount_text = document.createTextNode(Math.floor(((td_count)/summa(DaysX))*DaysX[$i]))

                        tdType.setAttribute('style','width:300px;')

                        tdData.appendChild(tdData_text)
                        tdType.appendChild(tdType_text)
                        tdCompany.appendChild(tdCompany_text)
                        tdCount.appendChild(tdCount_text)

                        tr.appendChild(tdData)
                        tr.appendChild(tdType)
                        tr.appendChild(tdCompany)
                        tr.appendChild(tdCount)
                        table.appendChild(tr)

                        if($i===days-1)
                            transactionOptionDetails.push({
                                amount: (td_count - $sumType),
                                rowId:numberRows,
                                type: td_type,
                                dates: nextDay(td_data, $i),
                                companyId: Number(td_companyId)
                            })
                        else {
                            transactionOptionDetails.push({
                                amount: Math.floor(((td_count) / summa(DaysX)) * DaysX[$i]),
                                rowId:numberRows,
                                type: td_type,
                                dates: nextDay(td_data, $i),
                                companyId: Number(td_companyId)
                            })

                            $sumType += Math.floor(((td_count) / summa(DaysX)) * DaysX[$i])
                        }
                        numberRows++
                    }
                })
            }
        }
        function randomAllData($data) {
            let result = []
            let $i=1
            while ($i<=$data.length){
                let number = getRndInteger(1,$data.length),
                    index = result.indexOf(number)

                $i++

                if (index > -1) $i--
                else result.push(number)
            }
            return result
        }
        function randomData($number) {
            let result = []
            let $i=1
            while ($i<=$number){
                let number = getRndInteger(1,$number),
                    index = result.indexOf(number)

                $i++

                if (index > -1) $i--
                else result.push(number)
            }
            return result
        }
        function getRndInteger(min, max) {
            return Math.floor(Math.random() * (max - min + 1) ) + min;
        }
        function summa($array) {
            let result = 0
            $array.forEach(function (el){
                result+=el
            })
            return result
        }
        function getDays($from, $to) {
            let date1 = new Date($from),
                date2 = new Date($to),
                diffTime = Math.abs(date2 - date1)+1
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }
        function getMonthText($from, $to) {
            let from  = new Date($from), to  = new Date($to), result = []

            for(let $i = from.getMonth(); $i <= to.getMonth(); $i++) {
                if( $i === 0 ) Month[$i] = $from.split("-")[2] + " " + Month[$i]
                else if( $i === to.getMonth() ) Month[$i] = $to.split("-")[2]  + " " + Month[$i]
                result.push(Month[$i])
            }
            return result
        }
        function nextDay($date, $day=0) {
            let date = new Date($date);
            date.setDate(date.getDate() + $day);
            return date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate();
        }
        let sendData = {
            progress:0,
            optData: [],
            chunk: function ($data, chunkSize) {
                let check = 0,
                    $steps = 0
                if($data.length> chunkSize)
                    $steps = Number($data.length/chunkSize)
                while (check < $steps) {
                    let result = []
                    for(let $i=check*chunkSize; $i<check*chunkSize+chunkSize; $i++) if($data[$i]) result.push($data[$i])
                    check++
                    sendData.optData.push(result)
                }
                sendData.recFunc()
                document.querySelector("button[class='btn btn-outline-success']").setAttribute('disabled','true')
            },
            recFunc: function ($step = 0) {
                if($step<this.optData.length){
                    const formData = {
                        api_token: Cookies.get('api_token'),
                        data: this.optData[$step],
                        firstDate: this.optData[$step][0].dates,
                        lastDate: this.optData[$step][this.optData[$step].length-1].dates
                    };
                    $.post('{{localeRoute('fake.transaction.send.data')}}', formData, function (res) {
                        sendData.progress += sendData.optData[$step].length
                        document.getElementById("progress").innerHTML = Math.floor((sendData.progress/transactionOptionDetails.length)*100)
                        $step++
                        if(res) return sendData.recFunc($step)
                    })
                }else{
                    document.getElementById('dataTableDetail').innerHTML = ""
                    alert("Successfully finished!")
                    location.reload()
                }
            },

        }
        let Table = {
            v1: function ($from, $to) {
                ajaxTable.creatData(transactions.companyName, transactions.type, [$from,$to], form.elements['company'].value)
                ajaxTable.createDataDetail()
            },
            getDates: function ($year, $month) {
                let cerateDate =  $year + "-" + $month+ "-" +"01"
                let date = new Date(cerateDate);
                let lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
                return [ cerateDate, $year + "-" + $month+ "-" +lastDay.getDate() ]
            }
        }
    </script>
@endsection



