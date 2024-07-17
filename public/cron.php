<?php 

file_put_contents('shedule.txt','test shedule' . date('d.m H:i:s'));

header ('location: https://test.loc/ru/cron/run');
exit;