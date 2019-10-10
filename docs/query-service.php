<?php

declare(strict_types=1);

$r = Icinga2_Query4Service(__ID__ /*[Icinga2]*/, '__SERVICE-NAME IN ICINGA__', '__HOST-NAME IN ICINGA__');
if ($r != '') {
    $j = json_decode($r, true);
    $service_name = $j[0]['attrs']['display_name'];
    $output = $j[0]['attrs']['last_check_result']['output'];
    $host_name = $j[0]['joins']['host']['display_name'];
    echo 'service ' . $host_name . '!' . $service_name . ' => ' . $output . PHP_EOL;
}
