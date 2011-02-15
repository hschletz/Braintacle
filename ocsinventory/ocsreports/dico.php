<?php 
/*
 * New version of dico page 
 * 
 */
$sadmin_profil=1;
include('security.php');
require_once('require/function_table_html.php');
require_once('require/function_dico.php');
require_once('require/function_mdb2.php');
require_once('require/function_misc.php');

if (PEAR::isError($_SESSION['readServer']->loadModule('Function')))
	die ('Could not load MDB2 Function Module');

$alpha = $_SESSION['readServer']->function->upper(
    $_SESSION['readServer']->function->substring ('TRIM(FROM name)',1,1)
);

//use or not cache
if ($_SESSION['usecache'])
	$table="softwares_name_cache";
else
	$table="softwares";
//form name
$form_name='admin_param';
//form open
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//definition of onglet
$def_onglets['CAT']='CATEGORIES'; //Categories
$def_onglets['NEW']='NEW'; //nouveau logiciels
$def_onglets['IGNORED']='IGNORED'; //ignored
$def_onglets['UNCHANGED']='UNCHANGED'; //unchanged
//default => first tab
if (check_param ($_POST, 'onglet') == "")
$_POST['onglet']="CAT";
//reset search
if (check_param ($_POST, 'RESET')=="RESET")
unset($_POST['search']);
//filtre
if (check_param ($_POST, "search")){
	$search_cache = " AND cache.name $LIKE " . $_SESSION["readServer"]->quote ("%$_POST[search]%");
	$search_count = " AND extracted $LIKE " . $_SESSION["readServer"]->quote ("%$_POST[search]%");
}
else{
	$search="";
	$search_cache = "";
	$search_count = "";
}
//show first lign of onglet
onglet($def_onglets,$form_name,"onglet",0);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
<tr><td align='center' colspan=10>";
//attention=> result with restriction
if ($search_count != "" or $search_cache != "")
echo "<font color=red><b>".$l->g(767)."</b></font>";
/**************************************ACTION ON DICO SOFT**************************************/

//transfert soft
if(check_param ($_POST, 'TRANS') == "TRANS"){	
	if (check_param($_POST, 'all_item') != ''){
		$list_check=search_all_item($_POST['onglet'],$_POST['onglet_soft']);
	}else{
		
		foreach ($_POST as $key=>$value){
			if (substr($key, 0, 5) == "check" and check_param ($_POST, $key)){
				$list_check[]=substr($key, 5);
			} 				
		}
	}
	if (isset($list_check) and $list_check != '')	
	trans($_POST['onglet'],$list_check,$_POST['AFFECT_TYPE'],check_param($_POST, 'NEW_CAT'),check_param($_POST, 'EXIST_CAT'));
}
//delete a soft in list => return in 'NEW' liste
if (check_param ($_POST, 'SUP_PROF') != ""){
	del_soft($_POST['onglet'],array($_POST['SUP_PROF']));
}
/************************************END ACTION**************************************/

