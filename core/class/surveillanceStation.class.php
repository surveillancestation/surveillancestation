<?php

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

/*
SYNO.SurveillanceStation.Camera							: Surveillance Station 6.0-2337
SYNO.SurveillanceStation.SnapShot						: Surveillance Station 6.0-2337
SYNO.SurveillanceStation.PTZ								: Surveillance Station 6.0-2337
SYNO.SurveillanceStation.ExternalRecording	: Surveillance Station 6.0-2337
SYNO.SurveillanceStation.VideoStream				: Surveillance Station 6.3
SYNO.Surveillance.Camera.Event							: Surveillance Station 7.0
SYNO.SurveillanceStation.HomeMode						: Surveillance Station 8.1.0

*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
define('__SSPLGBASE__', dirname(dirname(__DIR__)));

class surveillanceStation extends eqLogic {
	/*     * *************************Attributs****************************** */

	private static $_sid = null;
	private static $_api_info = null;

	/*     * ***********************Methode static*************************** */

	public static function cron5() {
		self::GetStatusCam();
		self::GetStatusDetecMouv();
		self::GetStatusHomeMode();
		self::GetUrlLive();
	}

	public static function event() {
		$cmd = surveillanceStationCmd::byId(init('id'));
		if (!is_object($cmd)) {
			throw new Exception('Commande ID surveillance station inconnu : ' . init('id'));
		}
		if ($cmd->getType() == 'action') {
			$cmd->execCmd(array());
		} else {
			$cmd->event(init('value'));
		}
	}


	/**
     * Verification des configurations du plugins
     */
    public static function checkConfig() {
		log::add('surveillanceStation', 'debug', ' ┌──── Verification des configurations du plugin');
		// Checking snapLocation
		if (config::byKey('snapLocation', 'surveillanceStation') == 'synology') {
			config::save('snapRetention', '', 'surveillanceStation');
			log::add('surveillanceStation', 'debug', ' │  checkConfig::snapRetention Nettoyage des valeurs');
		}
		// Checking Integer fields
		foreach (array('port', 'snapRetention') as $field) {
			if ( ! empty(config::byKey($field, 'surveillanceStation'))) {
				switch($field) {
					case 'port':
						$min_range = 1;
						$max_range = 65535;
					default:
						$min_range = 0;
						$max_range = 9999;
				}
				if(!filter_var(config::byKey($field, 'surveillanceStation'), FILTER_VALIDATE_INT, array('options' => array('min_range' => $min_range, 'max_range' => $max_range)))){
					log::add('surveillanceStation', 'debug', ' │  ERROR : checkConfig::'.$field.' est invalide. La configuration doit être un nombre (entier) compris entre '.$min_range.' et '.$max_range);
					log::add('surveillanceStation', 'debug', ' └────────────');
					throw new Exception(__($field.' est invalide. La configuration doit être un nombre (entier) compris entre '.$min_range.' et '.$max_range, __FILE__));
				} else {
					log::add('surveillanceStation', 'debug', ' │  checkConfig::'.$field.' OK with value ' . config::byKey($field, 'surveillanceStation'));
				}
			}
		}
		log::add('surveillanceStation', 'debug', ' └────────────');
    }


	public static function callUrl($_parameters = null, $_recall = 0) {
		$url = self::getUrl() . '/webapi/' . self::getApi($_parameters['api'], 'path') . '?version=' . self::getApi($_parameters['api'], 'version');
		if ($_parameters !== null && is_array($_parameters)) {
			foreach ($_parameters as $key => $value) {
				$url .= '&' . $key . '=' . urlencode($value);
			}
		}
		log::add('surveillanceStation', 'debug', 'callURL URL -> ' .print_r($url, true));
		$url .= '&_sid=' . self::getSid();
		$http = new com_http($url);
		$result = json_decode($http->exec(15), true);
		if ($result['success'] != true) {
			if (($result['error']['code'] == 105) && $_recall < 3) {
				self::deleteSid();
				self::updateAPI();
				return self::callUrl($_parameters, $_recall + 1);
				log::add('surveillanceStation', 'error', 'callURL retour code -> ' .print_r(self::convertCodeErreur($result['error']['code']), true));
			}
			if (($result['error']['code'] != 105)) {
				log::add('surveillanceStation', 'error', 'callURL retour code -> ' .print_r(self::convertCodeErreur($result['error']['code']), true));
			}
			throw new Exception(__('Appel api : ', __FILE__) . print_r($_parameters, true) . __(',url : ', __FILE__) . $url . ' => ' . print_r($result, true) . __(',code erreur : ', __FILE__) . ' => ' . print_r($result['error']['code'], true));
		}
		return $result;
	}

	public static function callUrlNoVersion($_parameters = null, $_recall = 0) {
		$url = self::getUrl() . '/webapi/' . $_parameters;
		log::add('surveillanceStation', 'debug', 'callURL URL -> ' .print_r($url, true));
		$http = new com_http($url);
		$result = json_decode($http->exec(15), true);
		if ($result['success'] != true) {
			if (($result['error']['code'] == 105) && $_recall < 3) {
				self::deleteSid();
				self::updateAPI();
				return self::callUrlNoVersion($_parameters, $_recall + 1);
				log::add('surveillanceStation', 'error', 'callURL retour code -> ' .print_r(self::convertCodeErreur($result['error']['code']), true));
			}
			if (($result['error']['code'] != 105)) {
				log::add('surveillanceStation', 'error', 'callURL retour code -> ' .print_r(self::convertCodeErreur($result['error']['code']), true));
			}
			throw new Exception(__('Appel api : ', __FILE__) . print_r($_parameters, true) . __(',url : ', __FILE__) . $url . ' => ' . print_r($result, true) . __(',code erreur : ', __FILE__) . ' => ' . print_r($result['error']['code'], true));
		}
		return $result;
	}

	public static function getSid() {
		if (self::$_sid !== null) {
			return self::$_sid;
		}
		if (config::byKey('SYNO.SID.Session', 'surveillancestation') != '') {
			self::$_sid = config::byKey('SYNO.SID.Session', 'surveillancestation');
			return self::$_sid;
		}
		//$url = self::getUrl() . '/webapi/' . self::getApi('SYNO.API.Auth', 'path') . '?api=SYNO.API.Auth&method=Login&version=' . self::getApi('SYNO.API.Auth', 'version') . '&account=' . urlencode(config::byKey('user', 'surveillanceStation')) . '&passwd=' . urlencode(config::byKey('password', 'surveillanceStation')) . '&session=SurveillanceStation&format=sid';
		$url = self::getUrl() . '/webapi/' . self::getApi('SYNO.API.Auth', 'path') . '?api=SYNO.API.Auth&method=login&version=' . self::getApi('SYNO.API.Auth', 'version') . '&account=' . urlencode(config::byKey('user', 'surveillanceStation')) . '&passwd=' . urlencode(config::byKey('password', 'surveillanceStation')) . '&session=SurveillanceStation&format=sid' . '&otp_code=' . urlencode(config::byKey('oauth', 'surveillanceStation')) . '&&enable_device_token=yes';
		$http = new com_http($url);
		$data = json_decode($http->exec(15), true);
		if ($data['success'] != true) {
			throw new Exception(__('Mise à jour des API SYNO.API.Auth en erreur : ', __FILE__) . print_r($data, true));
		}
		config::save('SYNO.SID.Session', $data['data']['sid'], 'surveillancestation');
		self::$_sid = $data['data']['sid'];
		return $data['data']['sid'];
	}

	public static function deleteSid() {
		self::$_sid = null;
		if (config::byKey('SYNO.SID.Session', 'surveillancestation') == '') {
			return;
		}
		$url = self::getUrl() . '/webapi/' . self::getApi('SYNO.API.Auth', 'path') . '?api=SYNO.API.Auth&method=logout&version=' . self::getApi('SYNO.API.Auth', 'version') . '&session=SurveillanceStation&_sid=' . self::getSid();
		$http = new com_http($url);
		$data = json_decode($http->exec(15), true);
		if ($data['success'] != true) {
			throw new Exception(__('Destruction de la session en erreur, code : ', __FILE__) . $url . __(' , code : ', __FILE__) . $data['error']['code']);
		}
		config::remove('SYNO.SID.Session', 'surveillancestation');
	}

	public static function getURL() {
		if (config::byKey('https', 'surveillancestation')) {
			return 'https://' . config::byKey('ip', 'surveillanceStation') . ':' . config::byKey('port', 'surveillanceStation');
		}
		return 'http://' . config::byKey('ip', 'surveillanceStation') . ':' . config::byKey('port', 'surveillanceStation');
	}

	public static function getApi($_api, $_key) {
		if (self::$_api_info == null && (config::byKey('api_info', 'surveillanceStation') == '' || !is_array(config::byKey('api_info', 'surveillanceStation')))) {
			self::updateAPI();
		}
		if (self::$_api_info == null) {
			self::$_api_info = config::byKey('api_info', 'surveillanceStation');
		}
		if (isset(self::$_api_info[$_api][$_key])) {
			return self::$_api_info[$_api][$_key];
		}
		return '';
	}

	public static function updateAPI() {
		$list_API = array(
			'SYNO.API.Auth',
			'SYNO.SurveillanceStation.Info',
			'SYNO.SurveillanceStation.Camera',
			'SYNO.SurveillanceStation.Camera.Event',
			'SYNO.SurveillanceStation.SnapShot',
			'SYNO.SurveillanceStation.Recording',
			'SYNO.SurveillanceStation.HomeMode'
			//'SYNO.SurveillanceStation.PTZ', (retourne une mauvaise version de l'API : 5 au lieu de 4 qui bug), donc 3)
			//'SYNO.SurveillanceStation.ExternalRecording', (retourne une mauvaise version de l'API : 3 au lieu de 2)
		);
		$url = self::getUrl() . '/webapi/query.cgi?api=SYNO.API.Info&method=Query&version=1&query=SYNO.API.Auth,SYNO.SurveillanceStation.';
		$http = new com_http($url);
		$data = json_decode($http->exec(15), true);
		if ($data['success'] != true) {
			throw new Exception(__('Mise à jour des API SYNO.API.Inf en erreur, url : ', __FILE__) . $url . __(' , code : ', __FILE__) . $data['error']['code']);
		}
		$api = array();
		foreach ($list_API as $value) {
			if (!isset($data['data'][$value])) {
				continue;
			}
			$api[$value] = array(
				'path' => $data['data'][$value]['path'],
				'version' => $data['data'][$value]['maxVersion'],
			);
			// contourne le pb de version fournie par l'API.Info
			$api['SYNO.SurveillanceStation.ExternalRecording'] = array(
				'path' => 'entry.cgi',
				'version' => '2',
			);
			$api['SYNO.SurveillanceStation.PTZ'] = array(
				'path' => 'entry.cgi',
				'version' => '3',
			);
		}
		config::save('api_info', $api, 'surveillanceStation');
		log::add('surveillanceStation', 'debug', 'résultat list API -> ' .print_r($api, true));
	}

	public static function discover() {
		self::deleteSid();
		self::updateAPI();
		$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Info', 'method' => 'GetInfo'));
		if ($data['data']['version']['major'] >= '8'){
			$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera', 'method' => 'List'));
			foreach ($data['data']['cameras'] as $camera) {
				$eqLogic = self::byLogicalId('camera' . $camera['id'], 'surveillanceStation');
				if (!is_object($eqLogic)) {
					$eqLogic = new self();
					$eqLogic->setLogicalId('camera' . $camera['id']);
					$eqLogic->setName($camera['newName']);
					$eqLogic->setEqType_name('surveillanceStation');
					$eqLogic->setIsVisible(0);
					$eqLogic->setIsEnable(1);
				}
				$eqLogic->setConfiguration('id', $camera['id']);
				$eqLogic->setConfiguration('model', $camera['model']);
				$eqLogic->setConfiguration('vendor', $camera['vendor']);
				$eqLogic->setConfiguration('ip', $camera['ip']);
				$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Info', 'method' => 'GetInfo'));
				$eqLogic->setConfiguration('versionSS', $data['data']['CMSMinVersion']);
				log::add('surveillanceStation', 'debug', 'Version SS '.$eqLogic->getConfiguration('versionSS'));
				$url = self::getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&version=8&method=GetCapabilityByCamId&cameraId='.$camera['id'].'&_sid='.$eqLogic->getSid();
				$http = new com_http($url);
				$data = json_decode($http->exec(15), true);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible direction '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzDirection']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Home '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzHome']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Speed '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzSpeed']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Pan '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzPan']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Tilt '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzTilt']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Zoom '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzZoom']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible Abs '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzAbs']);
				log::add('surveillanceStation', 'debug', 'résultat PTZ Compatible AutoFocus '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .$data['data']['ptzAutoFocus']);
				if ($data['data']['ptzDirection'] > '0'){$eqLogic->setConfiguration('ptzdirection', 'Oui');} else {$eqLogic->setConfiguration('ptzdirection', 'Non');}
				if ($data['data']['ptzHome'] > '0'){$eqLogic->setConfiguration('ptzHome', 'Oui');} else {$eqLogic->setConfiguration('ptzHome', 'Non');}
				if ($data['data']['ptzSpeed'] > '0'){$eqLogic->setConfiguration('ptzSpeed', 'Oui');} else {$eqLogic->setConfiguration('ptzSpeed', 'Non');}
				if ($data['data']['ptzPan'] > '0'){$eqLogic->setConfiguration('ptzPan', 'Oui');} else {$eqLogic->setConfiguration('ptzPan', 'Non');}
				if ($data['data']['ptzTilt'] > '0'){$eqLogic->setConfiguration('ptzTilt', 'Oui');} else {$eqLogic->setConfiguration('ptzTilt', 'Non');}
				if ($data['data']['ptzZoom'] > '0'){$eqLogic->setConfiguration('ptzZoom', 'Oui');} else {$eqLogic->setConfiguration('ptzZoom', 'Non');}
				if ($data['data']['ptzAbs'] > '0'){$eqLogic->setConfiguration('ptzAbs', 'Oui');} else {$eqLogic->setConfiguration('ptzAbs', 'Non');}
				if ($data['data']['ptzAutoFocus'] > '0'){$eqLogic->setConfiguration('ptzAutoFocus', 'Oui');} else {$eqLogic->setConfiguration('ptzAutoFocus', 'Non');}
				$eqLogic->save();
			}
			self::GetListPreset();
			self::GetListPatrol();
			self::GetStatusCam();
			self::GetStatusDetecMouv();
			self::GetUrlLive();
		} else {
			log::add('surveillanceStation', 'error', 'Votre version de Surveillance Station n\'est pas compatible avec ce plugin. Compatible à partir de la version : 8.0. Ancien plugin, sans maintenance et assistance, disponible ici : https://github.com/surveillancestation/surveillancestation. Merci d\'ouvrir un sujet dédié sur le forum');
		}
	}

	public static function GetStatusCam() {
		$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera', 'method' => 'List'));
		log::add('surveillanceStation', 'debug', 'résultat API Statut Caméra -> ' .print_r($data['data'], true));
		foreach ($data['data']['cameras'] as $camera) {
			$eqLogic = self::byLogicalId('camera' . $camera['id'], 'surveillanceStation');
			if (!is_object($eqLogic)) {
				continue;
			}
			$eqLogic->checkAndUpdateCmd('state', self::convertStatusCam($camera['status']));
			if ($camera['status'] == '3'){
				log::add('surveillanceStation', 'error', 'La caméra est avec l\'ID '.$camera['id'].' est déconnectée');
			}
		}
	}

	public static function GetStatusDetecMouv() {
		foreach (eqLogic::byType('surveillanceStation', true) as $eqLogic) {
			if ($eqLogic->getConfiguration('versionSS') >= '7.0'){
				$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera.Event', 'method' => 'MotionEnum', 'camId' => $eqLogic->getConfiguration('id')));
				log::add('surveillanceStation', 'debug', 'résultat API Statut Detection Mouvement '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .print_r($data['data'], true));
				$eqLogic->checkAndUpdateCmd('motion_status', self::convertStatusDetecMouv($data['data']['MDParam']['source']));
			}
		}
	}

	public static function GetStatusHomeMode() {
		foreach (eqLogic::byType('surveillanceStation', true) as $eqLogic) {
			if ($eqLogic->getConfiguration('versionSS') >= '8.1'){
				$data = self::callUrl(array('api' => 'SYNO.SurveillanceStation.HomeMode', 'method' => 'GetInfo'));
				log::add('surveillanceStation', 'debug', 'résultat API Home Mode : '.print_r(self::convertStatusHomeMode($data['data']['on']), true));
				$eqLogic->checkAndUpdateCmd('homemode_status', self::convertStatusHomeMode($data['data']['on']));
				$eqLogic->refreshWidget();
			}
		}
	}

	public static function GetListPreset() {
		foreach (eqLogic::byType('surveillanceStation', true) as $eqLogic) {
			if($eqLogic->getConfiguration('ptzdirection') == 'Oui'){
				$url = $eqLogic->getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&version=1&method=ListPreset&cameraId='.$eqLogic->getConfiguration('id').'&_sid='.$eqLogic->getSid();
				$data = file_get_contents($url);
				$presets = json_decode($data, true);
				$listselectpreset = '';
				foreach ($presets['data']['presets'] as $preset) {
					$listselectpreset .= $preset['id']."|".$preset['name'].";";
				}
				$cmd = $eqLogic->getCmd('action', 'ptz_preset_start');
				if (!is_object($cmd)) {
					$cmd = new surveillanceStationCmd();
					$cmd->setName(__('PTZ Position prédéfinie', __FILE__));
					$cmd->setOrder(18);
				}
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setLogicalId('ptz_preset_start');
				$cmd->setType('action');
				$cmd->setSubtype('select');
				$cmd->setConfiguration('listValue',$listselectpreset);
				$cmd->setIsVisible(0);
				$cmd->save();
				log::add('surveillanceStation', 'debug', 'résultat discovery Preset cam '.$eqLogic->getConfiguration('id').' -> ' .print_r($listselectpreset, true));
			}
		}
	}

	public static function GetListPatrol() {
		foreach (eqLogic::byType('surveillanceStation', true) as $eqLogic) {
			if($eqLogic->getConfiguration('ptzdirection') == 'Oui'){
				$url = $eqLogic->getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.PTZ&version=1&method=ListPatrol&cameraId='.$eqLogic->getConfiguration('id').'&_sid='.$eqLogic->getSid();
				$data = file_get_contents($url);
				$patrouilles = json_decode($data, true);
				$listselectpatrol = '';
				foreach ($patrouilles['data']['patrols'] as $patrouille) {
					$listselectpatrol .= $patrouille['id']."|".$patrouille['name'].";";
				}
				$cmd = $eqLogic->getCmd('action', 'ptz_patrol_start');
				if (!is_object($cmd)) {
					$cmd = new surveillanceStationCmd();
					$cmd->setName(__('PTZ Patrouille', __FILE__));
					$cmd->setOrder(19);
				}
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setLogicalId('ptz_patrol_start');
				$cmd->setType('action');
				$cmd->setSubtype('select');
				$cmd->setConfiguration('listValue',$listselectpatrol);
				$cmd->setIsVisible(0);
				$cmd->save();
				log::add('surveillanceStation', 'debug', 'résultat discovery Patrol caméra '.$eqLogic->getName(). '( id:'.$eqLogic->getConfiguration('id').') -> ' .print_r($listselectpatrol, true));
			}
		}
	}



	public static function GetUrlLive() {
		foreach (eqLogic::byType('surveillanceStation', true) as $eqLogic) {
			if ($eqLogic->getConfiguration('versionSS') >= '6.3'){
				$statutcam = $eqLogic->getCmd(null,'state')->execCmd();
				if($eqLogic->getConfiguration('choixlive') == '1' && $statutcam == 'Activée'){

					// Get RTSP LiveURL
					$response = self::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera', 'method' => 'GetLiveViewPath', 'idList' => $eqLogic->getConfiguration('id'))); // Method available since v8.0 (2017)
					log::add('surveillanceStation', 'debug', 'API Response '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .var_export($response, TRUE));
					$urlLive = $response['data']['0']['rtspPath'];
					log::add('surveillanceStation', 'debug', 'résultat URL Live RTSP '.$urlLive);
					$eqLogic->checkAndUpdateCmd('path_url_live_rtsp', $urlLive);
					// End RTSP LiveURL

					// URL Live for Dashboard - Updated by Marc GUYARD (@mguyard)
					$urlLive = surveillanceStation::forgeURLExternal($response['data']['0']['mjpegHttpPath']);
					log::add('surveillanceStation', 'debug', 'résultat URL Live '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .print_r($urlLive, true));
					$eqLogic->checkAndUpdateCmd('path_url_live', $urlLive);
					
					$eqLogic->refreshWidget();
				}
				else if ($eqLogic->getConfiguration('choixlive') == '0'){
					$eqLogic->checkAndUpdateCmd('path_url_live', '');
					$eqLogic->checkAndUpdateCmd('path_url_live_rtsp', '');
					log::add('surveillanceStation', 'debug', 'URL Live final : aucune, live désactivé dans la config');
					$eqLogic->refreshWidget();
				}
				else if ($statutcam == 'Désactivée' || $statutcam == 'Déconnectée'){
					$eqLogic->checkAndUpdateCmd('path_url_live', 'plugins/surveillanceStation/core/img/cameramini_off.png');
					$eqLogic->checkAndUpdateCmd('path_url_live_rtsp', 'plugins/surveillanceStation/core/img/cameramini_off.png');
					log::add('surveillanceStation', 'debug', 'URL Live final : aucune, caméra désactivée');
					$eqLogic->refreshWidget();
				}
			}
		}
	}

	/**
	 * Function to convert Synology URL received by a URL matching configuration (in case of external access for example)
	 * Author : Marc GUYARD (@mguyard)
	 * @param string $url
	 * @return string
	 */
	private static function forgeURLExternal($url) {
		log::add(__CLASS__, 'debug', 'URL received : ' . $url);
		$urlUpdated = $url;
		$urlParsed = parse_url($url);
		// Replace Scheme
		$configScheme = config::byKey('https', 'surveillanceStation') == 1 ? 'https' : 'http';
		if ($configScheme != $urlParsed['scheme']) {
			$urlUpdated = str_replace($urlParsed['scheme'], $configScheme, $urlUpdated);
		}
		// Replace host
		if (config::byKey('ip', 'surveillanceStation') != $urlParsed['host']) {
			$urlUpdated = str_replace($urlParsed['host'], config::byKey('ip', 'surveillanceStation'), $urlUpdated);
		}
		// Replace Port
		$configPort = empty(config::byKey('port', 'surveillanceStation')) ? '443' : config::byKey('port', 'surveillanceStation');
		if (intval($configPort) != $urlParsed['port']) {
			$urlUpdated = str_replace($urlParsed['port'], $configPort, $urlUpdated);
		}
		log::add(__CLASS__, 'debug', 'URL modified based on configuration : ' . $urlUpdated);
		return htmlentities($urlUpdated, ENT_COMPAT);
	}

	public function getSnapshots($url, $filetype) {
		// Define Timestamp NOW
		$date = new \DateTime('now', new \DateTimeZone(config::byKey('timezone')));
		// Create storage folders if don't exist
		if (!is_dir(__SSPLGBASE__.'/data/captures/'.strval($this->getLogicalId()))) {
			mkdir(__SSPLGBASE__.'/data/captures/'.strval($this->getLogicalId()), 0766, True);
		}
		// Purge Video up to the limit
		self::purgeSnapshots();
		// Define full storage path
		$storePath = __SSPLGBASE__.'/data/captures/'.strval($this->getLogicalId()).'/'.$date->format('Y-m-d_His').'.'.$filetype;
		// Store Snapshot or Video
		$opts=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		$content = file_get_contents($url, false, stream_context_create($opts));
		$storage = fopen($storePath, "wb");
		fwrite($storage, $content);
		fclose($storage);
		return $storePath;
	}


	private static function purgeSnapshots() {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__SSPLGBASE__.'/data/captures/'));
        $files = array();
        foreach ($rii as $file) {
            // Skip hidden files and directories.
            if ($file->getFilename()[0] === '.') {
                continue;
            }
            if ($file->isDir()){
                continue;
            }
            $files[] = array(
                'filefull' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'ctime' => $file->getCTime(),
                'mtime' => $file->getMTime(),
                'size' => $file->getSize()
            );
        }
        // Trie les fichiers du plus ancien au plus récent
        usort($files, function ($item1, $item2) {
            return $item1['ctime'] <=> $item2['ctime'];
        });
        log::add('surveillanceStation', 'debug', 'purgeVideos::ListAllVideos - ' . var_export($files, true));
        $countArray = count($files);
        log::add('surveillanceStation', 'debug', 'purgeVideos::CountVideos - Il y a actuellement ' . $countArray . ' vidéos sur '. config::byKey('video_retention', 'surveillanceStation') .' stockables (retention configuré dans le plugin)');
        // Si il y a plus de videos stocké que la retention autorisée
        if ($countArray > intval(config::byKey('video_retention', 'surveillanceStation', '10'))) {
            $nbToDelete = $countArray - intval(config::byKey('snapRetention', 'surveillanceStation', '10'));
            for ($entry = 0; $entry <= $nbToDelete - 1; $entry++) {
                unlink($files[$entry]['filefull']);
                log::add('surveillanceStation', 'info', 'purgeVideos::Delete Suppression du fichier ' . $files[$entry]['filefull']);
            }
        }
    }



