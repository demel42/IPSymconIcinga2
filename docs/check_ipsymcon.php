#!/usr/bin/env php
<?php

define('STATE_OK', 0);
define('STATE_WARNING', 1);
define('STATE_CRITICAL', 2);
define('STATE_UNKNOWN', 3);

$opts_s = '';
$opts_l = [
        'host:',
        'port:',
        'https',
        'user:',
        'password:',
        'mode:',
        'spec:',
    ];

$options = getopt($opts_s, $opts_l);

$host = isset($options['host']) ? $options['host'] : '';
if ($host == '') {
    echo 'UNKNOWN - missing host' . PHP_EOL;
    exit(STATE_UNKOWN);
}

$port = isset($options['port']) ? $options['port'] : 3777;

$user = isset($options['user']) ? $options['user'] : '';
$password = isset($options['password']) ? $options['password'] : '';

$mode = isset($options['mode']) ? $options['mode'] : '';
if ($mode == '') {
    echo 'UNKNOWN - missing mode' . PHP_EOL;
    exit(STATE_UNKOWN);
}

$postdata = [];
$postdata['mode'] = $mode;
if (isset($options['spec']))
	$postdata['spec'] = $options['spec'];

$url = (isset($options['https']) && $options['https'] ? 'https' : 'http') . '://' . $host . ':' . $port . '/hook/Icinga2';

$header = ['Accept: application/json; charset=utf-8'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
if ($user != '' && $password != '') {
    curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
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
    echo 'ERROR - ' . $err . PHP_EOL;
	echo '        ' . $cdata . PHP_EOL;
    exit(STATE_UNKNOWN);
}

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
        brewk;
}

$ret = $status . ' - ';

$info = isset($jdata['info']) ? $jdata['info'] : '';
$ret .= $info;

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
