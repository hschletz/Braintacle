<?php
/*
 * Created on 17 juin 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require("req.class.php");
require("fichierConf.class.php");
require_once("preferences.php");
include('security.php');
require_once("require/function_mdb2.php");

$toBeWritten = "";
if (isset($_SESSION['cvs'][$_GET['tablename']])){
	$result=mdb2_query($_SESSION['cvs'][$_GET['tablename']], $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
	$col=$result->getColumnNames (true);
	foreach( $col as $colname ) {
		$toBeWritten .= $colname.";";
	}
	$toBeWritten = substr($toBeWritten,0,-1)."\r\n";
	
	while( $cont = mdb2_fetch_assoc ($result) ) {
		foreach($col as $key=>$value){
			$toBeWritten.= $cont[$value].";";		
		}
		$toBeWritten = substr($toBeWritten,0,-1)."\r\n";
	}
	$filename="export.csv";
}
/*elseif (isset($_GET['log'])){
	
	if (file_exists($_GET['rep'].$_GET['log'])){
		$tab = file($_GET['rep'].$_GET['log']);
		while(list($cle,$val) = each($tab)) {
 		  $toBeWritten  .= $val."\r\n";
		}
		$filename=$_GET['log'];
	}
}*/
	
if ($toBeWritten != ""){
	// iexplorer problem
	if( ini_get("zlib.output-compression"))
		ini_set("zlib.output-compression","Off");
		
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-control: private", false);
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=\"".$filename."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".strlen($toBeWritten));
	echo $toBeWritten;
}else
echo "<font color=red align=center><B>ERROR, the requested file does not exist or the requested table is empty.</B></font>"
?>
