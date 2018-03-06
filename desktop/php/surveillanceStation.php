<?php
if (!isConnect('admin')) {
     throw new Exception('{{401 - Accès non autorisé}}');
}
global $listCmdsurveillanceStation;
sendVarToJS('eqType', 'surveillanceStation');
$eqLogics = eqLogic::byType('surveillanceStation');
?>
<div class="row row-overflow">
	<div class="col-lg-2 col-md-3 col-sm-4">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter caméra}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
				foreach ($eqLogics as $eqLogic) {
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				}
				?>
			</ul>
		</div>
	</div>
 <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
   <div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
     <center>
      <i class="fa fa-plus-circle" style="font-size : 5em;color:#94ca02;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>Ajouter</center></span>
  </div>
  <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
    <center>
      <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
  </div>
</div>
	<legend><i class="fa fa-table"></i> {{Mes Caméras}}</legend>
<div class="eqLogicThumbnailContainer">
		 <?php
				foreach ($eqLogics as $eqLogic) {
					echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
					echo "<center>";
					echo '<img src="plugins/surveillanceStation/doc/images/surveillancestation_icon.png" height="105" width="95" />';
					echo "</center>";
					echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
					echo '</div>';
				}
				?>
			</div>
	</div>
	<div class="col-md-10 eqLogic" style="padding-left: 25px;display: none;">
  <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>

  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
