<?php
/*
 * Created on 19 mars 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
//ADD new static group
include('security.php');
require_once ("require/function_mdb2.php");
require_once ("require/function_misc.php");

check_param ($_POST, "tri2", "^[0-9a-z]*$");
check_param ($_POST, "sens", "^(ASC|DESC)?$", NULL, false);

if (PEAR::isError($_SESSION['writeServer']->loadModule('Function')))
	die ('Could not load MDB2 Function Module');

if(check_param ($_POST, "Valid_modif_x")){
	//this group does exist?
	$sql_verif="select id from hardware
				where DEVICEID='_SYSTEMGROUP_' 
					AND name=? 
					AND DESCRIPTION=?";
	$res_verif = mdb2_query($sql_verif, $_SESSION["readServer"], array ("text", "text"), array($_POST['NAME'], $_POST['DESCR'])) or die(mdb2_error($_SESSION["readServer"]));
	$item_verif = mdb2_fetch_object($res_verif);
	//exist
	if (is_object ($item_verif) and $item_verif -> id != "")
		$error = $l->g(621);
	//name is null
	if (trim($_POST['NAME']) == "")
		$error = $l->g(638);
	//an error?
	if (isset($error)){
	echo "<script>alert('".$error."');</script>";
	$_POST['add_static_group']='KO';	
	}else{
		//else insert new group
		$sql_insert="insert into hardware (DEVICEID,NAME,LASTDATE,DESCRIPTION) 
					VALUES ('_SYSTEMGROUP_',?,CURRENT_TIMESTAMP,?)";
		mdb2_query($sql_insert, $_SESSION["writeServer"], array ("text", "text"), array ($_POST['NAME'], $_POST['DESCR'])) or die(mdb2_error($_SESSION["writeServer"]));
		$lastInsertID = $_SESSION["writeServer"]->lastInsertID();
		$sql_insert_groups="INSERT INTO groups (hardware_id,request,create_time) VALUES ($lastInsertID, '', " . $_SESSION["writeServer"]->function->unixtimestamp ("CURRENT_TIMESTAMP") . ")";
		mdb2_query($sql_insert_groups, $_SESSION["writeServer"]) or die(mdb2_error($_SESSION["writeServer"]));	
	}
	
}

 
 
//if no SADMIN=> view only your computors
if ($_SESSION["lvluser"] == ADMIN){
	$sql_mesMachines="select hardware_id from accountinfo a where ".$_SESSION["mesmachines"];
	$res_mesMachines = mdb2_query($sql_mesMachines, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
	$mesmachines="(";
	while ($item_mesMachines = mdb2_fetch_object($res_mesMachines)){
		$mesmachines.= $item_mesMachines->hardware_id.",";	
	}
	$mesmachines=" IN ".substr($mesmachines,0,-1).")";
		
}

//View for all profils?
if (isset($_POST['check_group']) and  $_POST['check_group'] != "")
{
	$sql_verif="select WORKGROUP from hardware where id=?";
	$res = mdb2_query($sql_verif, $_SESSION["readServer"], "integer", $_POST['check_group']) or die(mdb2_error($_SESSION["readServer"]));
	$item = mdb2_fetch_object($res, CASE_UPPER);
	if ($item->WORKGROUP != "GROUP_4_ALL")	
	$sql_update="update hardware set workgroup= 'GROUP_4_ALL' where id=?";
	else
	$sql_update="update hardware set workgroup= '' where id=?";
	mdb2_query($sql_update, $_SESSION["writeServer"], "integer", $_POST['check_group']) or die(mdb2_error($_SESSION["writeServer"]));	
}

//if delete group
if (isset($_POST['supp']) and  $_POST['supp'] != "" and is_numeric($_POST['supp']))
{
	deleteDid($_POST['supp']);
/*	$del_groups_server_cache="DELETE FROM download_servers WHERE group_id=?";
	mdb2_query($del_groups_server_cache, $_SESSION["writeServer"], 'integer', $_POST['supp']) or die(mdb2_error());
	$del_groups_cache="DELETE FROM groups_cache WHERE group_id=".$_POST['supp'];
	mdb2_query($del_groups_cache, $_SESSION["writeServer"]) or die(mdb2_error());
	$del_hardware="DELETE FROM hardware where id=?";
	mdb2_query($del_hardware, $_SESSION["writeServer"], 'integer', $_POST['supp']) or die(mdb2_error());
	$del_groups_TAG="DELETE FROM accountinfo where HARDWARE_ID=?";
	mdb2_query($del_groups_TAG, $_SESSION["writeServer"], 'integer', $_POST['supp']) or die(mdb2_error());*/
	

}

