<?php
/*
 * For redistribution's server
 */
$sadmin_profil=1;
include('security.php');
require_once('require/function_server.php');
require_once('require/function_mdb2.php');
require_once('require/function_misc.php');

//delete one server or all
if(check_param ($_POST, "supp")){
	if ($_POST["supp"] != "ALL"){
		$verif[0]['sql']="select fileid from download_enable,devices
				where download_enable.id=devices.ivalue
				and download_enable.SERVER_ID=" . check_param ($_POST, "supp", "^[0-9]+$");
		$verif[0]['condition']='EXIST';
		$verif[0]['MSG_ERROR']=$l->g(689)." ".$l->g(687);
		$ok=verification($verif);
		if (isset($ok)){
            mdb2_query("delete from download_enable where SERVER_ID=?", $_SESSION["writeServer"], "integer", $_POST["supp"]) or die(mdb2_error($_SESSION["writeServer"]));
			mdb2_query("delete from download_servers where hardware_id=?", $_SESSION["writeServer"], "integer", $_POST["supp"]) or die(mdb2_error($_SESSION["writeServer"]));
		}
	}
	elseif ($_POST["supp"] == "ALL"){
		$verif[0]['sql']="select fileid from download_enable,devices
				where download_enable.id=devices.ivalue
				and GROUP_ID=".$systemid;
		$verif[0]['condition']='EXIST';
		$verif[0]['MSG_ERROR']=$l->g(688)." ".$l->g(690);
		$ok=verification($verif);
		if (isset($ok)){
			mdb2_query("delete from download_enable where GROUP_ID=?", $_SESSION["writeServer"], "integer", $systemid) or die(mdb2_error($_SESSION["writeServer"]));
			$sql="delete from download_servers where GROUP_ID = ?";
			mdb2_query($sql, $_SESSION["writeServer"], "integer", $systemid) or die(mdb2_error($_SESSION["writeServer"]));
		}
	}
}