</ul>
	<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab">	
    <div class="col-lg-10 eqLogic" style="padding-top: 10px;display: none;">
        <div class="row">
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
				<legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-lg-8">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Objet parent}}</label>
                            <div class="col-lg-8">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Catégorie}}</label>
                            <div class="col-lg-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Activer}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-lg-4 control-label">{{Visible}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">Configuration Synology</div>
							<div class="panel-body">
								<div class="form-group">
									<div class="col-md-5">
										<span class="label label-info">Adresse complète avec le protocole http:// ou https:// et l'IP ou le DNS</span>
										<span class="label label-info">Exemples : http://192.168.x.x, https://monJeedom.fr, ...</span>
									</div>
								</div>
								<legend>Accès interne</legend>
                                <div class="form-group">
									<label class="col-md-2 control-label">{{Adresse}}</label>
                                    <div class="col-md-6">
                                        <input type="text" id="synoHost" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="host" placeholder="http://192.168.x.x (IP local du Synology)"/>
                                    </div>
                                    <label class="col-md-1 control-label">{{Port}}</label>
                                    <div class="col-md-2">
                                        <input type="text" id="synoPort" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="port" placeholder="port du synology"/>
                                    </div>
                                </div>
								<legend>Accès externe</legend>
                                <div class="form-group">
                                    <label class="col-md-2 control-label">{{Adresse}}</label>
                                    <div class="col-md-6">
                                        <input type="text" id="synoHostExt" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="hostExt" placeholder="https://Domaine.tld (URL externe Synolog"/>
                                    </div>
                                    <label class="col-md-1 control-label">{{Port}}</label>
                                    <div class="col-md-2">
                                        <input type="text" id="synoPortExt" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="portExt" placeholder="port du synology"/>
                                    </div>
                                </div>
                                <legend>Identifiant</legend>
								<div class="form-group">
                                    <label class="col-md-2 control-label">{{Login}}</label>
                                    <div class="col-md-4">
                                        <input type="text" id="synoLogin" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="login" placeholder="Login du synology"/>
                                    </div>
                                    <label class="col-md-2 control-label">{{Password}}</label>
                                    <div class="col-md-4">
                                        <input type="password" id="synoPassword" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="password" placeholder="Password du synology"/>
                                    </div>
                                </div>
								<div class="form-group">
									<label class="col-lg-9 control-label">{{A cocher si Surveillance Station version 6}}</label>
									<div class="col-lg-1">
										<label class="checkbox-inline">
										<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="v6"/>
									</div>
									<label class="col-lg-9 control-label">{{Cet équipement sera utilisé pour visualiser le live d'une caméra. }}</label>
									<div class="col-lg-1">
										<label class="checkbox-inline">
										<input type="checkbox" class="eqLogicAttr isliveEq" data-l1key="configuration" data-l2key="live"/>
									</div>
									<div class="col-lg-12">
                                        <button type="button" id="btnSynoTester" class="btn btn-primary">Tester</button>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group" id="synoCheckReturn"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php

                        $testRuby = exec('ruby --version');
                        if (strstr($testRuby, 'not found') == true) {
                          echo  '<h4><span class="label label-danger">ruby n\'est pas installé - pour l\'installer : sudo apt-get install ruby</span></h4>';
                        }
                        ?>
                    </fieldset>
                </form>
            </div>

            <div class="col-lg-6" id="dropboxConf">
                <div class="panel panel-default">
                    <div class="panel-heading">Configuration Dropbox</div>
                    <div class="panel-body">
                        <div class="form-group">
                        <label class="col-md-2 control-label">{{Token}}</label>
                        <div class="col-md-8">
                            <input type="text" id="dropboxToken" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="dropboxToken" placeholder="Token dropbox"/>
                        </div>
                            <div class="col-md-2">
                                <button type="button" id="btnDropboxToken" class="btn btn-primary">Tester</button>
                            </div>
                        </div>
                        <div class="col-md-8 col-md-offset-2">
                        <div class="form-group" id="dropBoxName">
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" id="mailConf">

                <div class="panel panel-default">
                    <div class="panel-heading">Configuration Email (smtp) de l'expéditeur</div>
                    <div class="panel-body">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="col-md-2 control-label">{{Serveur}}</label>
                                <div class="col-md-4">
                                    <input type="text" id="emailHost" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailServer" placeholder="adresse du serveur"/>
                                </div>
                                <label class="col-md-2 control-label">{{Port}}</label>
                                <div class="col-md-4">
                                    <input type="text" id="emailPort" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailPort" placeholder="Port du serveur"/>
                               </div>
                            </div>
                        </div>
                        <div class="col-md-12" style="margin-top: 10px;">
                            <div class="form-group">
                                <label class="col-md-2 control-label">{{Utlisateur}}</label>
                                <div class="col-md-4">
                                    <input type="text" id="emailLogin" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailLogin" placeholder="login"/>
                                </div>
                                <label class="col-md-2 control-label">{{Mot de passe}}</label>
                                <div class="col-md-4">
                                    <input type="password" id="emailPassword" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailPassword" placeholder="password"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" style="margin-top: 10px;">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Sécurité }}</label>
                                <div class="col-sm-4">
                                    <select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='emailSecurity'>
                                        <option value=''>{{Aucune}}</option>
                                        <option value='tls'>TLS</option>
                                        <option value='ssl'>SSL</option>
                                    </select>
                                </div>
                                   <label class="col-md-2 control-label">{{Mail expéditeur }}</label>
                                    <div class="col-md-4">
                                        <input type="text" id="emailMaiExp" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailMailExp" placeholder="email expéditeur"/>
                                   </div>
                            </div>
                        </div>
                        <div class="col-md-12" style="margin-top: 10px;">
                            <div class="form-group">
                                <label class="col-md-2 control-label">{{Nom expéditeur }}</label>
                                <div class="col-md-4">
                                    <input type="text" id="emailExpediteur" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailNomExp" placeholder="nom expéditeur"/>
                                </div>
                                <label class="col-md-2 control-label">{{Sujet du mail }}</label>
                                <div class="col-md-4">
                                    <input type="text" id="emailSubject" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="emailSubject" placeholder="sujet du mail"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" style="margin-top: 10px;">
                            <div class="form-group">
                                <div class="col-md-4">
                                    <span class="label label-info">Veuillez enregistrer avant de tester</span>
                                </div>
                                <div class="col-md-2 col-md-offset-5">
                                    <button type="button" id="btnMailTester" class="btn btn-primary">Tester</button>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
            <div class="col-lg-19">
                <div class="panel panel-default">
                    <div class="panel-heading">Caméras</div>
                   <div >
					<table class="table table table-bordered table-striped table-condensed ui-sortable" id="tableCameras" style="width: 98%;max-width: 100%;margin-bottom: auto;margin-left: 5px;margin-right: 5px;margin-top: 5px;margin-bottom: 5px;">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Model</th>
                            <th>Size</th>
                            <th>PTZ</th>
                            <th>Statut</th>

                            <th>Rec</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                        <tbody>

                        </tbody>
                    </table>
								</div>
                            </div>
							

                </div></div>
  


        </div></div></div>

	<div role="tabpanel" class="tab-pane" id="commandtab" style="padding-left: 15px;padding-right: 10px;padding-top: 10px">			
        <legend><i class="fa fa-arrow-circle-left"></i> {{Commandes}}</legend>
        <a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 120px;">{{Nom}}</th>
                    <th style="width: 180px;">{{Action}}</th>
                    <th style="width: 120px;">{{Cameras}}</th>
                    <th style="width: 140px;">{{Envois dropbox}}</th>
                    <th>{{Envois Email}}</th>
                    <th style="width: 100px;">{{Paramètres}}</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div></div>
</div></div>

<?php include_file('desktop', 'surveillanceStation', 'js', 'surveillanceStation'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>