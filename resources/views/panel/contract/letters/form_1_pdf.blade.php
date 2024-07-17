 <style>
     @font-face {
         font-family: 'TNRoman';
         src: url('{{url('/assets/fonts/pdf/times.ttf')}}') format('truetype');
         font-weight: normal;
         font-style: normal;
     }

     @font-face {
         font-family: 'TNRoman';
         src: url('{{url('/assets/fonts/pdf/timesbd.ttf')}}') format('truetype');
         font-weight: bold;
         font-style: normal;
     }
     @font-face {
         font-family: 'TNRoman';
         src: url('{{url('/assets/fonts/pdf/timesbi.ttf')}}') format('truetype');
         font-weight: bold;
         font-style: italic;
     }
     @font-face {
         font-family: 'TNRoman';
         src: url('{{url('/assets/fonts/pdf/timesi.ttf')}}') format('truetype');
         font-weight: normal;
         font-style: italic;
     }
        table th{
            border: none;
            width: 5%;
            padding: 0px;
        }
        table, td {
            border: 1px solid;
            font-family: 'TNRoman';
        }
        table {
            width: 100%;
        }
        body {
            background: white;
            font-size: 10px;
        }
        /*page[size='A4'] {*/
        /*    background: white;*/
        /*    width: 21cm;*/
        /*    height: 29.7cm;*/
        /*    display: block;*/
        /*    margin: 0 auto;*/
        /*    margin-bottom: 0.5cm;*/
        /*    box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);*/
        /*}*/
        /*@media print {*/
        /*    body, page[size='A4'] {*/
        /*        margin: 0;*/
        /*        box-shadow: 0;*/
        /*    }*/
        /*}*/

    </style>
    <table style='border-collapse: collapse;width:18cm'>
     <thead>
     <tr>
         <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
     </tr>
     </thead>
     <tbody>
     <tr>
         <td colspan='5' style="text-align: center;font-weight: bold;"> <strong> ВИД ДОКУМЕНТА</strong></td>
         <td colspan='15' style='text-align: center;font-weight: bold;'>{{$forma->doc_type}}</td>
     </tr>
     <tr>
         <td rowspan='6' colspan='3' id='photo_from_camera'>
             <img src='{{ $forma->photo_from_camera }}'
                  width='150' height='200'/></td>
         <td style='text-align: center;font-weight: bold;' colspan='17' id='names'>{{ $forma->names }}</td>
     </tr>
     <tr>
         <td colspan='3' style='text-align: center;font-weight: bold;'>ПИНФЛ</td>
         <td colspan='4' style='text-align: center;' id='pinfl'>{{ $forma->pinfl }}</td>
         <td colspan='2' style='text-align: center;font-weight: bold;'>ПОЛ</td>
         <td colspan='3' style='text-align: center;' id='gender'>{{ $forma->gender }}</td>
         <td colspan='2' style='text-align: center;font-weight: bold;'>ДАТА РОЖДЕНИЯ</td>
         <td colspan='3' style='text-align: center;' id='birth_date'>{{ $forma->birth_date }}</td>
     </tr>
     <tr>
         <td colspan='3' style='text-align: center;font-weight: bold;'>СЕРИЯ / НОМЕР</td>
         <td colspan='4' style='text-align: center;' id='pass_data'>{{ $forma->pass }}</td>
         <td colspan='2' style='text-align: center;font-weight: bold;'>ДАТА ВЫДАЧИ</td>
         <td colspan='3' style='text-align: center;' id='issued_date'>{{ $forma->issued_date }}</td>
         <td colspan='2' style='text-align: center;font-weight: bold;'>СРОК ДЕЙСТВИЯ</td>
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
         <td colspan='20' style='text-align: center;font-weight: bold;'>МЕСТО РОЖДЕНИЯ</td>
     </tr>
     <tr>
         <td colspan='10' style='text-align: center;font-weight: bold;'>СТРАНА РОЖДЕНИЯ</td>
         <td colspan='10' style='text-align: center;font-weight: bold;'>МЕСТО РОЖДЕНИЯ</td>
     </tr>
     <tr>
         <td colspan='10' style='text-align: center;' id='birth_country'>{{ $forma->birth_country }}</td>
         <td colspan='10' style='text-align: center;' id='birth_region'>{{ $forma->birth_region }}</td>
     </tr>
     <tr>
         <td colspan='20' style='text-align: center;font-weight: bold;'>МЕСТО ПОСТОЯННОГО ЖИТЕЛЬСТВА</td>
     </tr>
     <tr>
         <td colspan='5' style='text-align: center;font-weight: bold;'>СТРАНА</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>ГОРОД</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>РАЙОН</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>УЛИЦА</td>
     </tr>
     <tr>
         <td colspan='5' style='text-align: center;' id='permanent_country'>{{ $forma->permanent_country }}</td>
         <td colspan='5' style='text-align: center;' id='permanent_district'>{{ $forma->permanent_region }}</td>
         <td colspan='5' style='text-align: center;' id='permanent_region'>{{ $forma->permanent_district }}</td>
         <td colspan='5' style='text-align: center;' id='permanent_address'>{{ $forma->permanent_address }}</td>
     </tr>
     <tr>
         <td colspan='20' style='text-align: center;font-weight: bold;'>МЕСТО ВРЕМЕННОГО ЖИТЕЛЬСТВА</td>
     </tr>
     <tr>
         <td colspan='5' style='text-align: center;font-weight: bold;'>СТРАНА</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>ГОРОД</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>РАЙОН</td>
         <td colspan='5' style='text-align: center;font-weight: bold;'>УЛИЦА</td>
     </tr>

     <tr>
         <td colspan='5' style="text-align: center;{{$forma->temporary_country ? '': 'padding:10px;'}}" id='temporary_country'>{{ $forma->temporary_country }}</td>
         <td colspan='5' style='text-align: center;' id='temporary_district'>{{ $forma->temporary_district }}</td>
         <td colspan='5' style='text-align: center;' id='temporary_region'>{{ $forma->temporary_region }}</td>
         <td colspan='5' style='text-align: center;' id='temporary_address'>{{ $forma->temporary_address }}</td>
     </tr>

     </tbody>
 </table>
