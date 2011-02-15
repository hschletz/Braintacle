<?php
include('security.php');
require_once('require/function_table_html.php');
require_once('require/function_config_generale.php');
require_once('require/function_mdb2.php');
require_once('require/function_misc.php');

check_param ($_POST, "COMPAR", "^[<>=]?$");
check_param ($_POST, "NBRE", "^[0-9]*$");

if (PEAR::isError($_SESSION['readServer']->loadModule('Function')))
	die ('Could not load MDB2 Function Module');

if (check_param ($_POST, 'RESET')){ 
unset($_POST['search']);
unset($_POST['NBRE']);
}
if (check_param ($_POST, 'OLD_ONGLET') != check_param ($_POST, 'onglet_bis'))
$_POST['page']=0;

$alpha = $_SESSION['readServer']->function->substring ("TRIM(' ' FROM name)",1,1);

//search all onglet
$_post_search = $_SESSION['readServer']->quote ("%" . check_param ($_POST, "search") . "%", "text");
if( $_SESSION["lvluser"] == ADMIN) {
	$sql_list_alpha = "SELECT $alpha AS alpha, name ";
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha .=",count(*) AS nb ";
	$sql_list_alpha .=" from softwares,accountinfo a 
						where ".$_SESSION["mesmachines"]."
						and a.hardware_id=softwares.HARDWARE_ID and";			
}else{
	$sql_list_alpha = "SELECT $alpha AS alpha, name ";
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha.=" ,count(*) AS nb ";
	$sql_list_alpha.= " from ";
	//BEGIN use CACHE
	if ($_SESSION["usecache"] == 1 
		and !(isset($_POST['NBRE']) and $_POST['NBRE'] != "") 
		and !(isset($_POST['search']) and $_POST['search'] != ""))
	$sql_list_alpha.="softwares_name_cache where ";
	else
	$sql_list_alpha.="softwares where ";
}
if (isset($_POST['search']) and $_POST['search'] != "")
	$sql_list_alpha .= " softwares.name $LIKE $_post_search AND ";
	$sql_list_alpha .= " $alpha IS NOT NULL GROUP BY name ";	
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha.=" having COUNT(*) ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
	$sql_list_alpha.=" order by 1";

//execute the query only if necessary 
if(check_param ($_SESSION, 'REQ_ONGLET_SOFT') != $sql_list_alpha or !isset($_POST['onglet_bis'])){
	$result_list_alpha = mdb2_query( $sql_list_alpha, $_SESSION["readServer"]);
 	while($item_list_alpha = mdb2_fetch_object($result_list_alpha)){
		// CAVEAT: The following comparisions do not respect different character encodings.
		// They just happen to work with ISO 8859-1, UTF-8 and other encodings which have the same characters in the given range.
 		if (strtoupper($item_list_alpha -> alpha) != "" 
 			and strtoupper($item_list_alpha -> alpha) != "'"
			and ord (strtoupper($item_list_alpha -> alpha)) != 0xC2
			and ord (strtoupper($item_list_alpha -> alpha)) != 0xC3
			and ord (strtoupper($item_list_alpha -> alpha)) != 0xC4 ){
				if (!isset($_POST['onglet_bis']))
					$_POST['onglet_bis']=strtoupper($item_list_alpha -> alpha);
				$list_alpha[strtoupper($item_list_alpha -> alpha)]=strtoupper($item_list_alpha -> alpha);
				if (!isset($first)){
					$first=$list_alpha[strtoupper($item_list_alpha -> alpha)];				
				}
 		}
	}
	
	if (!isset($list_alpha[str_replace('\"','"',check_param ($_POST, 'onglet_bis'))])){
		$_POST['onglet_bis']=isset ($first) ? $first : null;
	}
	$_SESSION['REQ_ONGLET_SOFT']= $sql_list_alpha;
	$_SESSION['ONGLET_SOFT']=isset ($list_alpha) ? $list_alpha : null;
}
$form_name = "all_soft";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
 onglet($_SESSION['ONGLET_SOFT'],$form_name,"onglet_bis",20);
 $limit=nb_page($form_name);
if ((isset($_POST['search']) and $_POST['search'] != "") or
	((isset($_POST['NBRE']) and $_POST['NBRE'] != "")))
echo "<font color=red size=3><b>".$l->g(767)."</b></font>";

