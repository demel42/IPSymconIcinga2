<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class Icinga2 extends IPSModule
{
    use Icinga2Common;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyInteger('port', 5665);
        $this->RegisterPropertyBoolean('use_https', true);
        $this->RegisterPropertyString('user', 'icinga2-director');
        $this->RegisterPropertyString('password', '');

        $this->RegisterPropertyString('hook_user', '');
        $this->RegisterPropertyString('hook_password', '');

        $this->RegisterPropertyInteger('check_script', 0);
        $this->RegisterPropertyInteger('event_script', 0);
        $this->RegisterPropertyInteger('notify_script', 0);

        $this->RegisterPropertyInteger('update_interval', '60');

        $this->RegisterTimer('UpdateStatus', 0, 'Icinga2_UpdateStatus(' . $this->InstanceID . ');');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        parent::ApplyChanges();

        $vpos = 0;
        $this->MaintainVariable('BootTime', $this->Translate('Boot time'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $vpos = 10;
        $this->MaintainVariable('HostsUp', $this->Translate('hosts with state UP'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('HostsDown', $this->Translate('hosts with state DOWN'), VARIABLETYPE_INTEGER, '', $vpos++, true);

        $vpos = 20;
        $this->MaintainVariable('ServicesOk', $this->Translate('services with state OK'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('ServicesWarning', $this->Translate('services with state WARNING'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('ServicesCritical', $this->Translate('services with state CRITICAL'), VARIABLETYPE_INTEGER, '', $vpos++, true);

        $vpos = 100;
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');
        if ($host == '' || $port == 0 || $user == '' || $password == '') {
            $this->SetStatus(IS_INVALIDCONFIG);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/Icinga2');
        }

        $this->SetUpdateInterval();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->RegisterHook('/hook/Icinga2');
        }
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 1000 : 0;
        $this->SetTimerInterval('UpdateStatus', $msec);
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'Label', 'label' => 'Icinga2'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'host', 'caption' => 'Host'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'port', 'caption' => 'Port'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'use_https', 'caption' => 'Use HTTPS'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'user', 'caption' => 'User'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'password', 'caption' => 'Password'];

        $formElements[] = ['type' => 'Label', 'label' => 'Access to webhook'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'hook_user', 'caption' => 'User'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'hook_password', 'caption' => 'Password'];

        $formElements[] = ['type' => 'Label', 'label' => 'script for webhook to use for ...'];
        $formElements[] = ['type' => 'SelectScript', 'name' => 'check_script', 'caption' => ' ... "check"'];
        $formElements[] = ['type' => 'SelectScript', 'name' => 'event_script', 'caption' => ' ... "event"'];
        $formElements[] = ['type' => 'SelectScript', 'name' => 'notify_script', 'caption' => ' ... "notify"'];

        $formElements[] = ['type' => 'Label', 'label' => ''];
        $formElements[] = ['type' => 'Label', 'label' => 'Update status every X seconds'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'update_interval', 'caption' => 'Seconds'];

        $formActions = [];
        $formActions[] = ['type' => 'Button', 'label' => 'Verify API-access', 'onClick' => 'Icinga2_VerifyAccess($id);'];
        $formActions[] = ['type' => 'Button', 'label' => 'Update status', 'onClick' => 'Icinga2_UpdateStatus($id);'];
        $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/demel42/IPSymconIcinga2/blob/master/README.md";'
                        ];

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];
        $formStatus[] = ['code' => IS_FORBIDDEN, 'icon' => 'error', 'caption' => 'Instance is inactive (access forbidden)'];
        $formStatus[] = ['code' => IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }

    public function UpdateStatus()
    {
        $data = '';
        $statuscode = $this->do_HttpRequest('status', '', '', 'POST', $data);
        if ($statuscode == 0) {
            if (isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    if ($item['name'] != 'CIB') {
                        continue;
                    }
                    $status = $item['status'];
                    $this->SendDebug(__FUNCTION__, 'name=' . $item['name'] . ', status=' . print_r($status, true), 0);

                    $n_hosts_up = $status['num_hosts_up'];
                    $this->SetValue('HostsUp', $n_hosts_up);
                    $n_hosts_down = $status['num_hosts_down'];
                    $this->SetValue('HostsDown', $n_hosts_down);

                    $n_services_ok = $status['num_services_ok'];
                    $this->SetValue('ServicesOk', $n_services_ok);
                    $n_services_warning = $status['num_services_warning'];
                    $this->SetValue('ServicesWarning', $n_services_warning);
                    $n_services_critical = $status['num_services_critical'];
                    $this->SetValue('ServicesCritical', $n_services_critical);

                    $boot_ts = time() - $status['uptime'];
                    $this->SetValue('BootTime', $boot_ts);

                    $this->SetValue('LastUpdate', time());

                    break;
                }
            } else {
                $statuscode = IS_INVALIDDATA;
            }
        }

        $this->SetStatus($statuscode ? $statuscode : IS_ACTIVE);
    }

    public function VerifyAccess()
    {
        $s_hosts = 0;
        $s_services = 0;
        $boot_ts = 0;

        $msg = '';
        $statuscode = 0;

        $data = '';
        $statuscode = $this->do_HttpRequest('status', '', '', 'POST', $data);
        if ($statuscode == 0) {
            if (isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    if ($item['name'] != 'CIB') {
                        continue;
                    }
                    $status = $item['status'];
                    $n_hosts_up = $status['num_hosts_up'];
                    $n_hosts_down = $status['num_hosts_down'];
                    $s_hosts = $n_hosts_up . ' up';
                    if ($n_hosts_down) {
                        $s_hosts .= ', ' . $n_hosts_down . ' down';
                    }

                    $n_services_ok = $status['num_services_ok'];
                    $n_services_warning = $status['num_services_warning'];
                    $n_services_critical = $status['num_services_critical'];
                    $s_services = $n_services_ok . ' ok';
                    if ($n_services_warning) {
                        $s_services .= ', ' . $n_services_warning . ' warn';
                    }
                    if ($n_services_critical) {
                        $s_services .= ', ' . $n_services_critical . ' crit';
                    }

                    $boot_ts = time() - $status['uptime'];
                    break;
                }
            } else {
                $statuscode = IS_INVALIDDATA;
            }
        }

        if ($statuscode) {
            $msg = $this->Translate('access failed') . ':' . PHP_EOL;
            $msg .= '  ';
        } else {
            $msg = $this->Translate('access ok') . ':' . PHP_EOL;
        }
        switch ($statuscode) {
            case IS_INVALIDCONFIG:
                $msg .= $this->Translate('invalid configuration');
                break;
            case IS_FORBIDDEN:
                $msg .= $this->Translate('access forbidden');
                break;
            case IS_SERVERERROR:
                $msg .= $this->Translate('server error');
                break;
            case IS_HTTPERROR:
                $msg .= $this->Translate('http error');
                break;
            case IS_INVALIDDATA:
                $msg .= $this->Translate('invalid data');
                break;
            default:
                $msg .= '  ' . $this->Translate('started') . ': ' . date('d.m.Y H:i', $boot_ts) . PHP_EOL;
                $msg .= '  ' . $this->Translate('hosts') . ': ' . $s_hosts . PHP_EOL;
                $msg .= '  ' . $this->Translate('services') . ': ' . $s_services . PHP_EOL;
                break;
        }

        if ($statuscode > 0) {
            $this->SetStatus($statuscode);
        }

        echo $msg;
    }

    private function do_HttpRequest($cmd, $args, $postdata, $mode, &$result)
    {
        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');
        $use_https = $this->ReadPropertyBoolean('use_https');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        $url = ($use_https ? 'https://' : 'http://') . $host . ':' . $port . '/v1/' . $cmd;

        if ($args != '') {
            foreach ($args as $arg => $value) {
                $url .= '&' . $arg;
                if ($value != '') {
                    $url .= '=' . rawurlencode($value);
                }
            }
        }

        $header = [];
        $header[] = 'Accept: application/json; charset=utf-8';

        if ($postdata != '') {
            $header[] = 'Content-Type: application/json';
            $postdata = json_encode($postdata);
        }

        if ($mode == 'POST') {
            $header[] = 'X-HTTP-Method-Override: GET';
        }

        $this->SendDebug(__FUNCTION__, 'http: url=' . $url . ', mode=' . $mode . ' auth=' . $user . ':' . $password, 0);
        $this->SendDebug(__FUNCTION__, '  header=' . print_r($header, true), 0);
        if ($postdata != '') {
            $this->SendDebug(__FUNCTION__, '  postdata=' . $postdata, 0);
        }

        $time_start = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
        switch ($mode) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $cdata = curl_exec($ch);
        $cerrno = curl_errno($ch);
        $cerror = $cerrno ? curl_error($ch) : '';
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);
        $this->SendDebug(__FUNCTION__, '    cdata=' . $cdata, 0);

        $statuscode = 0;
        $err = '';
        $result = '';
        if ($cerrno) {
            $statuscode = IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 403) {
                $statuscode = IS_FORBIDDEN;
                $err = 'got http-code ' . $httpcode . ' (forbidden)';
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $statuscode = IS_HTTPERROR;
                $err = "got http-code $httpcode";
            }
        } else {
            $result = json_decode($cdata, true);
            if ($result == '') {
                $statuscode = IS_INVALIDDATA;
                $err = 'malformed response';
            }
        }

        if ($statuscode) {
            $this->LogMessage('url=' . $url . ' => statuscode=' . $statuscode . ', err=' . $err, KL_WARNING);
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $statuscode . ', err=' . $err, 0);
        }

        return $statuscode;
    }

    protected function PerformCheck($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $check_script = $this->ReadPropertyInteger('check_script');
        if ($check_script > 0) {
            $jdata['InstanceID'] = $this->InstanceID;
            $ret = IPS_RunScriptWaitEx($check_script, $jdata);
            $scriptName = IPS_GetName($check_script);
            $this->SendDebug(__FUNCTION__, 'scripts=' . $scriptName . ', ret=' . print_r($ret, true), 0);
            return $ret;
        }

        $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
        if ($mode != 'status') {
            return false;
        }

        $now = time();

        $startTime = IPS_GetKernelStartTime();

        $sdata = IPS_GetSnapshot();
        $udata = utf8_encode($sdata);
        $snapshot = json_decode($udata, true);
        $tps = floor($snapshot['timestamp'] / ($now - $startTime) * 100) / 100;

        $version = IPS_GetKernelVersion();

        $threadList = IPS_GetScriptThreadList();
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

        $instanceList = IPS_GetInstanceList();
        $instanceCount = count($instanceList);
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

        $scriptList = IPS_GetScriptList();
        $scriptCount = count($scriptList);
        $scriptError = 0;
        foreach ($scriptList as $id) {
            $script = IPS_GetScript($id);
            if (!$script['ScriptIsBroken']) {
                continue;
            }
            $scriptError++;
            $loc = IPS_GetLocation($id);
            $this->SendDebug(__FUNCTION__, 'script=' . $loc . ', status=broken', 0);
        }

        $linkList = IPS_GetLinkList();
        $linkCount = count($linkList);
        $linkError = 0;
        foreach ($linkList as $id) {
            $link = IPS_GetLink($id);
            if (IPS_ObjectExists($link['LinkID'])) {
                continue;
            }
            $linkError++;
        }

        $eventList = IPS_GetEventList();
        $eventCount = count($eventList);
        $eventActive = 0;
        $eventError = 0;
        foreach ($eventList as $id) {
            $event = IPS_GetEvent($id);
            $ok = true;
            $varID = $event['TriggerVariableID'];
            if ($event['EventActive']) {
                $eventActive++;
            }
            if ($varID == 0 || IPS_ObjectExists($varID)) {
                continue;
            }
            $loc = IPS_GetLocation($id);
            $this->SendDebug(__FUNCTION__, 'script=' . $loc . ', status=object missing', 0);
            $eventError++;
        }

        $moduleList = IPS_GetModuleList();
        $moduleCount = count($moduleList);

        $varList = IPS_GetVariableList();
        $varCount = count($varList);

        $this->SendDebug(__FUNCTION__,
                    'threadCount=' . $threadCount .
                    ', timerCount=' . $timerCount . ' (1m=' . $timer1MinCount . ', 5m=' . $timer5MinCount . ')' . 
                    ', instanceCount=' . $instanceCount . ', instanceError=' . $instanceError .
                    ', scriptCount=' . $scriptCount . ', scriptError=' . $scriptError .
                    ', linkCount=' . $linkCount . ', linkError=' . $linkError .
                    ', eventCount=' . $eventCount . ', eventActive=' . $eventActive . ', eventError=' . $eventError .
                    ', modulCount=' . $moduleCount .
                    ', varCount=' . $varCount .
                    '', 0);

        $status = 'OK';

        $info = 'started ' . date('d.m.Y H:i', $startTime);
        $info .= ', threads=' . $threadCount;
        $info .= ', timer=' . $timerCount;
        if ($instanceError) {
            $info .= ', invalid instances=' . $instanceError;
            $status = 'WARNING';
        }
        if ($linkError) {
            $info .= ', broken links=' . $linkError;
            $status = 'WARNING';
        }
        if ($scriptError) {
            $info .= ', faulty scripts=' . $scriptError;
            $status = 'WARNING';
        }
        if ($eventError) {
            $info .= ', invalid events=' . $eventError;
            $status = 'WARNING';
        }

        $perfdata = [];
        $perfdata['tps'] = $tps;
        $perfdata['threads'] = $threadCount;
        $perfdata['timer'] = $timerCount;
        $perfdata['timer_1m'] = $timer1MinCount;
        $perfdata['timer_5m'] = $timer5MinCount;

        /*
        $perfdata['instanceCount'] = $instanceCount;
        $perfdata['instanceError'] = $instanceError;
        $perfdata['scriptCount'] = $scriptCount;
        $perfdata['scriptError'] = $scriptError;
        $perfdata['linkCount'] = $linkCount;
        $perfdata['linkError'] = $linkError;
        $perfdata['eventCount'] = $eventCount;
        $perfdata['eventActive'] = $eventActive;
        $perfdata['eventError'] = $eventError;
        $perfdata['moduleCount'] = $moduleCount;
        $perfdata['varCount'] = $varCount;
        */

        $jret = [
                'status'   => $status,
                'info'     => $info,
                'perfdata' => $perfdata,
            ];
        return json_encode($jret);
    }

    protected function ProcessEvent($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $event_script = $this->ReadPropertyInteger('event_script');
        if ($event_script > 0) {
            $jdata['InstanceID'] = $this->InstanceID;
            $ret = IPS_RunScriptWaitEx($event_script, $jdata);
            $scriptName = IPS_GetName($event_script);
            $this->SendDebug(__FUNCTION__, 'scripts=' . $scriptName . ', ret=' . print_r($ret, true), 0);
            return $ret;
        }

        $this->SendDebug(__FUNCTION__, 'missing event_script, abort', 0);
        return false;
    }

    protected function SendNotification($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $notify_script = $this->ReadPropertyInteger('notify_script');
        $this->SendDebug(__FUNCTION__, 'notify_script=' . $notify_script, 0);
        if ($notify_script > 0) {
            $jdata['InstanceID'] = $this->InstanceID;
            $ret = IPS_RunScriptWaitEx($notify_script, $jdata);
            $scriptName = IPS_GetName($notify_script);
            $this->SendDebug(__FUNCTION__, 'scripts=' . $scriptName . ', ret=' . print_r($ret, true), 0);
            return $ret;
        }

        $this->SendDebug(__FUNCTION__, 'missing notify_script, abort', 0);
        return false;
    }

    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, '_SERVER=' . print_r($_SERVER, true), 0);
        $this->SendDebug(__FUNCTION__, '_POST=' . print_r($_POST, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        $hook_user = $this->ReadPropertyString('hook_user');
        $hook_password = $this->ReadPropertyString('hook_password');
        if ($hook_user != '' || $hook_password != '') {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $_SERVER['PHP_AUTH_USER'] = '';
            }
            if (!isset($_SERVER['PHP_AUTH_PW'])) {
                $_SERVER['PHP_AUTH_PW'] = '';
            }

            if (($_SERVER['PHP_AUTH_USER'] != $hook_user) || ($_SERVER['PHP_AUTH_PW'] != $hook_password)) {
                header('WWW-Authenticate: Basic Realm="Geofency WebHook"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization required';
                return;
            }
        }
        if ($uri == '/hook/Icinga2') {
            $proc = isset($_POST['proc']) ? $_POST['proc'] : '';
            $this->SendDebug(__FUNCTION__, 'proc: ' . $proc, 0);
            switch ($proc) {
                case 'check':
                    $ret = $this->PerformCheck($_POST);
                    break;
                case 'notify':
                    $ret = $this->SendNotification($_POST);
                    break;
                case 'event':
                    $ret = $this->ProcessEvent($_POST);
                    break;
                default:
                    $ret = false;
                    break;
            }

            if ($ret == false) {
                http_response_code(404);
                die('Proc not found!');
            }
            $this->SendDebug(__FUNCTION__, 'ret=' . $ret, 0);
            echo $ret . PHP_EOL;
            return;
        }
        http_response_code(404);
        die('File not found!');
    }

    public function QueryObject(string $obj, string $query)
    {
        $data = '';
        $statuscode = $this->do_HttpRequest('objects/' . $obj, '', json_decode($query), 'POST', $data);
        if ($statuscode == 0 && isset($data['results'])) {
            return json_encode($data['results']);
        }
        return false;
    }

    public function Query4Host(string $hosts)
    {
        if ($hosts != '') {
            $h = json_decode($hosts);
            if ($h == '') {
                $h = [$hosts];
            }
            $query = [
                    'filter'      => ['host.name in hosts'],
                    'filter_vars' => ['hosts' => $h],
                ];
            $data = '';
            $statuscode = $this->do_HttpRequest('objects/hosts', '', $query, 'POST', $data);
            if ($statuscode == 0 && isset($data['results'])) {
                return json_encode($data['results']);
            }
        }
        return false;
    }

    public function Query4Service(string $services, string $hosts)
    {
        if ($services != '') {
            $s = json_decode($services);
            if ($s == '') {
                $s = [$services];
            }
        } else {
            $s = '';
        }

        if ($hosts != '') {
            $h = json_decode($hosts);
            if ($h == '') {
                $h = [$hosts];
            }
        } else {
            $h = '';
        }

        if ($s != '' && $h != '') {
            $query = [
                    'joins'       => ['host'],
                    'filter'      => ['host.name in hosts && service.name in services'],
                    'filter_vars' => ['hosts' => $h, 'services' => $s],
                ];
        } elseif ($s != '') {
            $query = [
                    'joins'       => ['host'],
                    'filter'      => ['service.name in services'],
                    'filter_vars' => ['services' => $s],
                ];
        } elseif ($h != '') {
            $query = [
                    'joins'       => ['host'],
                    'filter'      => ['host.name in hosts'],
                    'filter_vars' => ['hosts' => $h],
                ];
        } else {
            $query = '';
        }
        if ($query != '') {
            $data = '';
            $statuscode = $this->do_HttpRequest('objects/services', '', $query, 'POST', $data);
            if ($statuscode == 0 && isset($data['results'])) {
                return json_encode($data['results']);
            }
        }
        return false;
    }
}
