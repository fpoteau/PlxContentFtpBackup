<?php
/*
    *   archiveftp Class
    *
    *   Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit 
    *   que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
    *   laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script. 
    *
    *   @author         François Poteau <fpoteau@gmail.com>
	* 	@copyright		2011-2012 François Poteau
    *   @link           http://francoispoteau.com
    *   @license        http://www.gnu.org/licenses/gpl.html (COPYING) GNU Public License
    *   @begin          07/06/2011, François Poteau
    *   @last           07/06/2011, François Poteau
*/

class archiveftp extends plxContentFtpBackup {
    
	public $save_dir;
	public $saved_dirs;
	
	public $ftp_server;
	public $ftp_user_name;
	public $ftp_user_pass;
	public $ftp_target_path;
	public $ftp_port;

	// *********************************************************************
	// __construct
	//
	// Paramètres de sauvegarde
	// Crée le dossier de sauvegarde temporaire s'il n'existe pas
	//
	// string $save_dir :: Localisation du dossier de sauvegarde pour les fichiers Zip temporaires
	// int $days :: sauvegarde tous les X jours
	// string $saved_dirs :: Dossiers sauvegardés
	// date $lastbackupdate :: yymmdd - Date de la dernière sauvegarde
	//
	// return bool
	// author	François POTEAU
	// *********************************************************************
	
	
    public function __construct($save_dir='../../sauvegarde/',$days='14',$saved_dirs='data/articles/,data/commentaires,data/statiques',$lastbackupdate='010101')
    {
		$this->saved_dirs = explode(",", $saved_dirs);
        $this->save_dir = (substr($save_dir, -1) != '/') ? $save_dir.'/' : $save_dir;
	
		$dir = @opendir($this->save_dir);
		
		if(!$dir) {
			// Création du dossier
			if(mkdir($this->save_dir)) {
				// Création du htaccess
				$this->htaccess($this->save_dir);
			}
			else { return false; }
		}
		
		$this->lastbackupdate = $lastbackupdate;
		if (is_numeric($days)) {
			$this->days = $days;
		}
		else { return false; }
		return true;
    }
	
	// *********************************************************************
	// ftp_param
	//
	// Enregistrement et vérification des paramètres de connexion au serveur FTP
	//
	// string $server :: IP serveur
	// string $user :: Nom d'utilisateur
	// string $pass :: Mot de passe
	// string $target :: Dossier de destination sur le serveur FTP
	// int $port  :: Port FTP
	//
	// return void
	// author	François POTEAU
	// *********************************************************************
	
	public function ftp_param($server,$user,$pass,$target,$port = '21') {
		
		$this->ftp_server = $server;
		$this->ftp_user_name = $user;
		$this->ftp_user_pass = $pass;
		$this->ftp_target_path = $target; 
		
		if($port) { $this->ftp_port = $port; }
		else { $this->ftp_port = '21'; }
		
		if($this->ftp_target_path) {
			if(substr($this->ftp_target_path, -1, 1) != '/') { $this->ftp_target_path = $this->ftp_target_path . '/'; }
			if(substr($this->ftp_target_path, 1, 1) != '/') { $this->ftp_target_path = '/' . $this->ftp_target_path; }
		}
	}
	
	// *********************************************************************
	// ftp_param
	//
	// Se connecte au serveur FTP à partir des informations saisie dans ftp_param
	// L'identifiant de connextion est stocké dans conn_id
	//
	// return bool
	// author	François POTEAU
	// *********************************************************************
	
	public function ftp_connection() {
		// Mise en place d'une connexion basique
		$this->conn_id = ftp_connect($this->ftp_server);
		// Identification avec un nom d'utilisateur et un mot de passe
		if($this->conn_id) {
			$login_result = ftp_login($this->conn_id, $this->ftp_user_name, $this->ftp_user_pass);
			if ($login_result) {
				// si la connexion a reussie on s'assure que le dossier de destination soit créé
				if($this->ftp_target_path) { $this->ftp_makedir($this->ftp_target_path); }
				return true;
			}
			else return false;
		}
		else return false;
	}
	
	// *********************************************************************
	// Méthode zip()
	//
	// Créer l'archive zip dans le dossier $save_dir à partir de $saved_dirs
	//
	// @return void
	// @author	François POTEAU
	// *********************************************************************
	
	public function zip() {
		$zip_name = 'archive-'.date('ymd').'.zip';
		$zip = new Zip($zip_name,$this->save_dir);
		foreach($this->saved_dirs as $dir) {
			$zip->add('../../'.$dir);
			// On supprime les références aux dossiers précédents ..
			$zip->renameName('../../'.$dir,$dir);
		}
		$zip->close();
		$this->lastbackup = date('ymd');
		$this->backuparchive = 'archive-'.date('ymd').'.zip';
	}
	
	// *********************************************************************
	// Méthode check()
	//
	// Vérifie s'il est nécessaire de réaliser une sauvegarde en accord avec la variable $days
	// 
	//
	// @return bool
	// @author	François POTEAU
	// *********************************************************************
	
	public function check() {
		// récuperation de la date de la dernière archive (stockée dans les paramètres)
		$lastbackup = $this->lastbackupdate;
		$day = substr($lastbackup, -2, 2); // yymmdd -> dd
		$month = substr($lastbackup, -4, 2); // yymmdd -> mm
		$year = substr($lastbackup, -6, 2); // yymmdd -> yy
		$days = (strtotime(date("Y-m-d")) - strtotime($year.'-'.$month.'-'.$day)) / (60 * 60 * 24);
		// Si l'écart entre aujourd'hui et la dernière sauvegarde excède le nombre de jours paramétré, retourner vrai
		if($days >= $this->days) {
			return true;
		}
		// sinon retourner faux
		else { return false; }
	}
	
