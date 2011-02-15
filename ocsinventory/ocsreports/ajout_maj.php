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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.10 $)

include('security.php');

require_once ('require/function_mdb2.php');
require_once ('require/function_misc.php');

if (PEAR::isError($_SESSION["readServer"]->loadModule("Reverse", null, true)))
	die ('Could not load MDB2 Reverse Module');

if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

if (isset($_POST['action_form'])) {
	$action_form = $_POST['action_form'];
}

function MAJ_Inventory($systemid)
{
	$queryAcc = 'SELECT * FROM accountinfo WHERE hardware_id=?';
	$resultAcc = mdb2_query($queryAcc, $_SESSION["readServer"], "integer", $systemid) or die(mdb2_error($_SESSION["readServer"]));
	$item=mdb2_fetch_assoc($resultAcc);
	foreach ($item as $k => $v) {
		$lesCol[] = $k;
	}
	
	$requeteSQL = "UPDATE accountinfo SET ";
	$indexType=0;
	foreach ($_POST as $kp => $vp) {		
		if(!in_array(strtolower($kp),$lesCol))
			continue;
		$indexType++;	

		$def = $_SESSION["readServer"]->reverse->getTableFieldDefinition("accountinfo", strtolower ($kp));
		if (PEAR::isError($def))
                       die ($def->getMessage());

		$colType = $def[0]['mdb2type'];
		if($colType=='date' and $vp) {
			$vp = dateToMysql($vp);
		}
		$kp = mdb2_quote_identifier ($kp, $_SESSION['writeServer']);
		if ($vp == '')
			$requeteSQL.="$kp=NULL,";
		else
		{
			$requeteSQL.="$kp=?,";
			$args[] = $vp;
			$types[] = $colType;
		}
	}
	
	$requeteSQL = substr($requeteSQL,0,strlen($requeteSQL)-1);
	$requeteSQL.=" WHERE hardware_id=?";
	$args[] = $systemid;
	$types[] = 'integer';
	if( lock($systemid) ) {
		$resultat = mdb2_query( $requeteSQL, $_SESSION["writeServer"], $types, $args );
		unlock($systemid);
	}
	else
		errlock();
		
	return;
}

if (isset ($action_form) && $action_form == 'modifier')
{	
	$systemid = check_param ($_POST, "systemid", "^[0-9]+$");
	MAJ_Inventory($systemid);	
	echo "<script language='javascript'>\n";
	echo "\twindow.open(\"./machine.php?systemid=$systemid&state='MAJ'\", \"_self\");\n";
	echo "</script>\n";
}

echo "<table class='Items' width='100%' border='0' cellpadding='4'>";
echo "<tr>";
echo "<td valign='center' align='left' width='100%'><b>".$l->g(56)."</b></font></td>";
echo "</tr>";
echo "</table>";

$queryAcInf = 'SELECT * FROM accountinfo WHERE hardware_id=?';
$resultAcInf = mdb2_query($queryAcInf, $_SESSION["readServer"], "integer", $systemid ) or die(mdb2_error($_SESSION["readServer"]));
$item=mdb2_fetch_assoc($resultAcInf, CASE_UPPER);

//********************************************************************
//*                       FORMULAIRE DE SAISIE						 *
//********************************************************************
echo "<form method='POST' name='Ajout_MAJ' action='machine.php'>\n";
echo "<table width='100%' border='0' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>\n";
$indexType = -1;
foreach ($item as $k=>$v) {
	$indexType++;
	$kaff = $k;
	if($k == "DEVICEID" || $k == "UNITID" || $k == "HARDWARE_ID")
		continue;
	if($k == "TAG")
		$kaff = TAG_LBL;

	$def = $_SESSION["readServer"]->reverse->getTableFieldDefinition('accountinfo', strtolower ($k));
	if (PEAR::isError($def))
		die ($def->getMessage());

	if($def[0]['mdb2type']=='date')
		echo "<tr bgcolor='#FFFFFF'><td align='right'><b>$kaff:</b></font></td><td>
		<input READONLY ".dateOnClick($k)." type='text' tabindex='5' name='$k' id='$k' value='".dateFromMysql($v)."'>".datePick($k).
		"&nbsp;&nbsp;</td></tr>\n";
	else
		echo "<tr bgcolor='#F2F2F2'><td align='right'><b>$kaff:</b></font></td><td><input type='text' tabindex='1' name='$k' value='$v'></td></tr>\n";
}

// les dates
echo "</table><br>\n";
echo "<table class='Items' width='100%' border='0' cellpadding='4'>";
echo "<tr><td align='center' colspan='2'>\n";
echo "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='submit' value='".$l->g(114)."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='button' value='   ".$l->g(113)."   ' onClick='window.open(\"./machine.php?systemid=$systemid\", \"_self\");'>\n";
echo "<input type='hidden' name='systemid' value='".$systemid."'>";
echo "</td></tr>\n";
echo "</table>\n";

if (isset($action)) {
if ($action == "ajouter_donnees")
{
	echo "<input type='hidden' name='action_form' value='ajouter'>\n";
}
elseif ($action == "MAJ_donnees")
{
	echo "<input type='hidden' name='action_form' value='modifier'>\n";
}
}

echo "<input type='hidden' name='systemid' value='$systemid'>\n";
echo "</form>\n";
echo "</body>";
echo "</html>";
?>