/*
	public function SnapshotSend() {
		$urlSnapshot = $this->getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.SnapShot&version=1&method=TakeSnapshot&dsId=0&camId='.$this->getConfiguration('id').'&_sid='.$this->getSid();
		$data = file_get_contents($urlSnapshot);
		$dataSnapShot = json_decode($data, true);
		$idSnapShot = $dataSnapShot['data']['id'];
		log::add('surveillanceStation', 'debug', 'résultat ID Snapshot '.$this->getName(). '(id:'.$this->getConfiguration('id').') -> ' .print_r($idSnapShot, true));
		$urlRecupSnapshot = $this->getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.SnapShot&version=1&method=LoadSnapshot&id='.$idSnapShot.'&imgSize=2&_sid='.$this->getSid();
		log::add('surveillanceStation', 'debug', 'résultat URL du Snapshot '.$this->getName(). '(id:'.$this->getConfiguration('id').') -> ' .print_r($urlRecupSnapshot, true));
	}
*/
	public static function convertStatusHomeMode($_state) {
		switch ($_state) {
			case 0:
				return __('Désactivé', __FILE__);
			case '':
				return __('Désactivé', __FILE__);
			case 1:
				return __('Activé', __FILE__);
		}
		return __('Inconnu', __FILE__);
	}

	public static function convertStatusDetecMouv($_state) {
		switch ($_state) {
			case -1:
				return __('Désactivée', __FILE__);
			case 0:
				return __('Activée (par caméra)', __FILE__);
			case 1:
				return __('Activée (par SS)', __FILE__);
		}
		return __('Inconnu', __FILE__);
	}

	public static function convertCodeErreur($_state) {
		switch ($_state) {
			case 100;
				return __('Unknown error', __FILE__);
			case 101;
				return __('Invalid parameters or The account parameter is not specified', __FILE__);
			case 102;
				return __('API does not exist', __FILE__);
			case 103;
				return __('Method does not exist', __FILE__);
			case 104;
				return __('This API version is not supported', __FILE__);
			case 105;
				return __('Insufficient user privilege', __FILE__);
			case 106;
				return __('Connection time out', __FILE__);
			case 107;
				return __('Multiple login detected', __FILE__);
			case 117;
				return __('Vérifier vos droits, certaines fonctions demandent le privilège Directeur', __FILE__);
			case 400;
				return __('Invalid password or Execution failed', __FILE__);
			case 401;
				return __('Parameter invalid or Guest or disabled account', __FILE__);
			case 402;
				return __('Permission denied or Camera disabled or IO module disabled', __FILE__);
			case 403;
				return __('One time password not specified or Insufficient license', __FILE__);
			case 404;
				return __('One time password authenticate failed or Codec acitvation failed', __FILE__);
			case 405;
				return __('App portal incorrect or CMS server connection failed', __FILE__);
			case 406;
				return __('OTP code enforced', __FILE__);
			case 407;
				return __('Max Tries (if auto blocking is set to true) or CMS closed', __FILE__);
			case 408;
				return __('Password Expired Can not Change', __FILE__);
			case 409;
				return __('Password Expired', __FILE__);
			case 410;
				return __('Service is not enabled or Password must change (when first time use or after reset password by admin)', __FILE__);
			case 411;
				return __('Account Locked (when account max try exceed)', __FILE__);
			case 412;
				return __('Need to add license', __FILE__);
			case 413;
				return __('Reach the maximum of platform', __FILE__);
			case 414;
				return __('Some events not exist', __FILE__);
			case 415;
				return __('message connect failed', __FILE__);
			case 417;
				return __('Test Connection Error', __FILE__);
			case 418;
				return __('Object is not exist or The VisualStation ID does not exist', __FILE__);
			case 419;
				return __('Visualstation name repetition', __FILE__);
			case 439;
				return __('Too many items selected', __FILE__);
		}
		return __('Inconnu', __FILE__);
	}

	public static function convertStatusCam($_state) {
		switch ($_state) {
			case 1:
				return __('Activée', __FILE__);
			case 2:
				return __('Supprimée', __FILE__);
			case 3:
				return __('Déconnectée', __FILE__);
			case 4:
				return __('Indisponible', __FILE__);
			case 5:
				return __('Prête', __FILE__);
			case 6:
				return __('Inaccessible', __FILE__);
			case 7:
				return __('Désactivée', __FILE__);
			case 8:
				return __('Non reconnue', __FILE__);
			case 9:
				return __('Parametrage', __FILE__);
			case 10:
				return __('Serveur déconnecté', __FILE__);
			case 11:
				return __('Migration', __FILE__);
			case 12:
				return __('Autre', __FILE__);
			case 13:
				return __('Stockage retiré', __FILE__);
			case 14:
				return __('Arrêt', __FILE__);
			case 15:
				return __('Historique de connexion échoué', __FILE__);
			case 16:
				return __('Non autorisé', __FILE__);
			case 17:
				return __('Erreur RTSP', __FILE__);
			case 18:
				return __('Aucune video', __FILE__);
		}
		return __('Inconnu', __FILE__);
	}

	public static $_widgetPossibility = array('custom' => true);

	public function toHtml($_version = 'dashboard') {
		$version = jeedom::versionAlias($_version);
		$replace = $this->preToHtml($_version, array(), true);

		$statecam = $this->getCmd(null,'state');
		$replace['#statecam#'] = (is_object($statecam)) ? $statecam->execCmd() : '';
		$replace['#statecamid#'] = is_object($statecam) ? $statecam->getId() : '';

		$activecam = $this->getCmd('action', 'enable');
		$replace['#activecamid#'] = is_object($activecam) ? $activecam->getId() : '';
		$replace['#activecam_display#'] = (is_object($activecam) && $activecam->getIsVisible()) ? "#activecam_display#" : "none";

		$desactivecam = $this->getCmd('action', 'disable');
		$replace['#desactivecamid#'] = is_object($desactivecam) ? $desactivecam->getId() : '';
		$replace['#desactivecam_display#'] = (is_object($desactivecam) && $desactivecam->getIsVisible()) ? "#desactivecam_display#" : "none";

		$recordstart = $this->getCmd('action', 'record_start');
		$replace['#recordstartid#'] = is_object($recordstart) ? $recordstart->getId() : '';
		$replace['#recordstart_display#'] = (is_object($recordstart) && $recordstart->getIsVisible()) ? "#recordstart_display#" : "none";

		$recordstop = $this->getCmd('action', 'record_stop');
		$replace['#recordstopid#'] = is_object($recordstop) ? $recordstop->getId() : '';
		$replace['#recordstop_display#'] = (is_object($recordstop) && $recordstop->getIsVisible()) ? "#recordstop_display#" : "none";

		$motionstartss = $this->getCmd('action', 'motion_start_ss');
		$replace['#motionstartssid#'] = is_object($motionstartss) ? $motionstartss->getId() : '';

		$motionstartcam = $this->getCmd('action', 'motion_start_cam');
		$replace['#motionstartcamid#'] = is_object($motionstartcam) ? $motionstartcam->getId() : '';

		$motionstop = $this->getCmd('action', 'motion_stop');
		$replace['#motionstopid#'] = is_object($motionstop) ? $motionstop->getId() : '';

		$snapshot = $this->getCmd('action', 'snapshot');
		$replace['#snapshotid#'] = is_object($snapshot) ? $snapshot->getId() : '';
		$replace['#snapshot_display#'] = (is_object($snapshot) && $snapshot->getIsVisible()) ? "#snapshot_display#" : "none";

		$ptzright = $this->getCmd('action', 'ptz_right');
		$replace['#ptzrightid#'] = is_object($ptzright) ? $ptzright->getId() : '';
		$replace['#ptzright_display#'] = (is_object($ptzright) && $ptzright->getIsVisible()) ? "#ptzright_display#" : "none";

		$ptzdown = $this->getCmd('action', 'ptz_down');
		$replace['#ptzdownid#'] = is_object($ptzdown) ? $ptzdown->getId() : '';
		$replace['#ptzdown_display#'] = (is_object($ptzdown) && $ptzdown->getIsVisible()) ? "#ptzdown_display#" : "none";

		$ptzup = $this->getCmd('action', 'ptz_up');
		$replace['#ptzupid#'] = is_object($ptzup) ? $ptzup->getId() : '';
		$replace['#ptzup_display#'] = (is_object($ptzup) && $ptzup->getIsVisible()) ? "#ptzup_display#" : "none";

		$ptzleft = $this->getCmd('action', 'ptz_left');
		$replace['#ptzleftid#'] = is_object($ptzleft) ? $ptzleft->getId() : '';
		$replace['#ptzleft_display#'] = (is_object($ptzleft) && $ptzleft->getIsVisible()) ? "#ptzleft_display#" : "none";

		$ptzhome = $this->getCmd('action', 'ptz_home');
		$replace['#ptzhomeid#'] = is_object($ptzhome) ? $ptzhome->getId() : '';
		$replace['#ptzhome_display#'] = (is_object($ptzhome) && $ptzhome->getIsVisible()) ? "#ptzhome_display#" : "none";

		$ptzstop = $this->getCmd('action', 'ptz_stop');
		$replace['#ptzstopid#'] = is_object($ptzstop) ? $ptzstop->getId() : '';
		$replace['#ptzstop_display#'] = (is_object($ptzstop) && $ptzstop->getIsVisible()) ? "#ptzstop_display#" : "none";

		$motionstatus = $this->getCmd(null, 'motion_status');
		$replace['#motionstatus#'] = (is_object($motionstatus)) ? $motionstatus->execCmd() : '';
		$replace['#motionstatusid#'] = is_object($motionstatus) ? $motionstatus->getId() : '';
		$replace['#motion_display#'] = is_object($motionstatus) ? "#motion_display#" : "none";

		$homemodestatus = $this->getCmd(null, 'homemode_status');
		$replace['#homemodestatus#'] = (is_object($homemodestatus)) ? $homemodestatus->execCmd() : '';
		$replace['#homemodestatusid#'] = is_object($homemodestatus) ? $homemodestatus->getId() : '';
		$replace['#homemode_display#'] = is_object($homemodestatus) ? "#homemode_display#" : "none";

		$urllive = $this->getCmd(null, 'path_url_live');
		$replace['#urllive#'] = (is_object($urllive)) ? $urllive->execCmd() : '';
		$replace['#urlliveid#'] = is_object($urllive) ? $urllive->getId() : '';
		$replace['#urllive_display#'] = (is_object($urllive) && $urllive->getIsVisible()) ? "#urllive_display#" : "none";

		$replace['#ptz_display#'] = ($this->getConfiguration('ptzdirection') == 'Oui') ? "#ptz_display#" : "none";
		$replace['#actions_display#'] = ($this->getConfiguration('choixactions') == '1') ? "#actions_display#" : "none";
		$replace['#statuts_display#'] = ($this->getConfiguration('choixstatuts') == '1') ? "#statuts_display#" : "none";

		$parameters = $this->getDisplay('parameters');
		if (is_array($parameters)) {
		    foreach ($parameters as $key => $value) {
		        $replace['#' . $key . '#'] = $value;
		    }
		}

		$commandes = template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'surveillanceStation_action', 'surveillanceStation'));
		$replace['#action#'] = $commandes;
		return $this->postToHtml($version, template_replace($replace, getTemplate('core', jeedom::versionAlias($version), 'surveillanceStation', 'surveillanceStation')));
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preUpdate() {
		$this->setCategory('security', 1);
	}

	public function postSave() {
		$cmd = $this->getCmd(null, 'state');
		if (!is_object($cmd)) {
			$cmd = new surveillanceStationCmd();
			$cmd->setLogicalId('state');
			$cmd->setName(__('Status caméra', __FILE__));
			$cmd->setOrder(1);
		}
		$cmd->setType('info');
		$cmd->setSubType('string');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setIsVisible(1);
		$cmd->save();
		$state_id = $cmd->getId();

		$cmd = $this->getCmd('action', 'enable');
		if (!is_object($cmd)) {
			$cmd = new surveillanceStationCmd();
			$cmd->setName(__('Activer', __FILE__));
			$cmd->setOrder(2);

		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('enable');
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setIsVisible(1);
		$cmd->save();

		$cmd = $this->getCmd('action', 'disable');
		if (!is_object($cmd)) {
			$cmd = new surveillanceStationCmd();
			$cmd->setName(__('Désactiver', __FILE__));
			$cmd->setOrder(3);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('disable');
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setIsVisible(1);
		$cmd->save();

		$cmd = $this->getCmd('action', 'record_start');
		if (!is_object($cmd)) {
			$cmd = new surveillanceStationCmd();
			$cmd->setName(__('Start record', __FILE__));
			$cmd->setOrder(4);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('record_start');
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setIsVisible(1);
		$cmd->save();

		$cmd = $this->getCmd('action', 'record_stop');
		if (!is_object($cmd)) {
			$cmd = new surveillanceStationCmd();
			$cmd->setName(__('Stop record', __FILE__));
			$cmd->setOrder(5);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('record_stop');
		$cmd->setType('action');
		$cmd->setSubtype('other');
		$cmd->setIsVisible(1);
		$cmd->save();

		if ($this->getConfiguration('versionSS') >= '7.0'){
			$cmd = $this->getCmd('action', 'motion_start_ss');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Détection mouvement start par SS', __FILE__));
				$cmd->setOrder(6);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('motion_start_ss');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(0);
			$cmd->save();

			$cmd = $this->getCmd('action', 'motion_start_cam');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Détection mouvement start par Caméra', __FILE__));
				$cmd->setOrder(7);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('motion_start_cam');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(0);
			$cmd->save();

			$cmd = $this->getCmd('action', 'motion_stop');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Détection mouvement stop', __FILE__));
				$cmd->setOrder(8);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('motion_stop');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(0);
			$cmd->save();

			$cmd = $this->getCmd(null, 'motion_status');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setLogicalId('motion_status');
				$cmd->setName(__('Détection mouvement', __FILE__));
				$cmd->setOrder(9);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->save();
			$motion_status_id = $cmd->getId();
		}

		if($this->getConfiguration('ptzdirection') == 'Oui'){
			if($this->getConfiguration('ptzHome') == 'Oui'){
				$cmd = $this->getCmd('action', 'ptz_home');
				if (!is_object($cmd)) {
					$cmd = new surveillanceStationCmd();
					$cmd->setName(__('PTZ Home', __FILE__));
					$cmd->setOrder(10);
				}
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('ptz_home');
				$cmd->setType('action');
				$cmd->setSubtype('other');
				$cmd->setIsVisible(1);
				$cmd->save();
			}

			$cmd = $this->getCmd('action', 'ptz_left');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('PTZ Gauche', __FILE__));
				$cmd->setOrder(11);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('ptz_left');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();

			$cmd = $this->getCmd('action', 'ptz_up');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('PTZ Haut', __FILE__));
				$cmd->setOrder(12);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('ptz_up');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();

			$cmd = $this->getCmd('action', 'ptz_down');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('PTZ Bas', __FILE__));
				$cmd->setOrder(13);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('ptz_down');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();

			$cmd = $this->getCmd('action', 'ptz_right');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('PTZ Droite', __FILE__));
				$cmd->setOrder(14);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('ptz_right');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();

			$cmd = $this->getCmd('action', 'ptz_stop');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('PTZ Stop', __FILE__));
				$cmd->setOrder(15);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('ptz_stop');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();
			}

			$cmd = $this->getCmd('action', 'snapshot');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Instantané', __FILE__));
				$cmd->setOrder(16);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('snapshot');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(1);
			$cmd->save();

			/* Code ajouté par Rémy JACOB le 25/12/2020 à partir des infos de https://community.jeedom.com/t/surveillance-station-telegram/31050/4 */
			$cmd = $this->getCmd(null, 'snapshotsendURL');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('snapshotsendURL');
				$cmd->setOrder(21);
			}
			$cmd->setName(__('URL instantané', __FILE__));
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->save();
			/* Fin du code ajouté */
		
		if ($this->getConfiguration('versionSS') >= '8.1'){
			$cmd = $this->getCmd('action', 'homemode_start');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Active Home Mode', __FILE__));
				$cmd->setOrder(17);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('homemode_start');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(0);
			$cmd->save();

			$cmd = $this->getCmd('action', 'homemode_stop');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setName(__('Désactive Home Mode', __FILE__));
				$cmd->setOrder(18);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('homemode_stop');
			$cmd->setType('action');
			$cmd->setSubtype('other');
			$cmd->setIsVisible(0);
			$cmd->save();

			$cmd = $this->getCmd(null, 'homemode_status');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setLogicalId('homemode_status');
				$cmd->setName(__('Home Mode', __FILE__));
				$cmd->setOrder(19);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->save();
			$motion_status_id = $cmd->getId();
		}

		if ($this->getConfiguration('versionSS') >= '6.3'){
			$cmd = $this->getCmd(null, 'path_url_live');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setLogicalId('path_url_live');
				$cmd->setName(__('URL Live', __FILE__));
				$cmd->setOrder(1);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->save();
			$path_url_live = $cmd->getId();
		}

		if ($this->getConfiguration('versionSS') >= '6.3'){
			$cmd = $this->getCmd(null, 'path_url_live_rtsp');
			if (!is_object($cmd)) {
				$cmd = new surveillanceStationCmd();
				$cmd->setLogicalId('path_url_live_rtsp');
				$cmd->setName(__('URL Live RTSP', __FILE__));
				$cmd->setOrder(1);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(0);
			$cmd->save();
			$path_url_live_rtsp = $cmd->getId();
		}

		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new surveillanceStationCmd();
		}
		$refresh->setName(__('Rafraîchir', __FILE__));
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setIsVisible(0);
		$refresh->save();

		self::GetStatusCam();
		self::GetStatusDetecMouv();
		self::GetUrlLive();
	}

	/*     * **********************Getteur Setteur*************************** */
}

