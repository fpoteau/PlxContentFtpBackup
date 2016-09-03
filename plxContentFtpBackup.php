<?php
/**
 * Classe plxContentFtpBackup
 *
 * @version 1.0
 * @date	02/06/2010
 * @update 03/09/2016
 * @author	François POTEAU
 **/
class plxContentFtpBackup extends plxPlugin {
	
	
	/**
	 * Constructeur de la classe
	 *
	 * @return	null
	 * @author	François POTEAU  
	 **/	
	public function __construct($default_lang) {

		# Appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);
		
		# droits pour accèder à la page config.php du plugin
		
		$this->setConfigProfil(PROFIL_ADMIN);
		
		# Ajouts des hooks
		$this->addHook('AdminIndexTop', 'AdminIndexTop');
		
		
		$this->addHook('AdminTopEndHead', 'AdminTopEndHead');
	}

	/**
	 * Méthode pour le hook AdminIndexTop
	 *
	 * Vérifie la dernière sauvegarde et procède au backup si nécessaire.
	 *
	 * @return	void
	 * @author	François POTEAU 
	 **/
	public function AdminIndexTop() {
	
		if(!class_exists('zip')) {
			require_once('class/class.zip.php');
		}
		require_once('class/class.archiveftp.php');
		
		$archive = new archiveftp($this->getParam('savedir'),$this->getParam('days'),$this->getParam('saved_dirs'),$this->getParam('lastbackupdate'));
		if($archive->check()) {
		
			$archive->zip();
			$archive->ftp_param(
				$this->getParam('ftp_server'),
				$this->getParam('ftp_user_name'),
				$this->getParam('ftp_user_pass'),
				$this->getParam('ftp_target_path'),
				$this->getParam('ftp_port')
			);
			
			if($archive->sendtoftp()) {
				$this->setParam('lastbackupdate', $archive->lastbackup, 'numeric');
				echo '<p class="msg">'.$this->getLang('L_SUCCESS').' '.$this->getParam('email').'</p>';
				$this->saveParams();
			}
			
		}
	}
	/**
	 * Méthode pour le hook AdminTopEndHead
	 *
	 * Ajout de la feuille de style (mise en forme de l'administration du plugin)
	 *
	 * @return	void
	 * @author	François POTEAU 
	 **/
	public function AdminTopEndHead() {
		echo '<link rel="stylesheet" type="text/css" href="'.PLX_PLUGINS.'plxContentFtpBackup/styles.css" media="screen" />';
	}

}
?>