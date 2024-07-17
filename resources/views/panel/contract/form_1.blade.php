@extends('templates.panel.app')
@section('title', __('panel/contract.anketa_myid'))

@section('content')
    <style>
        table th{
            border: none;
            width: 5%;
            padding: 0px;
        }
        table, td {
            border: 1px solid;
            padding: 10px;
        }
        table {
            width: 100%;
        }
    </style>
  <div id="letter">
    <div class="row">
      <div class="col-9">
        <div class="page-a4 residency">
            <table style='border-collapse: collapse;'>
                <thead>
                <tr>
                    <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>ВИД ДОКУМЕНТА</td>
                    <td colspan='15' style='text-align: center;font-weight: bold'>{{$forma->doc_type}}</td>
                </tr>
                <tr>
                    <td rowspan='6' colspan='3' id='photo_from_camera'>
                        <img src='{{ $forma->photo_from_camera }}'
                             width='100%' alt="No image"/></td>
                    <td style='text-align: center;font-weight: bold;' colspan='17' id='names'>{{ $forma->names }}</td>
                </tr>
                <tr>
                    <td colspan='3' style='font-weight: bold;text-align: center;'>ПИНФЛ</td>
                    <td colspan='4' style='text-align: center;' id='pinfl'>{{ $forma->pinfl }}</td>
                    <td colspan='2' style='font-weight: bold;text-align: center;'>ПОЛ</td>
                    <td colspan='3' style='text-align: center;' id='gender'>{{ $forma->gender }}</td>
                    <td colspan='2' style='font-weight: bold;text-align: center;'>ДАТА РОЖДЕНИЯ</td>
                    <td colspan='3' style='text-align: center;' id='birth_date'>{{ $forma->birth_date }}</td>
                </tr>
                <tr>
                    <td colspan='3' style='text-align: center;font-weight: bold;'>СЕРИЯ / НОМЕР</td>
                    <td colspan='4' style='text-align: center;' id='pass_data'>{{ $forma->pass }}</td>
                    <td colspan='2' style='font-weight: bold;text-align: center;'>ДАТА ВЫДАЧИ</td>
                    <td colspan='3' style='text-align: center;' id='issued_date'>{{ $forma->issued_date }}</td>
                    <td colspan='2' style='font-weight: bold;text-align: center;'>СРОК ДЕЙСТВИЯ</td>
                    <td colspan='3' style='text-align: center;' id='expire_date'>{{ $forma->expire_date }}</td>
                </tr>
                <tr>
                    <td colspan='3' style='text-align: center;font-weight: bold;'>МЕСТО ВЫДАЧИ</td>
                    <td colspan='14' style='text-align: center;' id='issued_by'>{{ $forma->issued_by }}</td>
                </tr>
                <tr>
                    <td colspan='4' style='text-align: center;font-weight: bold;'>ИМЯ</td>
                    <td colspan='5' style='text-align: center;font-weight: bold;'>ФАМИЛИЯ</td>
                    <td colspan='8' style='text-align: center;font-weight: bold;'>ОТЧЕСТВО</td>
                </tr>
                <tr>
                    <td colspan='4' style='text-align: center;' id='first_name'>{{ $forma->first_name }}</td>
                    <td colspan='5' style='text-align: center;' id='last_name'>{{ $forma->last_name }}</td>
                    <td colspan='8' style='text-align: center;' id='middle_name'>{{ $forma->middle_name }}</td>
                </tr>
                <tr>
                    <td colspan='3' style='text-align: center;font-weight: bold;'>НАЦИОНАЛЬНОСТЬ</td>
                    <td colspan='4' style='text-align: center;' id='nationality'>{{ $forma->nationality }}</td>
                    <td colspan='8' style='text-align: center;font-weight: bold;'>ГРАЖДАНСТВО</td>
                    <td colspan='5' style='text-align: center;' id='citizenship'>{{ $forma->citizenship }}</td>
                </tr>
                <tr>
                    <td colspan='20' style='font-weight: bold;text-align: center;'>МЕСТО РОЖДЕНИЯ</td>
                </tr>
                <tr>
                    <td colspan='10' style='font-weight: bold;text-align: center;'>СТРАНА РОЖДЕНИЯ</td>
                    <td colspan='10' style='font-weight: bold;text-align: center;'>МЕСТО РОЖДЕНИЯ</td>
                </tr>
                <tr>
                    <td colspan='10' style='text-align: center;' id='birth_country'>{{ $forma->birth_country }}</td>
                    <td colspan='10' style='text-align: center;' id='birth_region'>{{ $forma->birth_region }}</td>
                </tr>
                <tr>
                    <td colspan='20' style='font-weight: bold;text-align: center;'>МЕСТО ПОСТОЯННОГО ЖИТЕЛЬСТВА</td>
                </tr>
                <tr>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>СТРАНА</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>ГОРОД</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>РАЙОН</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>УЛИЦА</td>
                </tr>
                <tr>
                    <td colspan='5' style='text-align: center;' id='permanent_country'>{{ $forma->permanent_country }}</td>
                    <td colspan='5' style='text-align: center;' id='permanent_district'>{{ $forma->permanent_region }}</td>
                    <td colspan='5' style='text-align: center;' id='permanent_region'>{{ $forma->permanent_district }}</td>
                    <td colspan='5' style='text-align: center;' id='permanent_address'>{{ $forma->permanent_address }}</td>
                </tr>
                <tr>
                    <td colspan='20' style='font-weight: bold;text-align: center;'>МЕСТО ВРЕМЕННОГО ЖИТЕЛЬСТВА</td>
                </tr>
                <tr>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>СТРАНА</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>ГОРОД</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>РАЙОН</td>
                    <td colspan='5' style='font-weight: bold;text-align: center;'>УЛИЦА</td>
                </tr>
                <tr>
                    <td colspan='5' style="text-align: center;{{$forma->temporary_country ? '': 'padding:10px;'}}" id='temporary_country'>{{ $forma->temporary_country }}</td>
                    <td colspan='5' style='text-align: center;' id='temporary_district'>{{ $forma->temporary_district }}</td>
                    <td colspan='5' style='text-align: center;' id='temporary_region'>{{ $forma->temporary_region }}</td>
                    <td colspan='5' style='text-align: center;' id='temporary_address'>{{ $forma->temporary_address }}</td>
                </tr>
                </tbody>

            </table>
        </div>
      </div>

      <div class="col-3">
        <div class="info">
          <div class="d-flex justify-content-end mt-2">
            <button class="btn btn-primary ml-2"  @click="createDocX({{ $myid,  }})">{{__('panel/letters.download')}}DocX</button>
              <button class="btn btn-primary ml-2" @click="createPDF({{ $myid }})">{{__('panel/letters.download')}} PDF</button>
              <button class="btn btn-primary ml-2" @click="printDocument">&check; {{__('app.btn_print')}}</button>
          </div>

        </div>
      </div>
    </div>

  </div>

  @include('panel.contract.parts.letter_config')

@endsection


