<?php


//$f = fopen('/cron_payment/logs/cron_1.txt','a');
//fwrite($f,'cron1 start');
$i=0;
echo 'START';
while(true){
  //  fwrite($f,date('Y-m-d H:i:s') . ' CRON_1 ' . $i);

    echo date('Y-m-d H:i:s') . ' CRON_1 ' . $i . "\n";

    $i++;
    if($i>5){
        echo 'sleep';
        sleep(1);
    }
    if($i==10){
        echo '---10---';
        break;
    }
}
echo 'END';

//fclose($f);
