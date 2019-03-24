<?php

$query = [
        'joins' => [
                'host.address'
            ],
        'attrs' => [
                'host_name',
                'display_name',
                'last_check_result',
            ],
        'filter' => [
                'service.state != service_state && host.acknowledgement != host_ack && host.name == name'
            ],
        'filter_vars' => [
                'service_state' => 'ServiceOK',
                'host_ack'      => 2,
                'name'          => '__HOST-NAME IN ICINGA__'
            ],
    ];

$r = Icinga2_QueryObject(40093, 'services', json_encode($query));
echo print_r($r, true) . PHP_EOL;
