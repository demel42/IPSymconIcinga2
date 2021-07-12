#!/usr/bin/env php
<?php

define('STATE_OK', 0);
define('STATE_WARNING', 1);
define('STATE_CRITICAL', 2);
define('STATE_UNKNOWN', 3);

$opts_s = '';
$opts_l = [
    'ipsymcon_host:',
    'ipsymcon_port:',
    'https',
    'webhook_user:',
    'webhook_password:',
    'mode:',
    'spec:',
];

$options = getopt($opts_s, $opts_l);

$ipsymcon_host = isset($options['ipsymcon_host']) ? $options['ipsymcon_host'] : '';
if ($ipsymcon_host == '') {
    echo 'UNKNOWN - missing ipsymcon_host' . PHP_EOL;
    exit(STATE_UNKNOWN);
}

$ipsymcon_port = isset($options['ipsymcon_port']) ? $options['ipsymcon_port'] : 3777;

$webhook_user = isset($options['webhook_user']) ? $options['webhook_user'] : '';
$webhook_password = isset($options['webhook_password']) ? $options['webhook_password'] : '';

$mode = isset($options['mode']) ? $options['mode'] : '';
if ($mode == '') {
    echo 'UNKNOWN - missing mode' . PHP_EOL;
    exit(STATE_UNKNOWN);
}

$postdata = $options;
$postdata['proc'] = 'check';

$url = (isset($options['https']) && $options['https'] ? 'https' : 'http') . '://' . $ipsymcon_host . ':' . $ipsymcon_port . '/hook/Icinga2';

$header = ['Accept: application/json; charset=utf-8'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
if ($webhook_user != '' && $webhook_password != '') {
    curl_setopt($ch, CURLOPT_USERPWD, $webhook_user . ':' . $webhook_password);
}
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$cdata = curl_exec($ch);
$cerrno = curl_errno($ch);
$cerror = $cerrno ? curl_error($ch) : '';
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$err = '';
if ($cerrno) {
    $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
} elseif ($httpcode != 200) {
    if ($httpcode == 403) {
        $err = 'got http-code ' . $httpcode . ' (forbidden)';
    } elseif ($httpcode >= 500 && $httpcode <= 599) {
        $err = 'got http-code ' . $httpcode . ' (server error)';
    } else {
        $err = "got http-code $httpcode";
    }
} else {
    $result = json_decode($cdata, true);
    if ($result == '') {
        $err = 'malformed response';
    } else {
        $jdata = json_decode($cdata, true);
        if (!isset($jdata['status'])) {
            $err = 'malformed data';
        }
    }
}

if ($err != '') {
    $status = 'ERROR';
    $statuscode = STATE_WARNING;

    $info = $err;
} else {
    $status = $jdata['status'];
    switch ($status) {
        case 'OK':
            $statuscode = STATE_OK;
            break;
        case 'WARNING':
            $statuscode = STATE_WARNING;
            break;
        case 'CRITICAL':
            $statuscode = STATE_CRITICAL;
            break;
        default:
            $status = 'UNKNOWN';
            $statuscode = STATE_UNKNOWN;
            break;
    }

    $info = isset($jdata['info']) ? $jdata['info'] : '';
}
$ret = $status . ' - ' . $info;

$perfdata = isset($jdata['perfdata']) ? $jdata['perfdata'] : '';
if ($perfdata != '') {
    $perf = '';
    foreach ($perfdata as $var => $val) {
        if ($perf != '') {
            $perf .= ' ';
        }
        $perf .= $var . '=' . $val;
    }
    $ret .= ' | ' . $perf;
}

echo $ret . PHP_EOL;
exit($statuscode);
