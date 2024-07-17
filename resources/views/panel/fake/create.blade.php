<table>
    <tbody>
@foreach($transactions['companies'] as $company)
    <tr>
        <td style="color: black;font-weight: bold;width: 400px;height: 50px;">{{ $company }}</td>
        <td style="color: black;font-weight: bold;width: 50px">ед.изм</td>
        @foreach($transactions['dates'] as $date)
            <td style="color: black;font-weight: bold;width: 100px">{{ $date }}</td>
        @endforeach
    </tr>
   @foreach($transactions['types'] as $type)
       <tr>
           <td>{{$type}}</td>
           <td> шт </td>
           @foreach($transactions['dates'] as $date)
               @if(isset($transactions['val'][$date][$company][$type]))
                   <td>{{$transactions['val'][$date][$company][$type]}}</td>
               @else
                   <td>0</td>
               @endif
           @endforeach
       </tr>
   @endforeach
    <tr></tr>
    <tr></tr>
    <tr></tr>
@endforeach
    </tbody>
</table>