if ($_POST['onglet'] != check_param ($_POST, 'old_onglet'))
unset($_POST['onglet_soft']);
/*******************************************************CAS OF CATEGORIES*******************************************************/
if ($_POST['onglet'] == 'CAT'){
	//search all categories
	$sql_list_cat = "SELECT formatted AS name
		  from dico_soft where extracted!=formatted ".$search_count." group by formatted";
	 $result_list_cat = mdb2_query( $sql_list_cat, $_SESSION["readServer"]);
	 $i=1;
	 $first_onglet = NULL;
	 $list_cat = NULL;
	 while($item_list_cat = mdb2_fetch_object($result_list_cat)){
	 	if ($i==1)
		$first_onglet=$i;
		$list_cat[$i]=$item_list_cat -> name;
		$i++;
	 }
	 //delete categorie
	if(isset($_POST['SUP_CAT']) and $_POST['SUP_CAT']!=""){	
		if ($_POST['SUP_CAT'] == 1)
		$first_onglet=2;
		$reqDcat = "DELETE FROM dico_soft WHERE formatted=?";
		mdb2_query($reqDcat, $_SESSION["writeServer"], "text", $list_cat[$_POST["SUP_CAT"]]) or die(mdb2_error($_SESSION["writeServer"]));
		unset($list_cat[$_POST['SUP_CAT']]);		
	}
	//no selected? default=>first onglet
	 if (check_param ($_POST, 'onglet_soft')=="" or !isset($list_cat[$_POST['onglet_soft']]))
	 $_POST['onglet_soft']=$first_onglet;
	 //show all categories
	 onglet($list_cat,$form_name,"onglet_soft",7);
	 //You can delete or not?
	 if ($i != 1 and isset($list_cat[$_POST['onglet_soft']]))
	 echo "<a href=# OnClick='return confirme(\"\",\"".$_POST['onglet_soft']."\",\"".$form_name."\",\"SUP_CAT\",\"".$l->g(640)."\");'><img src=image/supp.png></a></td></tr><tr><td>";
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_EXIST";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT distinct ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= mdb2_quote_identifier ($value, $_SESSION["readServer"]) . ',';
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_soft left join ".$table." cache on dico_soft.extracted=cache.name
			 WHERE formatted=" . $_SESSION["readServer"]->quote ($list_cat[$_POST['onglet_soft']]) . $search_count . " GROUP BY extracted, cache.id";
}
/*******************************************************CAS OF NEW*******************************************************/
if ($_POST['onglet'] == 'NEW'){
	$search_dico_soft="SELECT extracted AS name FROM dico_soft";
	$search_ignored_soft="SELECT extracted AS name FROM dico_ignored";
	$sql_list_alpha = "SELECT DISTINCT $alpha AS alpha
				 from ".$table." cache 
				 WHERE $alpha IS NOT NULL AND name NOT IN ($search_dico_soft)
			and name not in (".$search_ignored_soft.") ".$search_cache;	
	$first='';

	//execute the query only if necessary 
	if(check_param ($_SESSION, 'REQ_ONGLET_SOFT') != $sql_list_alpha){
		$result_list_alpha = mdb2_query( $sql_list_alpha, $_SESSION["readServer"]);
		$i=1;
		 while($item_list_alpha = mdb2_fetch_object($result_list_alpha)){
			// CAVEAT: The following comparisions do not respect different character encodings.
			// They just happen to work with ISO 8859-1, UTF-8 and other encodings which have the same characters in the given range.
		 	if (strtoupper($item_list_alpha -> alpha) != "" 
				and strtoupper($item_list_alpha -> alpha) != "'"
				and ord (strtoupper($item_list_alpha -> alpha)) != 0xC2
				and ord (strtoupper($item_list_alpha -> alpha)) != 0xC3
				and ord (strtoupper($item_list_alpha -> alpha)) != 0xC4){
					if ($first == ''){
						$first=$i;
					}
					$list_alpha[$i]=strtoupper($item_list_alpha -> alpha);
					$i++;
		 	}
		}
		//execute the query only if necessary 
		$_SESSION['REQ_ONGLET_SOFT'] = $sql_list_alpha;
		$_SESSION['ONGLET_SOFT'] = isset($list_alpha) ? $list_alpha : NULL;
		$_SESSION['FIRST_DICO'] = $first;
	}else{
		$list_alpha=$_SESSION['ONGLET_SOFT'];
	}
	if (!isset($_POST['onglet_soft']))
	$_POST['onglet_soft']=$_SESSION['FIRST_DICO'];
	 onglet((isset($list_alpha) ? $list_alpha : NULL),$form_name,"onglet_soft",20);
	
	//search all soft for the tab as selected 
	$search_soft="select distinct name from ".$table." cache
			WHERE name $LIKE " . $_SESSION["readServer"]->quote ($_SESSION['ONGLET_SOFT'][$_POST['onglet_soft']] . "%", "text") . "
			and name not in (".$search_dico_soft.")
			and name not in (".$search_ignored_soft.") ".$search_cache;
	$result_search_soft = mdb2_query( $search_soft, $_SESSION["readServer"]);
	$list_soft="";
 	while($item_search_soft = mdb2_fetch_object($result_search_soft)){
		 		$list_soft .= $_SESSION["readServer"]->quote ($item_search_soft->name, "text") . ", ";
 	}
 	$list_soft=substr($list_soft,0,-2);
 	if ($list_soft == "")
 	$list_soft="''";

	$list_fields= array('SOFT_NAME'=>'NAME',
						'ID'=>'ID',
	 					 'QTE'=> 'QTE',
    					 'CHECK'=>'ID');
	$table_name="CAT_NEW";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','QTE'=>'QTE','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'CHECK' and $key != 'QTE')
		$querydico .= mdb2_quote_identifier ($value, $_SESSION["readServer"]) . ',';
		elseif ($key == 'QTE')
		$querydico .= ' COUNT(name) AS ' . mdb2_quote_identifier ($value, $_SESSION["readServer"]) . ',';
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from softwares 
			where name in (".$list_soft.") and name != ''
			group by name, softwares.id ";
}
/*******************************************************CAS OF IGNORED*******************************************************/
if ($_POST['onglet'] == 'IGNORED'){
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_IGNORED";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= mdb2_quote_identifier ($value, $_SESSION["readServer"]) . ',';
	} 
	if ($search_count != ""){
		$modif_search = " where ".substr($search_count,5);
	}
	else $modif_search = "";
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_ignored left join ".$table." cache on cache.name=dico_ignored.extracted ".$modif_search." group by EXTRACTED, cache.id ";
}
/*******************************************************CAS OF UNCHANGED*******************************************************/
if ($_POST['onglet'] == 'UNCHANGED'){
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_UNCHANGE";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= mdb2_quote_identifier ($value, $_SESSION["readServer"]) . ',';
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_soft left join ".$table." cache on cache.name=dico_soft.extracted
	 	where extracted=formatted ".$search_cache." group by EXTRACTED, cache.id ";
}
$_SESSION['query_dico']=$querydico;
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querydico,$form_name,80); 
echo "</td></tr>";
$search=show_modif(check_param ($_POST, 'search'),"search",'0');
$trans= "<input name='all_item' id='all_item' type='checkbox' ".(isset($_POST['all_item'])? " checked ": "").">".$l->g(384);
//recover all categories
$sql_list_categories="SELECT DISTINCT formatted AS name FROM dico_soft WHERE formatted != extracted order by formatted";
$result_list_categories = mdb2_query( $sql_list_categories, $_SESSION["readServer"]);
while($item_list_categories = mdb2_fetch_object($result_list_categories)){
	$list_categories[$item_list_categories ->name]=$item_list_categories ->name;	
}
//definition of all possible options
$choix_affect['NEW_CAT']=$l->g(385);
$choix_affect['EXIST_CAT']=$l->g(387);
$list_categories['IGNORED']="IGNORED";
$list_categories['UNCHANGED']="UNCHANGED";
$trans.=show_modif($choix_affect,"AFFECT_TYPE",'2',$form_name);
if (isset ($_POST["AFFECT_TYPE"])) {
if ($_POST['AFFECT_TYPE'] == 'EXIST_CAT'){
	$trans.=show_modif($list_categories,"EXIST_CAT",'2');	
	$verif_field="EXIST_CAT";
}
elseif ($_POST['AFFECT_TYPE'] == 'NEW_CAT'){
	$trans.=show_modif(check_param ($_POST, 'NEW_CAT'),"NEW_CAT",'0');
	$verif_field="NEW_CAT";
}	

if ($_POST['AFFECT_TYPE']!='')
$trans.= "<input type='button' name='TRANSF' value='".$l->g(13)."' onclick='return verif_field(\"".$verif_field."\",\"TRANS\",\"".$form_name."\");'>";
}

echo "<tr><td>".$search."<input type='submit' value='".$l->g(393)."'><input type='button' value='".$l->g(396)."' onclick='return pag(\"RESET\",\"RESET\",\"".$form_name."\");'>";
if ($result_exist != FALSE)
echo "<div align=right> ".$trans."</div>";
echo "</td></tr></table></table>";
echo "<input type='hidden' name='RESET' id='RESET' value=''>";
echo "<input type='hidden' name='TRANS' id='TRANS' value=''>";
echo "<input type='hidden' name='SUP_CAT' id='SUP_CAT' value=''>";
echo "</form>";
?>