//Modif server's machine
if (isset($_POST['Valid_modif_x']) and isset($_POST['modif']) and $_POST['modif'] != ""){
	$default_values=look_default_values();
	if (trim($_POST['URL']) == "")
	$_POST['URL']=$default_values['tvalue']['DOWNLOAD_SERVER_URI'];
	if (trim($_POST['REP_STORE']) == "")
	$_POST['REP_STORE']=$default_values['tvalue']['DOWNLOAD_SERVER_DOCROOT'];
		
	if ($_POST['modif'] != "ALL")
	{
		
			$sql= "update download_servers set URL=? ,ADD_REP=? where hardware_id=?";
			mdb2_query($sql, $_SESSION["writeServer"], array ("text", "text", "integer"), array ($_POST['URL'], $_POST['REP_STORE'], $_POST['modif'])) or die(mdb2_error($_SESSION["writeServer"]));
			$sql= "update download_enable set pack_loc=? where SERVER_ID=?";
			mdb2_query($sql, $_SESSION["writeServer"], array ("text", "integer"), array ($_POST['URL'], $_POST['modif'])) or die(mdb2_error($_SESSION["writeServer"]));
	
	}else
	{
	
			$sql="update download_servers set URL=?, ADD_REP=? where GROUP_ID=?";
			mdb2_query($sql, $_SESSION["writeServer"], array ("text", "text", "integer"), array ($_POST['URL'], $_POST['REP_STORE'], $systemid)) or die(mdb2_error($_SESSION["writeServer"]));
			$sql= "update download_enable set pack_loc=? where GROUP_ID=?";
			mdb2_query($sql, $_SESSION["writeServer"], array ("text", "integer"), array ($_POST['URL'], $systemid)) or die(mdb2_error($_SESSION["writeServer"]));
	
	}
}
//view of all group's machin
if (isset($systemid))
{
	if (check_param ($_POST, 'tri2') == "")
	$_POST['tri2']=1;
	if (!(isset($_POST["pcparpage"])) and isset($_GET['res_pag']))
	$_POST["pcparpage"]=$_GET['res_pag'];
	if (!(isset($_POST["page"])) and isset($_GET['page']))
	$_POST["page"]=$_GET['page'];
	$form_name='nb_4_pag';
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$limit=nb_page($form_name);
	$sql="select download_servers.HARDWARE_ID as ID,
			  hardware.NAME,
			  hardware.IPADDR,
			  hardware.DESCRIPTION,
			  download_servers.URL,
			  download_servers.ADD_REP
		from hardware right join download_servers on hardware.id=download_servers.hardware_id
		where download_servers.GROUP_ID=".$systemid." order by " . check_param ($_POST, "tri2", "^[a-zA-Z0-9]$") . " " . check_param ($_POST, "sens", "^(ASC|DESC)?$", null, false);
	$reqCount="select count(*) AS nb from (".$sql.") toto";
	$resCount = mdb2_query($reqCount, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
	$valCount = mdb2_fetch_assoc($resCount);
	$sql.=" limit ".$limit["END"]." OFFSET ".$limit["BEGIN"];
		$result = mdb2_query($sql, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
		$i=0;
	if (check_param ($_POST, 'sens') == "ASC")
		$sens="DESC";
	else
		$sens="ASC";
	foreach ($result->getColumnNames(true) as $col) {
		$deb="<a OnClick='tri(\"".$col."\",\"".$sens."\",\"".$form_name."\")' >";
		$fin="</a>";
		$entete[$i++]=$deb.$col.$fin;			
	}
		$entete[$i++]="SUP <br><img src=image/delete_all.png OnClick='confirme(\"\",\"ALL\",\"".$form_name."\",\"supp\",\"".$l->g(640)." ".$l->g(643)." \");'>";
		$entete[$i]="MODIF  <img src=image/modif_all.png  OnClick='pag(\"ALL\",\"modif\",\"".$form_name."\")'>";

	$i=0;
	$data2=NULL;
	//" du groupe ".$data[$_GET['viewmach']]['ID'].
	while($item = mdb2_fetch_object($result, CASE_UPPER)){
			$data2[$i]['ID']=$item ->ID;
			$data2[$i]['NAME']=$item ->NAME;
			$data2[$i]['IP_ADDR']=$item ->IPADDR;
			$data2[$i]['DESCRIPTION']=$item ->DESCRIPTION;
			$data2[$i]['URL']="http://".$item ->URL;
			$data2[$i]['REP_STORE']=$item ->ADD_REP;
			$data2[$i]['SUP']="<img src=image/supp.png OnClick='confirme(\"".$item ->NAME."\",\"".$item ->ID."\",\"".$form_name."\",\"supp\",\"".$l->g(640)." ".$l->g(644)." \");'>";
			if ($data2[$i]['IP_ADDR'] != "" )
			$data2[$i]['MODIF']="<img src=image/modif_tab.png OnClick='pag(\"".$i."\",\"modif\",\"".$form_name."\")'>";
			else
			$data2[$i]['MODIF']="";
			$i++;
	}
	 $total="<font color=red> (<b>".$valCount['nb']." ".$l->g(652)."</b>)</font>";
	tab_entete_fixe($entete,$data2,$l->g(645).$total,"95","300");
	show_page($valCount['nb'],$form_name);
	echo "<input type='hidden' id='supp' name='supp' value=''>";	
	echo "<input type='hidden' id='modif' name='modif' value=''>";
	echo "<input type='hidden' id='tri2' name='tri2' value='".$_POST['tri2']."'>";
	echo "<input type='hidden' id='sens' name='sens' value='".check_param ($_POST, 'sens')."'>";
	echo "</table>";
	echo "</form>";
	//detail of group's machin
	if (check_param ($_POST, 'modif')!=""  and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']))
	{
		$tab_name[1]=$l->g(646).": ";
		$tab_name[2]=$l->g(648).": ";
		$tab_typ_champ[1]['DEFAULT_VALUE']=substr($data2[$_POST['modif']]['URL'],7);
		$tab_typ_champ[1]['COMMENT_BEFORE']="<b>http://</b>";
		$tab_typ_champ[1]['COMMENT_BEHING']="<small>".$l->g(691)."</small>";
		$tab_typ_champ[1]['INPUT_NAME']="URL";
		$tab_typ_champ[1]['INPUT_TYPE']=0;
		$tab_typ_champ[2]['DEFAULT_VALUE']=$data2[$_POST['modif']]['REP_STORE'];
		$tab_typ_champ[2]['INPUT_NAME']="REP_STORE";
		$tab_typ_champ[2]['INPUT_TYPE']=0;
		$tab_hidden["modif"]=$data2[$_POST['modif']]['ID'];
		$tab_hidden["pcparpage"]=$_POST['pcparpage'];
		$tab_hidden["page"]=$_POST['page'];
		$tab_hidden["old_pcparpage"]=$_POST['old_pcparpage'];
		if ($_POST['modif'] == "ALL"){
			$tab_hidden["modif"]="ALL";
			$title= $l->g(692);
		}
		else
			$title= $l->g(693)." ".$data2[$_POST['modif']]['NAME'];
	        $comment=$l->g(694);
	        tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment);
		
	}
	
}
?>
