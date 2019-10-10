<?php

declare(strict_types=1);

$r = Icinga2_Query4Host(__ID__ /*[Icinga2]*/, '__HOST-NAME IN ICINGA__');
if ($r != '') {
    $j = json_decode($r, true);
    $host_name = $j[0]['attrs']['display_name'];
    $output = $j[0]['attrs']['last_check_result']['output'];
    echo 'host ' . $host_name . ' => ' . $output . PHP_EOL;
}
