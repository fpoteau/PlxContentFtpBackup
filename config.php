
﻿<?php if(!defined('PLX_ROOT')) exit; ?>


<?php
require_once('class/class.zip.php');
require_once('class/class.archiveftp.php');
$archive = new archiveftp($plxPlugin->getParam('savedir'),$plxPlugin->getParam('days'),$plxPlugin->getParam('saved_dirs'),$plxPlugin->getParam('lastbackupdate'));

$archive->check();

$archive->ftp_param(
	$plxPlugin->getParam('ftp_server'),
	$plxPlugin->getParam('ftp_user_name'),
	$plxPlugin->getParam('ftp_user_pass'),
	$plxPlugin->getParam('ftp_target_path'),
	$plxPlugin->getParam('ftp_port')
);

if(!empty($_POST)) {
	$plxPlugin->setParam('savedir', $_POST['savedir'], 'string');
	$plxPlugin->setParam('days', $_POST['days'], 'numeric');
	$plxPlugin->setParam('ftp_server', $_POST['ftp_server'], 'string');
	$plxPlugin->setParam('ftp_port', $_POST['ftp_port'], 'numeric');
	$plxPlugin->setParam('ftp_user_name', $_POST['ftp_user_name'], 'string');
	$plxPlugin->setParam('ftp_user_pass', $_POST['ftp_user_pass'], 'string');
	$plxPlugin->setParam('ftp_target_path', $_POST['ftp_target_path'], 'string');
	// on ajoute le répertoire 'data/' aux dossiers choisis
	//while(list ($key, $val) = each ($_POST['data'])) { $_POST['data'][$key] = 'data/'.$val; }
	$dirs = implode(',',$_POST['data']);
	$plxPlugin->setParam('saved_dirs', $dirs, 'string');
	// écriture des paramètres
	$plxPlugin->saveParams(); 
	header('Location: parametres_plugin.php?p=plxContentBackupFtp');
	exit;
}
if($_GET['action'] == 'upload') {
		$archive->zip();
		plxMsg::Info('L\'archive a été crée avec succès. Merci de patienter pendant l\'envoi du fichier.');
		if($archive->sendtoftp()) { 
			$plxPlugin->setParam('lastbackupdate', $archive->lastbackup, 'numeric');
			$plxPlugin->saveParams();
			plxMsg::Info('L\'archive a été envoyée avec succès sur le serveur FTP'); 
		}
		else plxMsg::Info('Impossible de se connecter au serveur FTP.');
}
if($_GET['action'] == 'download') {
	$archive->getlastfile();
}
?>
<div id="plxcontentbackupftp">
	<h2><?php $plxPlugin->lang('L_TITLE_CONFIG') ?></h2>
	<p><?php $plxPlugin->lang('L_DESCRIPTION_CONFIG') ?></p>
	<h3><?php $plxPlugin->lang('L_ACTION_CONFIG') ?></h3>
	<div class="backupoptions">
		<a class="upload" href="parametres_plugin.php?p=plxContentBackupFtp&action=upload" title="<?php $plxPlugin->lang('L_UPLOAD_CONFIG') ?>"><?php $plxPlugin->lang('L_UPLOAD_CONFIG') ?></a>
		<a class="download" href="parametres_plugin.php?p=plxContentBackupFtp&action=download"title="<?php $plxPlugin->lang('L_DOWNLOAD_CONFIG') ?>"><?php $plxPlugin->lang('L_DOWNLOAD_CONFIG') ?></a>
		<div class="clear"></div>
	</div>
	<h3><?php $plxPlugin->lang('L_ARCHIVELIST_CONFIG') ?> - <?php echo'<a href="ftp://'.$archive->ftp_user_name.':'.$archive->ftp_user_pass.'@'.$archive->ftp_server.$archive->ftp_target_path.'">'.$plxPlugin->getParam('ftp_server').'</a>'; ?></h3>
	<div class="liste">
		<?php $archive->displaylist(); ?>
	</div>
	<div class="clear"></div>
	<h3><?php $plxPlugin->lang('L_OPTIONS_CONFIG') ?></h3>
	<form action="parametres_plugin.php?p=plxContentBackupFtp" method="post">
		<fieldset>
			<label><?php $plxPlugin->lang('L_SAVEDIR_CONFIG') ?></label> <input type="text" name="savedir" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('savedir')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_DAY_CONFIG') ?></label> <input type="text" name="days" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('days')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_FTP_SERVER') ?></label> <input type="text" name="ftp_server" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('ftp_server')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_FTP_PORT') ?></label> <input type="text" name="ftp_port" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('ftp_port')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_FTP_USER_NAME') ?></label> <input type="text" name="ftp_user_name" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('ftp_user_name')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_FTP_USER_PASS') ?></label> <input type="password" name="ftp_user_pass" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('ftp_user_pass')) ?>" /><br />
			<label><?php $plxPlugin->lang('L_FTP_TARGET_PATH') ?></label> <input type="text" name="ftp_target_path" value="<?php echo plxUtils::strCheck($plxPlugin->getParam('ftp_target_path')) ?>" /><br />
			<div class="label"><?php $plxPlugin->lang('L_SAVED_DIRS') ?></div>
			<div class="checkboxes">
				<?php
				$data = array(
					$plxAdmin->aConf['images'],
					$plxAdmin->aConf['documents'],
					$plxAdmin->aConf['racine_articles'],
					$plxAdmin->aConf['racine_commentaires'],
					$plxAdmin->aConf['racine_statiques'],

					PLX_CONFIG_PATH,

					$plxAdmin->aConf['racine_plugins'],
					$plxAdmin->aConf['racine_themes'],
				);
				
				foreach($data as $d) {
					echo '<div><label>'. $d .'</label> <input class="checkbox"';
					if(in_array($d,explode(',',$plxPlugin->getParam('saved_dirs')))) { echo 'checked="checked"'; }
					echo' type="checkbox" value="'. $d .'" name="data[]" /></div>';
				}
				?>
			</div>
			<div class="clear"></div>
			<input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SUBMIT') ?>" />
		</fieldset>
	</form>
	<div class="clear"></div>
</div>
