<?php

$scriptName = IPS_GetName($_IPS['SELF']) . '(' . $_IPS['SELF'] . ')';

$instID = $_IPS['InstanceID'];

$type = $_IPS['type'];
$output = $_IPS['output'];
$comment = $_IPS['comment'];
$user = $_IPS['user'];

$state = $_IPS['state'];
switch ($state) {
    case 'DOWN':
    case 'CRITICAL':
         $severity = 'alert';
         $notify = true;
         break;
    case 'WARNING':
        $severity = 'warn';
        $notify = false;
        break;
    case 'UP':
    case 'OK':
    default:
        $severity = 'info';
        $notify = false;
        break;
}

$mode = $_IPS['mode'];
switch ($mode) {
    case 'host':
        $host_name = $_IPS['host_name'];
        $text = 'Host ' . $host_name . ' => ' . $state;
        IPS_LogMessage($scriptName, $text);
        if ($notify) {
            // WFC_PushNotification ...
        }
        break;
    case 'service':
        $host_name = $_IPS['host_name'];
        $service_name = $_IPS['service_name'];
        $text = 'Service ' . $host_name . '!' . $service_name . ' => ' . $state;
        IPS_LogMessage($scriptName, $text);
        if ($notify) {
            // WFC_PushNotification ...
        }
        break;
    default:
        echo json_encode(['status' => 'FAIL', 'info' => 'unknown mode "' . $mode . '"']);
        return;
}

echo json_encode(['status' => 'OK']);