//sql query for CSV export 
$_post_onglet_bis = $_SESSION['readServer']->quote ("$_POST[onglet_bis]%", "text");
$sql_csv="";
$sql_filter_name="";
$args = array();
$types = array();
if( $_SESSION["lvluser"] == ADMIN ) {
	$sql="SELECT name, COUNT(name) AS nbre FROM softwares, accountinfo a 
			where ".$_SESSION["mesmachines"]."
				and a.hardware_id=softwares.HARDWARE_ID";
	$sql_csv=$sql;
	$sql_filter = " AND name $LIKE $_post_onglet_bis ";
	if (isset($_POST['search']) and $_POST['search'] != "") {
		$sql_filter_name = " AND name $LIKE $_post_search ";
	} else {
		$sql_filter_name = '';
	}
	$sql_groupby="	group by name";
	$sql.=$sql_filter.$sql_filter_name.$sql_groupby;
	$sql_csv.=$sql_filter_name.$sql_groupby;	
}else{
	//BEGIN use CACHE
	if ($_SESSION["usecache"] == 1){
		$search_soft="select name from softwares_name_cache 
				WHERE name $LIKE $_post_onglet_bis ";
		if (isset($_POST['search']) and $_POST['search'] != "")
		$search_soft.=" AND name $LIKE $_post_search ";
		$result_search_soft = mdb2_query( $search_soft, $_SESSION["readServer"]);
		$list_soft="";
		$count_soft=0;
	
	 	while($item_search_soft = mdb2_fetch_object($result_search_soft)){
	 		$list_soft .= $_SESSION["readServer"]->quote ($item_search_soft->name, "text") . ", ";
	  		$count_soft++;
	 	}
	 	$list_soft=substr($list_soft,0,-2);
	 	if ($list_soft == "")
	 	$list_soft="''";
	
		$sql="SELECT name, COUNT(name) AS nbre FROM softwares 
				where name in (".$list_soft.")
				group by name";
	//END use CACHE
	}else{
		$sql = "SELECT name, COUNT(name) AS nbre FROM softwares 
			WHERE name $LIKE $_post_onglet_bis ";
		if (isset($_POST['search']) and $_POST['search'] != "")
			$sql .= " AND name $LIKE $_post_search ";
		$sql.="	group by name";
	}
	$sql_csv="SELECT name, COUNT(name) AS nbre FROM softwares 
			group by name";
			
}
if (isset($_POST['NBRE']) and $_POST['NBRE'] != ""){
	$sql.=" having COUNT(name) ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
	$sql_csv.=" having COUNT(name) ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
}

if ((!isset($count_soft) or $count_soft == 0) or (isset($_POST['NBRE']) and $_POST['NBRE'] != "" )){
	$reqCount = "SELECT COUNT(name) AS nb FROM (".$sql.") toto";
//	echo $reqCount;
	$resCount = mdb2_query($reqCount, $_SESSION["readServer"]) or die(mdb2_error($_SESSION["readServer"]));
	$valCount = mdb2_fetch_assoc($resCount);
	$count_soft=$valCount['nb'];
}

$sql.=" ORDER BY 2 DESC, name ASC LIMIT $limit[END] OFFSET $limit[BEGIN]";
$_SESSION["forcedRequest"]=$sql_csv;

$result = mdb2_query( $sql, $_SESSION["readServer"]);
$num_rows_reality = mdb2_num_rows($result);
	$i=0;
	foreach ($result->getColumnNames (true) as $colname){
		if ($colname != 'id')
		$entete[$i++]=$colname;
	}
	
	$i=0;
	$data = array();
	while($item = mdb2_fetch_object($result)){
		if ($num_rows_reality != 0)
		$data[$i][$entete[0]]=htmlspecialchars($item ->name);
		$data[$i][$entete[1]]=$item ->nbre;
		$i++;
		}

	$titre=$l->g(768)." ".$count_soft;
	$width=60;
	$height=300;
	tab_entete_fixe($entete,$data,$titre,$width,$height);
	show_page($count_soft,$form_name);
	




echo "<br><div align=center><table bgcolor='#66CCCC'><tr><td colspan=2 align=center >FILTRES</td></tr><tr><td align=right>".$l->g(382).": <input type='input' name='search' value='".check_param ($_POST, 'search')."'>
				<td rowspan=2><input type='submit' value='".$l->g(393)."'><input type='submit' value='".$l->g(396)."' name='RESET'>
		</td></tr><tr><td align=right>nbre <select name='COMPAR'>
			<option value='&lt;' " . (check_param ($_POST, 'COMPAR') == '<' ? 'selected' : '') . ">&lt;</option>
			<option value='&gt;' " . (check_param ($_POST, 'COMPAR') == '>' ? 'selected' : '') . ">&gt;</option>
			<option value='='    " . (check_param ($_POST, 'COMPAR') == '=' ? 'selected' : '') . ">=</option>
		</select><input type='input' name='NBRE' value='".check_param ($_POST, 'NBRE')."' ".$numeric."></td></tr>
		<tr><td colspan=2 align=center><a href='ipcsv.php'>".$l->g(136)." ".$l->g(765)."</a></td></tr></table></div>
		";

echo "<input type='hidden' name='OLD_ONGLET' value='".$_POST['onglet_bis']."'>";
 echo "</form></table>";
?>
