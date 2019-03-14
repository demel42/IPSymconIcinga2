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
		$this->MaintainVariable('HostsUp', $this->Translate('hosts with state: up'), VARIABLETYPE_INTEGER, '', $vpos++, true);
		$this->MaintainVariable('HostsDown', $this->Translate('hosts with state: down'), VARIABLETYPE_INTEGER, '', $vpos++, true);

		$vpos = 20;
		$this->MaintainVariable('ServicesOk', $this->Translate('services with state: ok'), VARIABLETYPE_INTEGER, '', $vpos++, true);
		$this->MaintainVariable('ServicesWarning', $this->Translate('services with state: warning'), VARIABLETYPE_INTEGER, '', $vpos++, true);
		$this->MaintainVariable('ServicesCritical', $this->Translate('services with state: critical'), VARIABLETYPE_INTEGER, '', $vpos++, true);

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
		$msec = $min > 0 ? $min * 1000 * 60 : 0;
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
                $msg .= '  ' . $this->Translate('services') . ': ' . $n_services . PHP_EOL;
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
            $statuscode = IS_INVALIDDATA;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } elseif ($httpcode != 200) {
            if ($httpcode == 403) {
                $err = 'got http-code ' . $httpcode . ' (forbidden)';
                $statuscode = IS_FORBIDDEN;
            } elseif ($httpcode >= 500 && $httpcode <= 599) {
                $statuscode = IS_SERVERERROR;
                $err = 'got http-code ' . $httpcode . ' (server error)';
            } else {
                $err = "got http-code $httpcode";
                $statuscode = IS_HTTPERROR;
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

    protected function ProcessHookData()
    {
        $this->SendDebug('WebHook SERVER', print_r($_SERVER, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        if ($uri == '/hook/Icinga2') {
            $data = file_get_contents('php://input');
            $jdata = json_decode($data, true);
            if ($jdata == '') {
                echo 'malformed data: ' . $data;
                $this->SendDebug(__FUNCTION__, 'malformed data: ' . $data, 0);
                return;
            }
            // DOIT
            return;
        }
        http_response_code(404);
        die('File not found!');
    }
}
