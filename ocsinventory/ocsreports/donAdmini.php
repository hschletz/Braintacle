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
//Modified on $Date: 2007-02-08 15:53:24 $$Author: plemmet $($Revision: 1.6 $)
include('security.php');
require ('fichierConf.class.php');

require('req.class.php');

require_once ('require/function_mdb2.php');
require_once ('require/function_misc.php');

if (PEAR::isError($_SESSION["writeServer"]->loadModule("Manager")))
	die ('Could not load MDB2 Manager Module');
if (PEAR::isError($_SESSION["readServer"]->loadModule("Reverse", null, true)))
	die ('Could not load MDB2 Reverse Module');

if(check_param ($_GET, "suppAcc")) {
	// Drop column $suppAcc
	$_SESSION["writeServer"]->manager->alterTable("accountinfo", array(
		"remove" => array(
			$_GET["suppAcc"] => array()
		)
	),false);

	unset($_SESSION["availFieldList"], $_SESSION["currentFieldList"], $_SESSION["optCol"]);
	echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>". $_GET["suppAcc"] ."</b> ".$l->g(226)." </font></center><br>";
}

if(check_param ($_POST, "nom"))
{
	unset($_SESSION["availFieldList"], $_SESSION["currentFieldList"], $_SESSION["optCol"]);
	switch($_POST["type"]) {
		case $l->g(229): $suff = array("type" => "text", "length" => 255); break;
		case $l->g(230): $suff = array("type" => "integer", "length" => 4); break;
		case $l->g(231): $suff = array("type" => "float"); break;
		case $l->g(232): $suff = array("type" => "date"); break;
	}
	// Add column $nom of type $suff
	$alterTable = $_SESSION["writeServer"]->manager->alterTable("accountinfo", array(
		"add" => array(
			$_POST["nom"] => $suff
		)
	),false);
	if (PEAR::isError($alterTable))
		echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>".$l->g(259)."</b></font></center><br>";
	else
		echo "<br><br><center><font face='Verdana' size=-1 color='green'><b>". $_POST["nom"] ."</b> ".$l->g(234)." </font></center><br>";
}//fin if
?>
			<script language=javascript>
				function confirme(did)
				{
					if(confirm("<?php echo $l->g(227)?> "+did+" ?"))
						window.location="index.php?multi=<?php echo $_GET["multi"]?>&c=<?php echo (check_param ($_SESSION, "c")?check_param ($_GET, "c"):2)?>&a=<?php echo check_param ($_GET, "a")?>&page=<?php echo check_param ($_GET, "page")?>&suppAcc="+did;
				}
			</script>
<?php 
printEnTete($l->g(56));
echo "
			<br>
		 <form name='ajouter_reg' method='POST' action='index.php?multi=9'>
	<center>
	<table width='60%'>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(228)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='nom'>
		</td>
	</tr>
	<tr>
		<td align=center>
			<font face='Verdana' size='-1'>".$l->g(66).":</font>
		</td>
		<td>
			<select name='type'>
				<option>".$l->g(229)."</option>
				<option>".$l->g(230)."</option>
				<option>".$l->g(231)."</option>
				<option>".$l->g(232)."</option>
			</select>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan='2' align='center'>
			<input class='bouton' name='enre' type='submit' value=".$l->g(114)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>
	
	</table></center></form><br>
	";
	printEnTete($l->g(233));
	
	$tableInfo = $_SESSION["readServer"]->reverse->tableInfo("accountinfo");
	if (PEAR::isError($tableInfo))
		die ($tableInfo->getMessage());

	echo "<br><table BORDER='0' WIDTH = '50%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr><td align='center'><b>".$l->g(49)."</b></font></td><td align='center'><b>".$l->g(66)."</b></font></td></tr>";		
	$x = 0;
	foreach ($tableInfo as $colname) {
		if( $colname["name"] != "deviceid" && $colname["name"] != strtolower(TAG_NAME) && $colname["name"] != "hardware_id" ) {
			$x++;
			echo "<TR height=20px bgcolor='". ($x%2==0 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne			
			echo "<td align=center>".$colname["name"]."</font></td><td align=center>".$colname["mdb2type"]."</font></td><td align=center>
			<a href=# OnClick='confirme(\"".$colname["name"]."\");'><img src=image/supp.png></a></td></tr>";
		}
	}
	echo "</table><br>";

?>

