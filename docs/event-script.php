<?php

require_once "util.php";

$scriptName = IPS_GetName($_IPS['SELF']) . '(' . $_IPS['SELF'] . ')';

$instID = $_IPS['InstanceID'];

$type = $_IPS['type'];
$state = $_IPS['state'];
$attempt = $_IPS['attempt'];

$mode = $_IPS['mode'];
switch ($mode) {
	case 'host':
		$host = $_IPS['host'];
		IPS_LogMessage($scriptName, 'host ' . $host . ': state=' . $state . ', type=' . $type);
		break;
	case 'service':
		$host = $_IPS['host'];
		$service = $_IPS['service'];
		IPS_LogMessage($scriptName, 'service ' . $host . '!' . $service. ': state=' . $state . ', type=' . $type);
		break;
	default:
		echo json_encode([ 'status' => 'FAIL', 'info' => 'unknown mode "' . $mode . '"']);
		return;
}

echo json_encode([ 'status' => 'OK' ]);
