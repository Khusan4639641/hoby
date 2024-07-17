<div style="text-align: center;font-size: 24px;" id="blog">
    <p id="all">Количество всех записи: {{ $size }} </p>
    <p id="done">Количество отработанных записи: 0 </p>
    <p id="excel"></p>
</div>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/axios.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" ></script>
<script type="text/javascript"  src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js" ></script>
<script>
    let summa = 0
    const d = new Date();
    let time = d.getTime();
    function saveXLSX(data,index=1) {
        summa+=data.length
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.json_to_sheet(data);
        ws['!autofilter'] = { ref: "A1:Q1" };
        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
        XLSX.writeFile(wb, "filesHistory_"+time+"_part_"+index+".xlsx");
        document.getElementById("excel").innerHTML = "Подождите пожалуйста генерацию excel файла..."
        document.getElementById("done").innerHTML = "Количество отработанных записи: "+summa
        if(summa===Number({{ $size }})){
            window.close()
        }
    }
</script>
@foreach($FilesHistory as $key => $history)
<script>
    $(document).ready(function(){
        saveXLSX(@json($history),'{{ $key + 1 }}');
    })
</script>
@endforeach
