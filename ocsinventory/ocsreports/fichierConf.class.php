<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006-12-21 18:13:46 $$Author: plemmet $($Revision: 1.4 $)

require_once ("require/function_misc.php");

if(!class_exists("FichierConf"))
{ 
/**
 * \brief Classe FichierConf
 *
 * This class contains all the data of the langueage file
 */
class FichierConf
{		
	var  	$tableauMots;    // tableau contenant tous les mots du fichier 			
	
	function FichierConf($language) // constructeur
	{
		if( !isset($_SESSION["langueFich"])) {
			$_SESSION["langueFich"] = "languages/$language.txt";
		}
		
		$file=@fopen(check_param ($_SESSION, "langueFich", NULL, "(\\.\\.)"),"r"); // regex prevents path traversal
		
		if (!$file) {
			$_SESSION["langueFich"] = "languages/".DEFAULT_LANGUAGE.".txt";
			$file=@fopen($_SESSION["langueFich"],"r");
		}
		
		if ($file) {	
			while (!feof($file)) {
				$val = fgets($file, 1024);
				$tok1   =  rtrim(strtok($val," "));
				$tok2   =  rtrim(strtok(""));
				$this->tableauMots[$tok1] = $tok2;
			}
			fclose($file);	
		} 
	}
		
	function g($i)
	{
        // Do suppress E_NOTICE here - this would indicate a missing translation that has to be addressed.
		return $this->tableauMots[$i];
	}
}		
}
?>