$form_name='groups';
require_once('require/function_table_html.php');
//if SADMIN=> view all groups
if ($_SESSION["lvluser"]!= ADMIN){
	$def_onglets['DYNA']=$l->g(810); //Dynamic group
	$def_onglets['STAT']=$l->g(809); //Static group centraux
	$def_onglets['SERV']=strtoupper($l->g(651));
	if (check_param ($_POST, "onglet") == "")
	$_POST['onglet']="DYNA";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	//show onglet
	onglet($def_onglets,$form_name,"onglet",0);
}else{
	
	$_POST['onglet']="STAT";
	
}
$limit=nb_page($form_name);

if (check_param ($_POST, "tri2") == "")
$_POST['tri2']=1;
if ($_POST['onglet'] == "STAT" or $_POST['onglet'] == "DYNA"){
	$sql="SELECT h.id AS id, h.name AS name, h.DESCRIPTION AS description, h.lastdate AS creat, COUNT(g_c.hardware_id) AS nbr ";
	if ($_POST['onglet'] == "STAT")
	$sql.=", h.workgroup";
	$sql.=" from hardware h left join groups_cache g_c on g_c.group_id=h.ID,groups g ";
//	if ($_POST['onglet'] == "STAT")
//		$sql.="left join accountinfo on accountinfo.hardware_id=g.hardware_id";
	$sql.="	where deviceid = '_SYSTEMGROUP_' 
				and g.HARDWARE_ID=h.ID
				and g.request ";
	
	if ($_POST['onglet'] == "DYNA")
		$sql.=" != ";
	else
		$sql.=" = ";
		$sql .= " '' ";
	if($_SESSION["lvluser"] == ADMIN)
	$sql.=" and workgroup='GROUP_4_ALL' ";
	$sql.=" group by h.id, h.name, h.description, h.lastdate";
	if ($_POST['onglet'] == "STAT")
		$sql .= ", h.workgroup";
	$sql .= " order by ".$_POST['tri2']." " . check_param ($_POST, "sens");
	$reqCount = "SELECT COUNT(id) AS nb FROM ($sql) toto";
	$sql.=" LIMIT $limit[END] OFFSET $limit[BEGIN]";
}elseif ($_POST['onglet'] == "SERV"){
	
	$sql="SELECT group_id AS id, h.name AS name, h.description AS description, h.lastdate AS creat, COUNT(hardware_id) AS nbr
			from download_servers d_s left join hardware h on d_s.group_id=h.id
			group by group_id, h.name, h.description, h.lastdate order by ".$_POST['tri2']." " . check_param ($_POST, "sens");
	$reqCount="SELECT COUNT(id) AS nb FROM ($sql) toto";
	$sql.=" LIMIT $limit[END] OFFSET $limit[BEGIN]";
	
}

