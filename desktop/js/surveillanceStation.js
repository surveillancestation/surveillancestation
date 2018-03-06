
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


var cameras = [];
var currentEq = "";

$(function() {


    $("#searchCameras").on('click', function(event) {
        searchCameras($(this).attr('data-eqLogic_id'),$('#synoHost').val(),$('#synoPort').val(),$('#synoLogin').val(),$('#synoPassword').val());
    });

    $("#btnDropboxToken").on('click', function(event) {
       checkDropboxToken();
    });

    $("#btnMailTester").on('click', function(event) {
        checkMail();
    });

    $("#btnSynoTester").on('click', function(event) {
        $("#synoCheckReturn").empty();
       checkSyno();
    });

    $(".isliveEq").on('click', function(event) {
        $('#table_cmd tr:gt(0)').remove();
    });


    setInterval(function () {
        cameras = [];

        searchCameras($(".li_eqLogic.active").attr('data-eqLogic_id'));
    }, 30*1000);
    //
    checkAlreadyConfigured();
    //
    $(".li_eqLogic").on('click', function(event) {
        $("#synoCheckReturn").empty();
        if(currentEq != $(".li_eqLogic.active").attr('data-eqLogic_id')){
            cameras = [];
            currentEq = $(".li_eqLogic.active").attr('data-eqLogic_id');
        }
        searchCameras(currentEq);
    });

});

function checkAlreadyConfigured(){
    if(isset($(".li_eqLogic.active").attr('data-eqLogic_id'))){
        currentEq = $(".li_eqLogic.active").attr('data-eqLogic_id');
        searchCameras(currentEq);
    }
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
var time = new Date().getTime();
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="name">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="info" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subtype" value="string" style="display : none;">';
    tr += '<div class="row">';
    tr += '<div class="col-lg-7" id="icon_'+time+'" '+ '">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
    tr += '<span class="cmdAttr cmdAction" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';


    tr += '<td>';
    tr += '<select input id="synoAction_'+time+'"'+'class="cmdAttr form-control input-sm list-syno-action" data-l1key="configuration" data-l2key="synoAction" onchange="enableEnvois('+time+')">';
    if(!$(".isliveEq").is(':checked')){
        tr += '<option value="enable">{{Activer camera}}</option>';
        tr += '<option value="disable">{{Désactiver camera}}</option>';
        tr += '<option value="startRecording">{{Start enregistrement}}</option>';
        tr += '<option value="stopRecording">{{Stop enregistrement}}</option>';
        tr += '<option value="statut">{{Statut caméra}}</option>';
        tr += '<option value="snapshot">{{Snapshot camera}}</option>';
		tr += '<option value="startMotionSS">{{Active détection Mouvement par SS}}</option>';
		tr += '<option value="startMotionCM">{{Active détection Mouvement par Cam}}</option>';
		tr += '<option value="stopMotion">{{Désactive détection Mouvement}}</option>';
		tr += '<option value="statutMotion">{{Statut détection Mouvement}}</option>';
		tr += '<option value="ptzup">{{PTZ Haut}}</option>';
		tr += '<option value="ptzdown">{{PTZ Bas}}</option>';
		tr += '<option value="ptzleft">{{PTZ Gauche}}</option>';
		tr += '<option value="ptzright">{{PTZ Droite}}</option>';
		tr += '<option value="ptzstop">{{PTZ Stop}}</option>';
    }else{
        tr += '<option value="live">{{Live camera}}</option>';
    }
    tr += '</select>';
    tr += '</td>';

    tr += '<td id="camera_'+time+'">';

    tr += '</td>';

    tr += '<td>';
        tr += '<input id="dropbox_'+time+'"'+' type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="dropbox" disabled/> {{upload dropbox}}';
    tr += '</td>';

    tr += '<td width="30%">';
    tr += '<div><input id="email_'+time+'"'+'type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="email" disabled/> {{envois par mail}}</div>';
    tr += '<div><textarea disabled id="adresses_'+time+'"'+'width="100%" rows="2" class="cmdAttr form-control" data-l1key="configuration" data-l2key="adresses" checked placeholder="adresse mail séparées par des virgules"/></div>';
    tr += '</td>';

    tr += '<td style="width: 130px;">';
    tr += '<span><input type="checkbox" class="cmdAttr" data-size="mini"  data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
    if (_cmd.type == 'info') {
		tr += '<span><input id="isHistorized_'+time+'"'+' type="checkbox" class="cmdAttr" data-size="mini"   data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
	}
    tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-size="mini"  data-l1key="display" data-l2key="invertBinary" /> {{Inverser}}<br/></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        if(!$(".isliveEq").is(':checked')){
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    initCameras(_cmd,time);

}


function searchCameras(eqId) {

    $('#tableCameras tr:gt(0)').remove();
    var cams = getCameras();
            for (var i in cams) {
                var line = "";
                line+=('<tr>');
                line+=('<td>'+cams[i].id+'</td>');
                line+=('<td>'+cams[i].name+'</td>');
                line+=('<td>'+cams[i].host+'</td>');
                line+=('<td>'+cams[i].port+'</td>');
                line+=('<td>'+cams[i].model+'</td>');
                line+=('<td>'+cams[i].resolution+'</td>');
                line+=('<td>'+cams[i].ptzCap+'</td>');
                line+=('<td>'+((cams[i].enable == true) ? 'Activée' : 'Désactivée')+'</td>');
                line+=('<td style="text-align: center"><img height="25px;" src="'+((cams[i].recStatus == 6) ? 'plugins/surveillanceStation/desktop/ressources/rec.png' : 'plugins/surveillanceStation/desktop/ressources/norec.png')+'"></td>');
                line+=('</tr>');

                $('#tableCameras').append(line);
            }
}
function initCameras(_cmd,time) {

	var cams = getCameras();
			var line = "";
			for (var i in cams) {
				line+=' <label class="checkbox-inline">';
				var camera = cams[i].name.replace(" ","_")+'%'+cams[i].id;
				line += '<input type="checkbox" '+checkCameraChecked(_cmd,cams[i]) + ' class="cmdAttr" data-l1key="configuration" data-l2key="cameras" data-l3key="'+camera+'"/>' + cams[i].name + '<br/>';
				line+=' </label>';
			}
			$('#camera_'+time).append(line);
}

function getCameras(){
    if(cameras.length == 0){
        var eqId = $(".li_eqLogic.active").attr('data-eqLogic_id');
        $.ajax({
            type: "POST",
            url: "plugins/surveillanceStation/core/ajax/surveillanceStation.ajax.php",
            data: {
                action: "getCameras",
                eqId: eqId
            },
            async:false,
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function(data) {
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message:  data.result,level: 'danger'});
                    return;
                }
                cameras =  data.result.cmd;
            }
        });
    }
    return cameras;

}

