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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    $return = array();
    if (init('action') == 'checkDropbox') {
        $info = surveillanceStation::checkDropbox();
        $return['dropbox'] = $info;
        ajax::success($return);
    }else if (init('action') == 'getCameras') {
        $cameras = surveillanceStation::getCameras(init('eqId'));
        $return['cmd'] = array();
		$return['cmd'] = $cameras;
        ajax::success($return);
    }else if (init('action') == 'mailTester') {
        $contact = surveillanceStation::mailTester(init('eqId'));
		$return['cmd'] = array();
		$return['cmd'] = $contact;
		ajax::success('ok');
}
    else if (init('action') == 'checkSynoSid') {
        $sid = surveillanceStation::getSidFromCache(init('host'));
        ajax::success($sid);
    }
    else if (init('action') == 'synoTester') {
        $sid = surveillanceStation::checkAuth(init('host'),init('port'),init('login'),init('password'));
        ajax::success($sid);
    }

	if (init('action') == 'updateRuby') {
		surveillanceStation::updateRuby();
		ajax::success();
	}
	
    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
