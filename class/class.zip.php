<?php
/*
    *   Zip Class
    *
    *   Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit 
    *   que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
    *   laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script. 
    *
    *   @package        Packer
    *   @author         Kévin Gomez <geek63@gmail.com>
    *   @copyright      ©Kévin Gomez, La Geek Attitude 2007, 2008
    *   @link           http://geek-attitude.fr.nf/ La Geek Attitude
    *   @license        http://www.gnu.org/licenses/gpl.html (COPYING) GNU Public License
    *   @begin          04/08/2009, Kévin Gomez
    *   @last           04/08/2009, Kévin Gomez
*/

class Zip extends ZipArchive {
    
    /**
    *   __construct
    *
    *   string $zip_name :: nom du pack (avec extension)
    *   string $zip_dir  :: dossier de destination pour le zip
    *
    *   return void
    **/
 
    public function __construct($zip_name, $zip_dir='./', $overwrite=True)
    {
        $dest_dir = (substr($zip_dir, -1) != '/') ? $zip_dir.'/' : $zip_dir;
        
        if(!self::checkDir($dest_dir))
            return;
        
        if($this->open($dest_dir.$zip_name, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) !== True)
        {
            trigger_error('Zipper :: Impossible de créer le fichier « '.$dest_dir.$zip_name.' ».');
            return;
        }
    }
    
    /**
    *   Pour ajouter un/des fichiers/dossiers au zip
    *
    *   string|array $to_add :: fichiers/dossiers à zipper
    *   bool $recursive :: si ajout d'un dossier, doit-on l'explorer et ajouter son contenu récursivement ? (True par défaut)
    *
    *   return this
    **/
 
    public function add($to_add, $recursive=True)
    {
        if(!is_array($to_add))
            $to_add = array($to_add);
        
        foreach($to_add as $add)
            $this->_add($add, (bool) $recursive);
        
        return $this;
    }
    
    /**
    *   Dé-zip une archive dans le dossier spécifié
    *
    *   string $file :: archive à unziper
    *   string $dest_dir:: dossier
    *
    *   return bool
    **/
    
    public static function unzip($file, $dest_dir='./')
    {
        $zip = new ZipArchive;
        if($zip->open($file) !== True)
        {
            trigger_error('Zipper :: Impossible d\'ouvrir le fichier « '.$file.' ».');
            return False;
        }
        
        if(!self::checkDir($dest_dir))
            return False;
        
        $ret = $zip->extractTo($dest_dir);
        
        $zip->close();
        
        return $ret;
    }
    
    /**
    *   Gère l'ajout d'éléments au zip
    *
    *   string $to_pack :: fichier/dossier à packer
    *   bool $recursive :: si dossier, doit-on l'explorer récursivement ?
    *
    *   return void
    **/

    private function _add($to_pack, $recursive)
    {
        if(is_dir($to_pack))
        {
            $to_pack = ($to_pack[ strlen($to_pack)-1] == '/') ? $to_pack : $to_pack.'/';
            $to_pack = (substr($to_pack, 0, 2) == './') ? substr($to_pack, 2) : $to_pack;
            
            $this->addEmptyDir($to_pack);
            
            if((bool) $recursive)
                $this->_exploreAndPack($to_pack);
        }
        else
        {
            if(@file_exists($to_pack)) {
                $this->addFile($to_pack);
			}
            else
                trigger_error('Packer :: « '.$to_pack.' » n\'a pas été trouvé.');
        }
    }
    
    /**
    *   Explore de manière récursive un dossier
    *   et ajoute son contenu à la liste des éléments à packer
    *
    *   string $path :: dossier à explorer
    *
    *   return void
    **/
    
    private function _exploreAndPack($path)
    {
        $not_to_explore = array('.', '..'); // TODO :: laisser la possibilité à l'user de rajouter des trucs
        $dir = opendir($path);
 
        $actual_dir = '';
        while($item_dir=readdir($dir))
        {
            if(in_array($item_dir, $not_to_explore))
                continue;
            
            if(!is_dir($path.$item_dir)) {
                $this->addFile($path.$item_dir, str_replace('../','',$path.$item_dir));
			}
            else
            {
                $this->addEmptyDir($path.$item_dir);
                
                $this->_exploreAndPack($path.$item_dir.'/');
            }
        }
    }
    
    /**
    *   Vérifie l'existence d'un dossier et le crée le cas échéant
    *   vérifie aussi l'accès en écriture
    *
    *   string $dir:: dossier
    *
    *   return bool
    **/
    
    private static function checkDir($dir)
    {
        if(!is_dir($dir))
        {
            if(!mkdir($dir, 0755))
            {
                trigger_error('Zipper :: « '.$dir.' » n\'existe pas et n\'a pas pu être créé.');
                return False;
            }
        }
        else
        {
            if(!is_writable($dir))
            {
                trigger_error('Zipper :: « '.$dir.' » n\'est pas accessible en écriture.');
                return False;
            }
        }
        
        return True;
    }
}

/*
$zip = new Zip('test.zip');
$zip->add('./directory_index')->close();

var_dump(Zip::unzip('test.zip', 'yooo'));
*/
?>