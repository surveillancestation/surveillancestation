<?php
error_reporting(E_ALL);
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

use \Dropbox as dbx;

class surveillanceStation extends eqLogic
{
    public static function cron5()
    {
        foreach (eqLogic::byType('surveillanceStation') as $surveillanceStation) {

            if (is_object($surveillanceStation)) {
                foreach ($surveillanceStation->getCmd('info') as $cmd) {
                    $cmd->event($cmd->execute());
                    $cmd->save();
                }
            }
        }
    }

	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'surveillanceStation_update';
		$return['progress_file'] = '/tmp/dependancy_ruby_in_progress';
		if (file_exists('/tmp/dependancy_ruby_in_progress')) {
			$return['state'] = 'in_progress';
		} else {
			if (exec('which ruby | wc -l') != 0 && exec('gem list --local | grep concurrent-ruby | wc -l') != 0 && exec('gem list --local | grep thread | wc -l') != 0 && exec('gem list --local | grep httparty | wc -l') != 0) {
				$return['state'] = 'ok';
			} else {
				$return['state'] = 'nok';
			}
		}
		return $return;
	}

	public static function dependancy_install() {
		if (file_exists('/tmp/compilation_ruby_in_progress')) {
			return;
		}
		log::remove('surveillanceStation_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../desktop/ressources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('surveillanceStation_update') . ' 2>&1 &';
		exec($cmd);
	}

	public static function pull($_options)
    {
        foreach (eqLogic::byType('surveillanceStation') as $surveillanceStation) {

            if (is_object($surveillanceStation)) {
                foreach ($surveillanceStation->getCmd('info') as $cmd) {
                    $cmd->event($cmd->execute());
                    $cmd->save();
                }
            }
        }
    }
	
    public static function getSidFromCache($host)
    {
        $sidCache = cache::byKey('surveillanceStationSID'.$host);
        return $sidCache->getValue();
    }

    public static function cron15()
    {
        $sidByHost = array();

        $ctx = stream_context_create(array('http'=>
            array(
                'timeout' => 5,
            ),
            'https'=>
                array(
                    'timeout' => 5,
                )
        ));

        foreach (eqLogic::byType('surveillanceStation') as $surveillanceStation) {

            if (is_object($surveillanceStation)) {

                if(!array_key_exists($surveillanceStation->getConfiguration('host'),$sidByHost)){

                    log::add('surveillanceStation', 'debug', 'CRON - test validité SID pour synology '.$surveillanceStation->getConfiguration('host'));

                    $valid = false;

                    $version = ($surveillanceStation->getConfiguration('v6') == 0) ? 'v7' : 'v6';
                    $host = $surveillanceStation->getConfiguration('host');
                    $port = $surveillanceStation->getConfiguration('port');

                    $sidCache = cache::byKey('surveillanceStationSID'.$surveillanceStation->getConfiguration('host'));

                    if($sidCache->getValue() != ''){

                        $contents = file_get_contents($host.':'.$port.surveillanceStationCmd::getAPI($version,'STATUT').'1&_sid='.$sidCache->getValue(), false, $ctx);
                        $json = json_decode($contents);
                        if($json->success == 'true'){
                            $valid = true;
                            log::add('surveillanceStation', 'debug', 'CRON - sid toujours valide pour synology '.$surveillanceStation->getConfiguration('host'));
                            $sidByHost[$surveillanceStation->getConfiguration('host')] = $sidCache->getValue();
                        }else{
                            $valid = false;
                            log::add('surveillanceStation', 'debug', 'CRON - sid expiré pour synology '.$surveillanceStation->getConfiguration('host'));
                        }
                    }

                    if($sidCache == '' || $sidCache == null || $valid == false){
                        $sid = self::authSID($surveillanceStation->getConfiguration('host'),$surveillanceStation->getConfiguration('port'),$surveillanceStation->getConfiguration('login'),$surveillanceStation->getConfiguration('password'));
                        cache::set('surveillanceStationSID'.$surveillanceStation->getConfiguration('host'), $sid, 0);
                        log::add('surveillanceStation', 'debug', 'CRON - mise en cache SID ['.$sid.'] pour synology '.$surveillanceStation->getConfiguration('host'));
                        $sidByHost[$surveillanceStation->getConfiguration('host')] = $sid;
                    }

                }else{
                    log::add('surveillanceStation', 'debug', 'CRON - SID déjà en cache pour '.$surveillanceStation->getConfiguration('host'));
                }
                $surveillanceStation->refreshWidget();
            }
        }
    }

    public static function mailTester($eqId)
    {
        require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer-master/PHPMailerAutoload.php';

        try{

            $conf = surveillanceStation::byId($eqId);

            $mail = new PHPMailer();

            $mail->isSMTP();
            $mail->Host  = $conf->getConfiguration('emailServer');

            if($conf->getConfiguration('emailLogin') != ''){
                $mail->SMTPAuth     = ($conf->getConfiguration('emailSecurity') == '') ? false : true;
                $mail->Username     = $conf->getConfiguration('emailLogin');
                $mail->Password     = $conf->getConfiguration('emailPassword');
                $mail->SMTPSecure   = $conf->getConfiguration('emailSecurity');
            }

            $mail->Port         = $conf->getConfiguration('emailPort');
            $mail->From         = $conf->getConfiguration('emailMailExp');
            $mail->FromName     = $conf->getConfiguration('emailNomExp');

            $mail->addAddress($conf->getConfiguration('emailMailExp'));

            $mail->isHTML(true);

            $mail->Subject = '[Jeedom] plugin surveillanceStation station';

            $mail->Body = "[Jeedom] plugin surveillanceStation, ceci est un mail de test.";

            if (!$mail->send()) {
                log::add('surveillanceStation', 'debug', 'Impossible d\'envoyer le mail.'.' '.$mail->ErrorInfo);
                echo 'Le message n\'a pas été envoyé.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                return 'Le mail a été envoyé';
            }
        }catch (Exception $e){
            log::add('surveillanceStation', 'debug', 'Impossible d\'envoyer le mail.'.' '.$e);
            throw $e;
        }
    }

    public static function checkDropbox()
    {
        require_once dirname(__FILE__) . '/../../3rdparty/dropbox-sdk/lib/Dropbox/autoload.php';

        try {
            $dbxClient = new dbx\Client($_POST['token'], "Jeedom synology plugin");
            $accountInfo = $dbxClient->getAccountInfo();
        } catch (Exception $e) {
            return "Token invalide";
        }
        return $accountInfo;
    }

    public static function getCameras($eqId)
    {
		if ($eqId == '') {
			return '';
		}
        $syno_config = eqLogic::byId($eqId);
        $host = $syno_config->getConfiguration('host');
        $port = $syno_config->getConfiguration('port');
        $login = $syno_config->getConfiguration('login');
        $password = $syno_config->getConfiguration('password');

        if($host == '' || $port == '' || $login == '' || $password == ''){
            return array();
        }

        //auth + retrieve session cookies
        $session_syno = self::auth($host, $port, $login, $password);
		try{

			$version = ($syno_config->getConfiguration('v6') == 0) ? 'v7' : 'v6';
			$contents = file_get_contents($host . ':' . $port . surveillanceStationCmd::getAPI($version,'LIST'), false, $session_syno);

		}catch (Exception $e){
			log::add('surveillanceStation', 'error', 'Erreur lors de la recupération des caméras '.$e);
			throw new Exception(__('Erreur lors de la recupération des caméras', __FILE__));
		}

        $json = json_decode($contents);
        $cameras = array();

        if($json == null){
            throw new Exception(__('Erreur lors de la recupération des caméras.', __FILE__));
        }

        foreach ($json->data->cameras as $camera) {
            array_push($cameras, ['id' => $camera->id,
                'name' => $camera->name,
                'host' => $camera->host,
                'model' => ($version == 'v7') ? $camera->model :  $camera->additional->device->model,
                'port' => ($version == 'v7') ? $camera->port : $camera->additional->device->httpPort,
                'resolution' => ($version == 'v7') ? $camera->resolution : $camera->additional->video->liveResolution,
                'enable' => $camera->enabled,
                'ptzCap' => $camera->ptzCap,
                'recStatus' => $camera->recStatus
            ]);
        }

        return $cameras;

    }

    public static function auth($host, $port, $login, $password)
    {

        $ch = curl_init($host . ':' . $port . '/webapi/auth.cgi?api=SYNO.API.Auth&method=Login&version=1&account=' . $login . '&passwd=' . urlencode($password) . '&session=SurveillanceStation');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($result, $header_size);

        if(strstr($body,'error') != null){
            log::add('surveillanceStation', 'error', 'Erreur lors de l authentification. Vérifiez vos login et password. message : '.$body);
            throw new Exception('Erreur lors de l authentification. Vérifiez vos login et password.');
        }

        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $result, $m);
        parse_str($m[1], $cookies);

            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'timeout' => 15,
                    'header' => "Cookie: id=" . $cookies['id'] . ";\r\n"
                ),
                'https' => array(
                    'method' => "GET",
                    'timeout' => 15,
                    'header' => "Cookie: id=" . $cookies['id'] . ";\r\n"
                )
            );
        return stream_context_create($opts);
    }

    public static function authSID($host, $port, $login, $password)
    {
        $ctx = stream_context_create(array('http'=>
            array(
                'timeout' => 15,
            ),
            'https'=>
			array(
				'timeout' => 15,
			)
        ));
        $responseSID = file_get_contents($host . ':' . $port . '/webapi/auth.cgi?api=SYNO.API.Auth&method=Login&version=2&account='.$login.'&passwd='.urlencode($password).'&session=SurveillanceStation&format=sid',false,$ctx);
        $jsonSID = json_decode($responseSID);
        return $jsonSID->data->sid;
    }

    public static function checkAuth($host, $port, $login, $password)
    {
        $ctx = stream_context_create(array('http'=>
            array(
                'timeout' => 15,
            ),
            'https'=>
			array(
				'timeout' => 15,
			)
        ));

        try{
            $responseSID = file_get_contents($host . ':' . $port . '/webapi/auth.cgi?api=SYNO.API.Auth&method=Login&version=2&account='.$login.'&passwd='.urlencode($password).'&session=SurveillanceStation&format=sid',false,$ctx);
        }catch (Exception $e){
            log::add('surveillanceStation', 'error', 'Erreur lors de l\'authentification : '.$e);
            throw new Exception('Erreur lors de l\'authentification, vérifier l\'adresse ip, le port, le protocole http / https.');
        }
        $jsonSID = json_decode($responseSID);

        if($jsonSID->success == true){
            file_get_contents($host . ':' . $port . '/webapi/auth.cgi? api=SYNO.API.Auth&method=Logout&version=2&session=SurveillanceStation&_sid='.$jsonSID->data->sid,false,$ctx);
            return 'true';
        }else{
            if($responseSID != null){
                log::add('surveillanceStation', 'error', 'Erreur lors de l\'authentification code : '.$jsonSID->error->code);
                return self::getAuthError($jsonSID->error->code);
            }else{
                log::add('surveillanceStation', 'error', 'Erreur lors de l\'authentification, vérifier l\'adresse ip, le port, le protocole http / https.');
                return 'Vérifier l\'adresse ip, le port, le protocole http / https.';
            }
        }
    }

    public static function getAuthError($code){

        $ERROR = array
        (
                100          => 'Erreur inconnu',
                101          => 'Les paramètres du compte ne sont pas spécifiés ',
                400          => 'Mot de passe invalide',
                401          => 'Compte invité ou compte désactivé',
                402          => 'Permission refusée',
                403          => 'One time password not specified.',
                404          => 'One time password authenticate failed.'
        );
        return $ERROR[$code];
    }


    /*     * *********************Methode d'instance************************* */

    public function preSave()
    {
    }

    public function preUpdate()
    {
        if($this->getConfiguration('live') == 1){
            foreach ($this->getCmd() as $cmd) {
                if($cmd->getConfiguration('synoAction') != 'live'){
                    $cmd->remove();
                }
            }
        }

    }
    /*     * **********************Getteur Setteur*************************** */

    public function getUserIP()
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }

        return $ip;
    }

	public static $_widgetPossibility = array('custom' => true);

 	public function toHtml($_version = 'dashboard')	{
		if($this->getConfiguration('live') == '1'){	
			$replace = $this->preToHtml($_version);
			if (!is_array($replace)) {
				return $replace;
			}
			$_version = jeedom::versionAlias($_version);

			$live = false;
			$cmdLive = null;
			foreach ($this->getCmd('action') as $cmd) {
				if($cmd->getConfiguration('synoAction') == 'live') {
				$live = true;
				$cmdLive = $cmd;
					log::add('surveillanceStation', 'debug', 'Live demandé pour '.$cmd->getName());
				}
			}
			if(!$live){
				return parent::toHtml($_version);
			}

			$cmdConf = $cmdLive->getConfiguration('cameras');

			foreach (array_keys($cmdConf) as $key) {
				if ($cmdConf[$key] == '1') {
					$cameraId = explode("%", $key)[1];
				}
			}

			$sidCache = cache::byKey('surveillanceStationSID'.$this->getConfiguration('host'));
			if($sidCache->getValue() == ''){
				$sid = self::authSID($this->getConfiguration('host'),$this->getConfiguration('port'),$this->getConfiguration('login'),$this->getConfiguration('password'));
				cache::set('surveillanceStationSID'.$this->getConfiguration('host'), $sid, 0);
				log::add('surveillanceStation', 'debug', 'mise en cache SID pour synology '.$this->getConfiguration('host'));
			}else{
				$sid = $sidCache->getValue();
				log::add('surveillanceStation', 'debug', 'Sid depuis cache '.$this->getConfiguration('host'));
			}

			log::add('surveillanceStation', 'debug', 'ip client '.$this->getUserIP());

			$replace ['#host#'] = netMatch('192.168.*.*', getClientIp()) || netMatch('10.*.*.*', getClientIp()) ? $this->getConfiguration('host') : $this->getConfiguration('hostExt');
			$replace ['#port#'] = netMatch('192.168.*.*', getClientIp()) || netMatch('10.*.*.*', getClientIp()) ? $this->getConfiguration('port') : $this->getConfiguration('portExt');
			$replace ['#sid#'] = $sid;
			$replace ['#humanname#'] = $this->getHumanName();
			$replace ['#cameraId#'] = $cameraId;
			$replace ['#formatvideo#'] = 'mjpeg'; //mjpeg ou hls
			$replace ['#heightcam#'] = $this->getDisplay('height', 'auto') - 20;

			$html = template_replace($replace, getTemplate('core', $_version, 'surveillanceStation','surveillanceStation'));
			return $html;
		}
		if($this->getConfiguration('live') != '1'){
			return parent::toHtml($_version);
		}
	}
}


