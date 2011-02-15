<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.8 $)
$sadmin_profil=1;
include('security.php');

require_once ("require/function_mdb2.php");
require_once ("require/function_misc.php");

debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
$mode = 0;

if( isset ($optvalueTvalue) && check_param ($optvalueTvalue, 'IPDISCOVER') && $optvalue['IPDISCOVER']==1 ) {
	$select_value=$optvalueTvalue['IPDISCOVER'];
	echo "<br><center><b>".$l->g(519).": ".$optvalueTvalue['IPDISCOVER']."</b></center>";
	$mode = 1;
}
else if( isset ($optvalue) && check_param ($optvalue, 'IPDISCOVER')==2 ) {
	$select_value=$optvalueTvalue['IPDISCOVER'];
	echo "<br><center><b>".$l->g(520).": ".$optvalueTvalue['IPDISCOVER']."</b></center>";
	$mode = 3;
}
else if( isset ($optvalue) && check_param ($optvalue, 'IPDISCOVER')==="0" ) {
	$select_value="OFF";
	echo "<br><center><b>".$l->g(521)."</b></center>";
	$mode = 2;	
}
elseif(isset($_POST['systemid'])) {
	echo "<br><center><b>".$l->g(522)."</b></center>";		
}
elseif(!isset($_POST['systemid'])){
	$mode = 2;	
}
if (isset($_POST['systemid'])){
	$resInt = mdb2_query("SELECT ipaddress FROM networks WHERE hardware_id=?",$_SESSION["readServer"], "integer", $_POST['systemid']);
	while( $valInt = mdb2_fetch_assoc( $resInt )){
		if( ! ($res = @mdb2_query( "SELECT ipsubnet FROM networks WHERE ipaddress=? AND hardware_id=?", $_SESSION["readServer"], array ("text", "integer"), array ($valInt["ipaddress"], $_POST["systemid"])))) {
					echo "<br><center><font color=red><b>ERROR: Database problem<br>".mdb2_error($_SESSION["readServer"])."</b></font></center>";	}
		while( $val = mdb2_fetch_assoc( $res ))
		$lesRez[$val["ipsubnet"]] = $val["ipsubnet"];
	}
}
	if( $mode==3 || $mode==2) 
	$lesRez['des']=$l->g(523);
	$lesRez['OFF']=$l->g(524);
ligne('IPDISCOVER',$l->g(518),'select',array('VALUE'=> isset ($values) ? $values['tvalue']['OCS_FILES_FORMAT'] : NULL,'SELECT_VALUE'=>$lesRez,'VALUE'=> isset ($select_value) ? $select_value : NULL));
fin_tab($form_name);

?>