	// *********************************************************************
	// Méthode sendtoftp()
	//
	// Envoi la dernière archive disponible sur le serveur FTP et supprime l'archive temporaire
	//
	// @author François POTEAU
	// @return bool
	// *********************************************************************
	
	
	public function sendtoftp() {
		if($this->ftp_connection()) {
			if($this->save_dir && $this->backuparchive && $this->conn_id) {
				
				$upload = ftp_put($this->conn_id, '/'.$this->ftp_target_path.$this->backuparchive, $this->save_dir.$this->backuparchive, FTP_BINARY);
				
				ftp_close($this->conn_id);
				// suppresion du fichier
				unlink($this->save_dir.$this->backuparchive);
				if (!$upload) { return false; }
				else return true;
			}
			else return false;
		}
		else return false;
	}
	
	// *********************************************************************
	// Méthode displaylist()
	// affiche la liste des fichiers avec sa taille (sortie HTML)
	//
	// @author	François POTEAU
	// @return void
	// *********************************************************************
	
	public function displaylist() {
		// Mise en place d'une connexion basique
		if($this->ftp_connection()) {
			
			$ftp_rawlist = ftp_rawlist($this->conn_id, $this->ftp_target_path);
			echo '<table>';
			
			foreach ($ftp_rawlist as $v) {
				$info = array();
				$vinfo = preg_split("/[\s]+/", $v, 9);
				if ($vinfo[0] !== "total") {
				
					$info['chmod'] = $vinfo[0];
					$info['num'] = $vinfo[1];
					$info['owner'] = $vinfo[2];
					$info['group'] = $vinfo[3];
					$info['size'] = $vinfo[4];
					$info['month'] = $vinfo[5];
					$info['day'] = $vinfo[6];
					$info['time'] = $vinfo[7];
					$info['name'] = $vinfo[8];
					$rawlist[$info['name']] = $info;
					// On recupère uniquement les fichiers Zip
					if($info['chmod']{0} == '-' && $info['name']{0} != '.' && strrchr($info['name'], '.') == '.zip') { 
						echo '<tr><td class="name">'.$info['name'].'</td><td>'.$this->format_bytes($info['size']).'</td><tr />';
					}
				}
			}
			ftp_close($this->conn_id);
			echo '</table>';
		}
	}
	
	// *********************************************************************
	// Méthode format_bytes()
	// Convertit l'unité bytes 
	//
	// @author	François POTEAU
	// @return void
	// *********************************************************************
	
	private function format_bytes($size) {
		$units = array(' o', ' Ko', ' Mo', ' Go', ' To');
		for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
		return round($size, 2).$units[$i];
	}
	
	// *********************************************************************
	// Méthode htaccess()
	// creer un fichier htaccess dans $path
	// string $path :: dossier de destination
	//
	// @author	François POTEAU
	// @return void
	// *********************************************************************
	
	private function htaccess($path) {
		$htaccess = "<Files *>\r\n\tOrder allow,deny\r\n\tDeny from all\r\n</Files>";
		file_put_contents($path.'.htaccess',$htaccess);
	}
	
	// *********************************************************************
	// Méthode ftp_makedir()
	// Créer le chemin $path sur le serveur ftp
	// string $path :: dossier de destination
	//
	// @author	François POTEAU
	// @return bool
	// *********************************************************************
	
	function ftp_makedir($path)  { 
	   $dir=split("/", $path); 
	   $path=""; 
	   $ret = true; 
	   
	   for ($i=0;$i<count($dir);$i++) 
	   { 
		   $path.="/".$dir[$i]; 
		   if(!@ftp_chdir($this->conn_id,$path)){ 
			 @ftp_chdir($this->conn_id,"/"); 
			 if(!@ftp_mkdir($this->conn_id,$path)){ 
			  $ret=false; 
			  break; 
			 } 
		   } 
	   } 
	   return $ret; 
  }
  
	// *********************************************************************
	// Méthode getlastfile()
	// Récupère sur le serveur la dernière archive en date et l'envoi au client
	// ATTENTION: NE FONCTIONNE PAS SUR LES SERVEURS APACHE2 AYANT GZIP D'ACTIVE
	// TODO: SOLUTION A TROUVER
	//
	// @author	François POTEAU
	// @return bool
	// *********************************************************************
  
	public function getlastfile() {
		
		if($this->ftp_connection()) {
			
			if($this->save_dir && $this->lastbackupdate && $this->conn_id) {
				
				// try to download $server_file and save to $local_file
				$name = 'dlarchive.'.$this->lastbackupdate.'.zip';
				$local_file = $this->save_dir.$name;
				$server_file = $this->ftp_target_path.'archive-'.$this->lastbackupdate.'.zip';
				
				if (ftp_get($this->conn_id, $local_file, $server_file, FTP_BINARY)) {
					if(file_exists($local_file)) {
						$taille = filesize($local_file);
						// Set headers
						header("Content-Type: application/force-download; name=\"$name\"" );
						header("Content-Transfer-Encoding: binary\n" );
						header("Content-Length: $taille" );
						header("Content-Disposition: attachment; filename=\"$name\"" );
						header("Expires: 0" );
						header("Cache-Control: no-cache, must-revalidate" );
						header("Pragma: no-cache" );
						// read file  
						readfile($local_file);
						//delete file						
						if(unlink($local_file)) {
							return true;
						}
					}
				} 
				else {
					return false;
				}
			}
			ftp_close($this->conn_id);
	    }
	}
}
?>