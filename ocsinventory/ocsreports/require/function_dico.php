<?php

require_once ("function_mdb2.php");

function search_all_item($onglet,$sous_onglet){

	$result_search_soft = mdb2_query( $_SESSION['query_dico'], $_SESSION["readServer"]);
	while($item_search_soft = mdb2_fetch_object($result_search_soft, CASE_UPPER)){
	 		$list[]=$item_search_soft->ID;
	}	
	return $list;	
}

function del_soft($onglet,$list_soft){
	if ($_SESSION['usecache'])
	$table="softwares_name_cache";
	else
	$table="softwares";
		
	foreach ($list_soft as $softID) {
		if (!preg_match("/^[0-9]+$/", $softID))
			die ("Invalid input for \$list_soft");
	}
	$sql_soft_name="select distinct NAME from ".$table." where ID in (".implode(",",$list_soft).")";
	if($onglet == "CAT" or $onglet == "UNCHANGED")	
		$sql_delete="delete from dico_soft where extracted in ($sql_soft_name)";
	if($onglet == "IGNORED")	
		$sql_delete="delete from dico_ignored where extracted in ($sql_soft_name)";		
	mdb2_query($sql_delete, $_SESSION["writeServer"]);	
}


function trans($onglet,$list_soft,$affect_type,$new_cat,$exist_cat){
	global $l;

	foreach ($list_soft as $softID) {
		if (!preg_match("/^[0-9]+$/", $softID))
			die ("Invalid input for \$list_soft");
	}

	if ($_SESSION['usecache'])
	$table="softwares_name_cache";
	else
	$table="softwares";
	//verif is this cat exist
	if ($new_cat != ''){
		$sql_verif="select extracted from dico_soft where formatted =?";
		$result_search_soft = mdb2_query( $sql_verif, $_SESSION["readServer"], "text", $new_cat);
	 	$item_search_soft = mdb2_fetch_object($result_search_soft);
	 	if (isset($item_search_soft->extracted) or $new_cat == "IGNORED" or $new_cat == "UNCHANGED"){
	 		$already_exist=TRUE;
	 	}
	}
	
	if ($onglet == "NEW"){
		$table="softwares";
		$ok=TRUE;		
	}else{
		if (!isset($already_exist))	{
			del_soft($onglet,$list_soft);
		}		
		$ok = TRUE;
	}	

	if ($ok == TRUE){
		if ($affect_type== "EXIST_CAT"){
				if ($exist_cat == "IGNORED"){			
					$sql="insert into dico_ignored (extracted) select distinct NAME from ".$table." where ID in (".implode(",",$list_soft).")";						
				}elseif($exist_cat == "UNCHANGED"){
					$sql="insert into dico_soft (extracted,formatted) select distinct NAME,NAME from ".$table." where ID in (".implode(",",$list_soft).")";			
				}else
					$sql="insert into dico_soft (extracted,formatted) select distinct NAME," . $_SESSION["writeServer"]->quote ($exist_cat, "text") . " from ".$table." where ID in (".implode(",",$list_soft).")";
		}else{
		 	if (!isset($already_exist)){
		 		$sql="insert into dico_soft (extracted,formatted) select distinct NAME," . $_SESSION["writeServer"]->quote ($new_cat, "text") . " from ".$table." where ID in (".implode(",",$list_soft).")";
		 	}else
		 		echo "<script>alert('".$l->g(771)."')</script>";			
		}
		if ($sql!=''){
			mdb2_query($sql, $_SESSION["writeServer"]);	
		}
	}
	
}
?>
