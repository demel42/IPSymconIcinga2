#!/usr/bin/env php
<?php

$opts_s = '';
$opts_l = [
        'ipsymcon_host:',
        'ipsymcon_port:',
        'https',
        'webhook_user:',
        'webhook_password:',

        'mode:',		// 'host' || 'service'

        'user:',		// user.name
        'type:',		// notification.type
        'comment:',		// notification.comment

        'name:',		// host.name
        'output:',		// host.output
        'state:',		// host.state
    ];

$options = getopt($opts_s, $opts_l);

$ipsymcon_host = isset($options['ipsymcon_host']) ? $options['ipsymcon_host'] : '';
if ($ipsymcon_host == '') {
    echo 'UNKNOWN - missing ipsymcon_host' . PHP_EOL;
    exit(-1);
}

$ipsymcon_port = isset($options['ipsymcon_port']) ? $options['ipsymcon_port'] : 3777;

$webhook_user = isset($options['webhook_user']) ? $options['webhook_user'] : '';
$webhook_password = isset($options['webhook_password']) ? $options['webhook_password'] : '';

$mode = isset($options['mode']) ? $options['mode'] : '';
if ($mode == '') {
    echo 'UNKNOWN - missing mode' . PHP_EOL;
    exit(-1);
}

$postdata = [];
$postdata['proc'] = 'notify';
$postdata['mode'] = $mode;

if (isset($options['user'])) {
    $postdata['user'] = $options['user'];
}
if (isset($options['type'])) {
    $postdata['type'] = $options['type'];
}
if (isset($options['comment'])) {
    $postdata['comment'] = $options['comment'];
}

if (isset($options['name'])) {
    $postdata['name'] = $options['name'];
}
if (isset($options['output'])) {
    $postdata['output'] = $options['output'];
}
if (isset($options['state'])) {
    $postdata['state'] = $options['state'];
}

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
        $err = 'got http-code ' . $httpcode . ' (ipsymcon_host error)';
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
    exit(-1);
}

$statuscode = $jdata['status'] == 'OK' ? 0 : -1;

exit($statuscode);
