<?php

declare(strict_types=1);

$scriptName = IPS_GetName($_IPS['SELF']) . '(' . $_IPS['SELF'] . ')';

$instID = $_IPS['InstanceID'];

$spec = $_IPS['spec'];

$threadCount = 0;
foreach ($threadList as $t => $i) {
    $thread = IPS_GetScriptThread($i);
    $ScriptID = $thread['ScriptID'];
    if ($ScriptID != 0) {
        $threadCount++;
    }
}

$timerCount = 0;
$timer1MinCount = 0;
$timer5MinCount = 0;
$timerList = IPS_GetTimerList();
foreach ($timerList as $t) {
    $timer = IPS_GetTimer($t);
    $next_run = $timer['NextRun'];
    if ($next_run == 0) {
        continue;
    }
    $timerCount++;
    $delay = $next_run - $now;
    if ($delay < 60) {
        $timer1MinCount++;
    } elseif ($delay < 300) {
        $timer5MinCount++;
    }
}

$instanceError = 0;
foreach ($instanceList as $id) {
    $instance = IPS_GetInstance($id);
    $instanceStatus = $instance['InstanceStatus'];
    if ($instanceStatus <= IS_NOTCREATED) {
        continue;
    }
    $instanceError++;
    $loc = IPS_GetLocation($id);
    $this->SendDebug(__FUNCTION__, 'instance=' . $loc . ', status=' . $instanceStatus, 0);
}

$status = 'OK';

$info = 'started ' . date('d.m.Y H:i', $startTime);
$info .= ', threads=' . $threadCount;
$info .= ', timer=' . $timerCount;
if ($instanceError) {
    $info .= ', invalid instances=' . $instanceError;
    $status = 'WARNING';
}

$perfdata = [];
$perfdata['threads'] = $threadCount;
$perfdata['timer'] = $timerCount;
$perfdata['timer_1m'] = $timer1MinCount;
$perfdata['timer_5m'] = $timer5MinCount;

$jret = [
    'status'   => $status,
    'info'     => $info,
    'perfdata' => $perfdata,
];
return json_encode($jret);