class surveillanceStationCmd extends cmd {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => false);
	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		if ($this->getLogicalId() != '') {
			return true;
		}
		return false;
	}

	public function execute($_options = array()) {
		if ($this->getType() == 'info') {
			return;
		}
		$eqLogic = $this->getEqLogic();
		$statecam = $eqLogic->getCmd(null,'state')->execCmd();

		if ($this->getLogicalId() == 'enable') {
			log::add('surveillanceStation', 'debug', 'lancement de l\'action  enable caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
			$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.Camera', 'method' => 'Enable', 'idList' => $eqLogic->getConfiguration('id')));
			sleep(5);
			$eqLogic->GetStatusCam();
			$eqLogic->GetUrlLive();
		}
		if ($this->getLogicalId() == 'disable') {
			log::add('surveillanceStation', 'debug', 'lancement de l\'action  disable caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
			$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.Camera', 'method' => 'Disable', 'idList' => $eqLogic->getConfiguration('id')));
			$eqLogic->GetStatusCam();
			$eqLogic->GetUrlLive();
		}
		if ($this->getLogicalId() == 'record_start') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Record Start '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.ExternalRecording', 'method' => 'Record', 'action' => 'start', 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'record_stop') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Record Stop '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.ExternalRecording', 'method' => 'Record', 'action' => 'stop', 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'motion_start_ss') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Motion Start par SS '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.Camera.Event', 'source' => '1', 'method' => 'MDParamSave', 'keep' => 'true', 'camId' => $eqLogic->getConfiguration('id')));
				sleep(5);
				$eqLogic->GetStatusDetecMouv();
				$eqLogic->refreshWidget();
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'motion_start_cam') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Motion Start par Caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				surveillanceStation::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera.Event', 'source' => '0', 'method' => 'MDParamSave', 'keep' => 'true', 'camId' => $eqLogic->getConfiguration('id')));
				sleep(5);
				$eqLogic->GetStatusDetecMouv();
				$eqLogic->refreshWidget();
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'motion_stop') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Motion Stop '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				surveillanceStation::callUrl(array('api' => 'SYNO.SurveillanceStation.Camera.Event', 'source' => '-1', 'method' => 'MDParamSave', 'keep' => 'false', 'camId' => $eqLogic->getConfiguration('id')));
				sleep(5);
				$eqLogic->GetStatusDetecMouv();
				$eqLogic->refreshWidget();
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_home') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Home caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
				$eqLogic->callUrlNoVersion('entry.cgi?version=5&api=SYNO.SurveillanceStation.PTZ&method=Home&speed='.$eqLogic->getConfiguration('speedptz').'&cameraId='.$eqLogic->getConfiguration('id').'&_sid='.$eqLogic->getSid());
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_left') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Left caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'Move', 'direction' => 'left', 'moveType' => 'Start', 'speed' => $eqLogic->getConfiguration('speedptz'), 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_up') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Up caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'Move', 'direction' => 'up', 'moveType' => 'Start', 'speed' => $eqLogic->getConfiguration('speedptz'), 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_down') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Down caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'Move', 'direction' => 'down', 'moveType' => 'Start', 'speed' => $eqLogic->getConfiguration('speedptz'), 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_right') {
			if ($statecam == 'Activée'){
			log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Right caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
			$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'Move', 'direction' => 'right', 'moveType' => 'Start', 'speed' => $eqLogic->getConfiguration('speedptz'), 'cameraId' => $eqLogic->getConfiguration('id')));
		} else {
			throw new Exception('Commande impossible, la caméra est désactivée');
		}
		}
		if ($this->getLogicalId() == 'ptz_stop') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  PTZ Stop caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> speed -> ' .$eqLogic->getConfiguration('speedptz'));
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'Move', 'direction' => 'right', 'moveType' => 'Stop', 'speed' => $eqLogic->getConfiguration('speedptz'), 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_patrol_start') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Patrouille caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> preset ID -> ' .$_options['select']);
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'RunPatrol', 'patrolId' => $_options['select'], 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		if ($this->getLogicalId() == 'ptz_preset_start') {
			if ($statecam == 'Activée'){
				log::add('surveillanceStation', 'debug', 'lancement de l\'action  Position prédéfinie caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> preset ID -> ' .$_options['select']);
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.PTZ', 'method' => 'GoPreset', 'presetId' => $_options['select'], 'cameraId' => $eqLogic->getConfiguration('id')));
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		/* Code ajouté par Rémy JACOB le 25/12/2020 à partir des infos de https://community.jeedom.com/t/surveillance-station-telegram/31050/4
			2021/06/05 - Modify by Mguyard to generate snapshotsendURL directly with snapshot action and not need of command snapshotsend - More simple for users 
			2021/06/05 - Modify by Mguyard to download snapshot file in Jeedom */
		if ($this->getLogicalId() == 'snapshot') {
			if ($statecam == 'Activée'){
				// Generate snapshot
				log::add('surveillanceStation', 'debug', 'lancement de l\'action Instantané caméra '.$eqLogic->getName(). '(id:'.$eqLogic->getConfiguration('id').')');
				$response = $eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.SnapShot', 'method' => 'TakeSnapshot', 'dsId' => '0', 'camId' => $eqLogic->getConfiguration('id')));
				// Get and store snapshot URL in Synology
				$idSnapShot = $response['data']['id'];
				log::add('surveillanceStation', 'debug', 'résultat ID Snapshot '.$this->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .var_export($idSnapShot, true));
				$urlRecupSnapshot = $eqLogic->getUrl() . '/webapi/entry.cgi?api=SYNO.SurveillanceStation.SnapShot&version=1&method=LoadSnapshot&id='.$idSnapShot.'&imgSize=2&_sid='.$eqLogic->getSid();
				log::add('surveillanceStation', 'debug', 'résultat URL du Snapshot '.$this->getName(). '(id:'.$eqLogic->getConfiguration('id').') -> ' .var_export($urlRecupSnapshot, true));
				switch (config::byKey('snapLocation', 'surveillanceStation')) {
					case 'synology':
						$eqLogic->checkAndUpdateCmd('snapshotsendURL', $urlRecupSnapshot);
						break;
					case 'jeedom':
						$snapshotPath = $eqLogic->getSnapshots($urlRecupSnapshot, 'jpg');
						$eqLogic->checkAndUpdateCmd('snapshotsendURL', $snapshotPath);
						break;
				}
			} else {
				throw new Exception('Commande impossible, la caméra est désactivée');
			}
		}
		/* Fin du code ajouté */


		if ($this->getLogicalId() == 'homemode_start') {
				log::add('surveillanceStation', 'debug', 'lancement de l\'action active Home Mode');
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.HomeMode', 'method' => 'Switch', 'on' => 'true'));
				$eqLogic->GetStatusHomeMode();
				$eqLogic->refreshWidget();
		}
		if ($this->getLogicalId() == 'homemode_stop') {
				log::add('surveillanceStation', 'debug', 'lancement de l\'action désactive Home Mode');
				$eqLogic->callUrl(array('api' => 'SYNO.SurveillanceStation.HomeMode', 'method' => 'Switch', 'on' => 'false'));
				$eqLogic->GetStatusHomeMode();
		}
		if ($this->getLogicalId() == 'refresh') {
			$eqLogic->GetStatusCam();
			$eqLogic->GetStatusDetecMouv();
			$eqLogic->GetStatusHomeMode();
			$eqLogic->GetUrlLive();
		}
	}
	/*     * **********************Getteur Setteur*************************** */
}