$resCount = mdb2_query($reqCount, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
$valCount = mdb2_fetch_assoc($resCount);
$result = mdb2_query( $sql, $_SESSION["readServer"]);
	$i=0;
	foreach ($result->getColumnNames (true) as $col){

		if ($col != "id" and $col != "workgroup"){
			if (check_param ($_POST, "sens") == "ASC")
			$sens="DESC";
			else
			$sens="ASC";
		$deb="<a OnClick='tri(\"".$col."\",\"".$sens."\",\"".$form_name."\")' >";
		$fin="</a>";
		$entete[$i++]=$deb.$col.$fin;
		}
	}
	if ($_SESSION["lvluser"] == SADMIN){
		$entete[$i++]="del";
		if ($_POST['onglet'] == "STAT")
		$entete[$i++]="Visible";
	}
	$i=0;
	$data = array();
	while($item = mdb2_fetch_object($result)){
		$deb="<a href='index.php?multi=29&popup=1&systemid=".$item ->id."' target='_blank'>";
		$fin="</a>";
		$data[$i][$entete[0]]=$deb.$item ->name.$fin;
		$data[$i][$entete[1]]=$item ->description;
		$data[$i][$entete[2]]=$item ->creat;
		if ($_SESSION["lvluser"] == ADMIN){
			$sql_count_my = "SELECT COUNT(hardware_id) AS c FROM groups_cache WHERE group_id=" . $item->id . " AND hardware_id ".$mesmachines; 
			$res_count_my = mdb2_query($sql_count_my, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
			$item_count_my = mdb2_fetch_object($res_count_my);
			if ($item_count_my -> c == "")
			$nbr='0';
			else
			$nbr=$item_count_my -> c;
		}else
		$nbr=$item ->nbr;
		$data[$i][$entete[3]]=$nbr;
		if ($_SESSION["lvluser"] == SADMIN){
			$data[$i][$entete[4]]="<img src='image/supp.png' OnClick='confirme(\"" . htmlspecialchars (str_replace ("'", "", $item->name)) . "\"," . $item->id . ",\"" . $form_name . "\",\"supp\",\"" . $l->g(640) . " \")'>";
			if ($_POST['onglet'] == "STAT")
			$data[$i][$entete[5]]="<input type='checkbox' OnClick='confirme(\"" . htmlspecialchars (str_replace ("'", "", $item->name)) . "\"," . $item->id . ",\"" . $form_name . "\",\"check_group\",\"" . $l->g(811) ." \")' " . ($item-> workgroup ? " checked" : "") . ">";
			
			//OnClick='page(\"".$item ->id."\",\"check\",\"".$form_name."\")'
		}
		$i++;
	}
	$titre=$l->g(768)." ".$valCount['nb'];
	$width=90;
	$height=300;
	tab_entete_fixe($entete,$data,$titre,$width,$height);
	show_page($valCount['nb'],$form_name);

//if we are SADMIN, add button for add new static group
if ($_POST['onglet'] == "STAT" and $_SESSION["lvluser"]==SADMIN)
echo "<br><input type=submit value='".$l->g(587)."' name='add_static_group'>";

echo "</table>";
echo "<input type='hidden' id='tri2' name='tri2' value='".$_POST['tri2']."'>";
echo "<input type='hidden' id='sens' name='sens' value='" . check_param ($_POST, "sens")."'>";
echo "<input type='hidden' id='supp' name='supp' value=''>";
echo "<input type='hidden' id='check_group' name='check_group' value=''>";
echo "</form>";

//if user want add a new group
if (isset($_POST['add_static_group']) and $_SESSION["lvluser"]==SADMIN){
	$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";
$img_modif="";
	//list of input we can modify
	$name=show_modif(check_param ($_POST, "NAME"),'NAME',0);
	$description=show_modif(check_param ($_POST, "DESCR"),'DESCR',1);
	//show new bottons
	$button_valid="<input title='".$l->g(625)."' type='image'  src='image/modif_valid_v2.png' name='Valid_modif'>";
	$button_reset="<input title='".$l->g(626)."' type='image'  src='image/modif_anul_v2.png' name='Reset_modif'>";
//form for modify values of group's
echo "<form name='add' action='' method='POST'>";
echo "<br><br><table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>".$tdhd.$l->g(577).$tdhf.$tdhdpb.$name.$tdhfpb;
echo "</tr>";
echo $tdhd."</b></td><td  align='left' width='20%' colspan='3'>";
echo "</tr><tr>".$tdhd.$l->g(53).$tdhf.$tdhdpb.$description.$tdhfpb;
echo "<tr><td align='left' colspan=4>".$button_valid."&nbsp&nbsp".$button_reset."&nbsp&nbsp".$img_modif."</td></tr>";
echo "$tdhfpb</table>";
echo "<input type='hidden' id='onglet' name='onglet' value='".$_POST['onglet']."'>";
echo "</form>";	
	
	
}

?>