function checkDropboxToken(){
        var token = $('#dropboxToken').val()
        $.ajax({
            type: "POST",
            url: "plugins/surveillanceStation/core/ajax/surveillanceStation.ajax.php",
            data: {
                action: "checkDropbox",
                token: token
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function(data) {
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message:  data.result,level: 'danger'});
                    return;
                }

                if (data.result.dropbox.display_name != undefined) {
                    $("#dropBoxName")
                        .html('<h4><span class="label label-success">' + data.result.dropbox.display_name + '</span></h4>');
                } else {
                    $("#dropBoxName")
                        .html('<h4><span class="label label-danger">please check or add your token</span></h4>');
                }
            }
        });

}

function enableEnvois(time,cmd){

    var disable = true;
    if($( '#synoAction_'+time ).val() == "statut"){
        $( '#icon_'+time).hide();
    }else{
        $( '#icon_'+time).show();
        $( '#isHistorized_'+time ).prop( "disabled", disable );
    }
    if($( '#synoAction_'+time ).val() == "snapshot"){
        disable = false;
    }else{
        $( '#email_'+time ).prop( "checked", false );
        $( '#dropbox_'+time ).prop( "checked", false );
        $( '#adresses_'+time ).prop( "value", "" );
    }
    $( '#email_'+time ).prop( "disabled", disable );
    $( '#dropbox_'+time ).prop( "disabled", disable );
    $( '#adresses_'+time ).prop( "disabled", disable );

}

function checkMail(){
    var eqId = $(".li_eqLogic.active").attr('data-eqLogic_id');
    $.ajax({
        type: "POST",
        url: "plugins/surveillanceStation/core/ajax/surveillanceStation.ajax.php",
        data: {
            action: "mailTester",
            eqId: eqId
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
        }
    });

}

function checkSyno(){
    $.ajax({
        type: "POST",
        url: "plugins/surveillanceStation/core/ajax/surveillanceStation.ajax.php",
        data: {
            action: "synoTester",
            protocole: $('#synoProtocole').is(':checked'),
            host: $('#synoHost').val(),
            port: $('#synoPort').val(),
            login: $('#synoLogin').val(),
            password: $('#synoPassword').val()
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.result == 'true') {
                $("#synoCheckReturn")
                    .html('<h5><span class="label label-success">Connexion réussie, veuillez sauvegarder</span></h5>');
            } else {
                $("#synoCheckReturn")
                    .html('<h5><span class="label label-danger">'+data.result+'</span></h5>');
            }
        }
    });

}

function checkCameraChecked(_cmd,cam){
    var camera = cam.name.replace(" ","_")+'%'+cam.id;
    if(isset(_cmd) && isset(_cmd.configuration.cameras) ){

        var keys = Object.keys(_cmd.configuration.cameras);

        for(var i in keys){
            var index = keys[i].indexOf('%');
            var id = keys[i].substring(index+1);
            if(id == cam.id && _cmd.configuration.cameras[keys[i]] == 1){
                return "checked";
            }
        }
    }
    return "";
}
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});