class surveillanceStationCmd extends cmd
{

    /*     * *************************Attributs****************************** */
    	public static $_widgetPossibility = array('custom' => true);
    /*     * *********************Methode d'instance************************* */

    public function preSave()
    {

        if ($this->getName() == '') {
            throw new Exception(__('Veuillez choisir un nom pour la commande', __FILE__));
        }

        if ($this->getConfiguration('synoAction') == '') {
            throw new Exception(__('Veuillez choisir une action pour la commande', __FILE__));
        }

        if ($this->getConfiguration('synoAction') != "statut" && $this->getConfiguration('synoAction') != "statutMotion") {
            $this->setType("action");
            $this->setSubType('other');
        } else {
            $this->setType("info");
            $this->setSubType('binary');
        }

        $cmdConf = $this->getConfiguration('cameras');
        $nbCams = 0;
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                $nbCams++;
            }
        }

        /*if ($this->getConfiguration('synoAction') == "startRecording" && $this->getConfiguration('askForDuration') == '1') {
            if(!is_numeric($this->getConfiguration('duration')) || (is_numeric($this->getConfiguration('duration')) && $this->getConfiguration('duration') <= 0)){
                throw new Exception(__('Erreur pour la commande : '.$this->getName().' ->'.'La durée d\'enregistrement doit être de type numérique, exprimée en minute.' , __FILE__));
            }
        }*/

        if($nbCams == 0){
            throw new Exception(__('Erreur pour la commande : '.$this->getName().' ->'.'Veuillez choisir au moins une caméra.', __FILE__));
        }

        if ($this->getConfiguration('synoAction') == "statut") {
            if($nbCams > 1){
                throw new Exception(__('Erreur pour la commande :  Statut caméra -> La commande est compatible avec une seule caméra. Créer une commande par caméra. ', __FILE__));
            }
        }
		
		if ($this->getConfiguration('synoAction') == "statutMotion") {
            if($nbCams > 1){
                throw new Exception(__('Erreur pour la commande : Statut détection Mouvement -> La commande est compatible avec une seule caméra. Créer une commande par caméra.', __FILE__));
            }
        }

        $eq = $this->getEqLogic();

        if($this->getConfiguration('dropbox') == '1'){
            if($eq->getConfiguration('dropboxToken') == ''){
                throw new Exception(__('Erreur pour la commande : '.$this->getName().' ->'.'Pour uploader sur dropbox veuillez rentrer un token.', __FILE__));
            }
        }

        if($this->getConfiguration('email') == '1'){
            if($eq->getConfiguration('emailServer') == '' ||
                $eq->getConfiguration('emailPort') == '' ||
                $eq->getConfiguration('emailSubject') == '' ||
                $eq->getConfiguration('emailNomExp') == '' ||
                $eq->getConfiguration('emailMailExp') == ''){
                throw new Exception(__('Erreur pour la commande : '.$this->getName().' ->'.'Pour envoyer les snapshots par mail, veuillez configurer l\'accès à votre serveur smpt.', __FILE__));
            }

            if($this->getConfiguration('adresses') == ''){
                throw new Exception(__('Erreur pour la commande : '.$this->getName().' ->'.'Pour envoyer les snapshots par mail, veuillez configurer renseigner des adresses de destinations.', __FILE__));
            }
        }
    }

    public function postSave()
    {
    }

    public function execute($_options = null)
    {
        $action = $this->getConfiguration('synoAction');
        $ret = $this->$action();

        if ($this->getType() != 'action') {
            return $ret;
        }
    }

    private function disable()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';

        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'DISABLE') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }

    private function enable()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'ENABLE') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }

    private function startRecording()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'START') . explode("%", $key)[1] , false, $session);
            }
        }

        $this->logout($session);
    }

    private function stopRecording()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'STOP') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);
    }

    private function startMotionSS()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'STARTMOTIONSS') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }

    private function startMotionCM()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'STARTMOTIONCM') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }

    private function stopMotion()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'STOPMOTION') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }	
	
    private function snapshot()
    {
        require_once dirname(__FILE__) . '/../../3rdparty/dropbox-sdk/lib/Dropbox/autoload.php';
        require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer-master/PHPMailerAutoload.php';

        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();

        $cmd = 'ruby '.dirname(__FILE__) . '/../../3rdparty/'.'syno.rb';
        $cmd = $cmd . " " . $synoConf->getConfiguration('host') . " " . $synoConf->getConfiguration('port') . " " . $synoConf->getConfiguration('login') . " " . rawurlencode($synoConf->getConfiguration('password'));
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        $cmd = $cmd . " " . $version . " ";

        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                $cmd = $cmd . self::wd_remove_accents($key) . ",";
            }
        }

        $cmd = substr($cmd, 0, -1);

        log::add('surveillanceStation', 'debug', 'Commande ruby :'.$cmd);
        $path = exec($cmd);
        log::add('surveillanceStation', 'debug', 'Path snapshot :'.$path);

        if ($this->getConfiguration('email') == '1') {

            try {

                $mail = new PHPMailer();

                $mail->isSMTP();
                $mail->Host  = $synoConf->getConfiguration('emailServer');


                if($synoConf->getConfiguration('emailLogin') != ''){
                    $mail->SMTPAuth     = ($synoConf->getConfiguration('emailSecurity') == '') ? false : true;
                    $mail->Username     = $synoConf->getConfiguration('emailLogin');
                    $mail->Password     = $synoConf->getConfiguration('emailPassword');
                    $mail->SMTPSecure   = $synoConf->getConfiguration('emailSecurity');
                }
                $mail->Port         = $synoConf->getConfiguration('emailPort');
                $mail->From         = $synoConf->getConfiguration('emailMailExp');
                $mail->FromName     = $synoConf->getConfiguration('emailNomExp');

                $mail->CharSet      = 'utf-8';

                $tos = explode(',', $this->getConfiguration('adresses'));

                foreach ($tos as $to) {
                    $mail->addAddress($to);
                }

                $body = '<body><head>';
                $body = $body . '</head>';
                $body = $body . '<h3>[Jeedom] - plugin surveillance station</h3>';
                $body = $body . '<div>';


                $snapshots = array_diff(scandir($path), array('..', '.'));

                foreach ($snapshots as $key => $snapshot) {
                    $mail->AddEmbeddedImage($path . "/" . $snapshot, $snapshot, $snapshot);

                    $body = $body . '<div>';
                    $body = $body . '<H6><span style="font-size: 12px;background-color:#337ab7;color:#ffffff;border-radius:.25em;vertical-align:baseline;padding: .2em .6em .3em;">' . $snapshot . ' </span></H6>';
                    $body = $body . '<img class="img-rounded" style="padding:5px" src="cid:' . $snapshot . '">';
                    $body = $body . '</div>';

                }

                $body = $body . '</div>';
                $body = $body . '</body>';
                $mail->Body = $body;
                $mail->AltBody = '[Jeedom] - plugin surveillance station';

                $mail->isHTML(true);

                $mail->Subject = $synoConf->getConfiguration('emailSubject');

                if (!$mail->send()) {
                    echo 'Le message n\'a pas été envoyé.';
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                } else {
                }
            } catch (Exception $e) {
                log::add('surveillanceStation', 'debug', 'Impossible d\'envoyer le mail avec les snapshots .'.$e);
            }
        }
        if ($this->getConfiguration('dropbox') == '1') {
            try {
                $dbxClient = new dbx\Client($synoConf->getConfiguration('dropboxToken'), "Jeedom synology plugin");
                $snapshots = array_diff(scandir($path), array('..', '.'));
                $dropbox_path = str_replace("/tmp", "", $path);
                foreach ($snapshots as $key => $snapshot) {
                    $f = fopen($path . "/" . $snapshot, "rb");
                    $dbxClient->uploadFile("/" . $snapshot,
                        dbx\WriteMode::add(), $f);
                    fclose($f);
                }
				$deletedsnap = exec("rm -r /tmp". $dropbox_path);

            } catch (Exception $e) {
                log::add('surveillanceStation', 'debug', 'upload impossible, vérifié votre token dropbox '.$e);
            }
        }

        $this->logout($session);
    }

    public static function getAPI($version,$cmd){

        $SYNO_API = array
        (
            'v6' => array(
                'LIST'          => '/webapi/SurveillanceStation/camera.cgi?api=SYNO.SurveillanceStation.Camera&method=List&version=1&additional=device,video',
                'ENABLE'        => '/webman/3rdparty/SurveillanceStation/cgi/camera.cgi?action=cameraEnable&UserId=1024&idList=',
                'DISABLE'       => '/webman/3rdparty/SurveillanceStation/cgi/camera.cgi?action=cameraDisable&UserId=1024&idList=',
                'START'         => '/webapi/SurveillanceStation/extrecord.cgi?api=SYNO.SurveillanceStation.ExternalRecording&method=Record&version=2&action=start&cameraId=',
                'STOP'          => '/webapi/SurveillanceStation/extrecord.cgi?api=SYNO.SurveillanceStation.ExternalRecording&method=Record&version=2&action=stop&cameraId=',
                'STATUT'        => '/webapi/SurveillanceStation/camera.cgi?api=SYNO.SurveillanceStation.Camera&method=GetInfo&version=1&cameraIds=',
            ),
            'v7' => array(
					'LIST'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&method=List&version=1',
					'ENABLE'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&method=Enable&version=3&cameraIds=',
					'DISABLE'			=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&method=Disable&version=3&cameraIds=',
					'START'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.ExternalRecording&method=Record&version=2&action=start&cameraId=',
					'STOP'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.ExternalRecording&method=Record&version=2&action=stop&cameraId=',
					'STATUT'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&method=GetInfo&version=2&cameraIds=',
					'STARTMOTIONSS'	=> '/webapi/entry.cgi?api="SYNO.SurveillanceStation.Camera.Event"&source=1&version="1"&method="MDParamSave"&keep=true&camId=',
					'STARTMOTIONCM'	=> '/webapi/entry.cgi?api="SYNO.SurveillanceStation.Camera.Event"&source=0&version="1"&method="MDParamSave"&keep=true&camId=',
					'STOPMOTION'		=> '/webapi/entry.cgi?api="SYNO.SurveillanceStation.Camera.Event"&source=-1&version="1"&method="MDParamSave"&keep=true&camId=',
					'STATUTMOTION'		=> '/webapi/entry.cgi?api="SYNO.SurveillanceStation.Camera.Event"&version="1"&method=MotionEnum&camId=',
					'PTZUP'				=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&method=Move&version=1&direction=up&speed=1&moveType=Start&cameraId=',
					'PTZDOWN'			=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&method=Move&version=1&direction=down&speed=1&moveType=Start&cameraId=',
					'PTZLEFT'			=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&method=Move&version=1&direction=left&speed=1&moveType=Start&cameraId=',
					'PTZRIGHT'			=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&method=Move&version=1&direction=right&speed=1&moveType=Start&cameraId=',
					'PTZSTOP'			=> '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&method=Move&version=1&direction=up&speed=1&moveType=Stop&cameraId=',
			)
        );
        return $SYNO_API[$version][$cmd];
    }

    private function statut()
    {
        $session = $this->auth($this->getEqLogic());
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
            $contents = file_get_contents($this->buildUrl().$this->getAPI($version,'STATUT').$this->getIds(), false, $session);
        $this->logout($session);

        $json = json_decode($contents);
        return ($json->data->cameras[0]->enabled == '') ? "0" : "1";
    }

	private function statutMotion()
    {
        $session = $this->auth($this->getEqLogic());
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
            $contents = file_get_contents($this->buildUrl().$this->getAPI($version,'STATUTMOTION').$this->getIds(), false, $session);
        $this->logout($session);

        $json = json_decode($contents);
		if ($json->data->MDParam->source == '-1'){
			return "0";
		}
		elseif ($json->data->MDParam->source == '0' || $json->data->MDParam->source == '1'){
			return "1";
		}
    }

    private function ptzup()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'PTZUP') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }

    private function ptzdown()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'PTZDOWN') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    }    

    private function ptzleft()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'PTZLEFT') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    } 
    
    private function ptzright()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'PTZRIGHT') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    } 

    private function ptzstop()
    {
        $session = $this->auth($this->getEqLogic());
        $cmdConf = $this->getConfiguration('cameras');
        $synoConf = $this->getEqLogic();
        $version = ($synoConf->getConfiguration('v6') == 0) ? 'v7' : 'v6';
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                file_get_contents($this->buildUrl() . $this->getAPI($version,'PTZSTOP') . explode("%", $key)[1], false, $session);
            }
        }
        $this->logout($session);

        surveillanceStation::pull(null);
    } 
  
    private function buildUrl()
    {
        $synoConf = $this->getEqLogic();
        $host = $synoConf->getConfiguration('host');
        $port = $synoConf->getConfiguration('port');
        return $host . ':' . $port;
    }

    private function auth($synoConf)
    {

        $host = $synoConf->getConfiguration('host');
        $port = $synoConf->getConfiguration('port');
        $login = $synoConf->getConfiguration('login');
        $password = $synoConf->getConfiguration('password');

        $ch = curl_init($host . ':' . $port . '/webapi/auth.cgi?api=SYNO.API.Auth&method=Login&version=1&account=' . $login . '&passwd=' . urlencode($password) . '&session=SurveillanceStation');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($result, $header_size);

        if(strstr($body,'error') != null){
            log::add('surveillanceStation', 'error', 'Erreur lors de l\'authentification. Vérifiez vos login et password.');
            throw new Exception('Erreur lors de l\'authentification. Vérifiez vos login et password.');
        }

        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $result, $m);
        parse_str($m[1], $cookies);

        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 15,
                'header' => "Cookie: id=" . $cookies['id'] . ";\r\n"
            ),
            'https' => array(
                'method' => "GET",
                'timeout' => 15,
                'header' => "Cookie: id=" . $cookies['id'] . ";\r\n"
            )
        );
        return stream_context_create($opts);
    }

    private function logout($session)
    {
        file_get_contents($this->buildUrl() . '/webapi/auth.cgi?api=SYNO.API.Auth&method=Logout&version=1&session=SurveillanceStation', false, $session);
    }

    public static function wd_remove_accents($str, $charset='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return $str;
    }


    /**
     * @return string
     */
    private function getIds()
    {
        $cmdConf = $this->getConfiguration('cameras');
        $ids = "";
        foreach (array_keys($cmdConf) as $key) {
            if ($cmdConf[$key] == '1') {
                $ids = $ids.explode("%", $key)[1].',';
            }
        }
        $ids = substr($ids, 0, -1);
        return $ids;
    }

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */
}

?>
