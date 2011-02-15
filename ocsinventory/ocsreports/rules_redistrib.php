<?php
/*
 * Rules for redistribution servers
 */
$sadmin_profil=1;
include('security.php');
require_once('require/function_table_html.php');
require_once('require/function_rules.php');
require_once('require/function_mdb2.php');
require_once('require/function_misc.php');
//DEL RULE
if (check_param ($_POST, 'SUP_PROF') != ""){	
	delete_rule($_POST['SUP_PROF']);
}
//ADD new rule
if (check_param ($_POST, 'ADD_RULE')){
	add_rule($_POST['RULE_NAME'],$_POST);
}
//modif rule
if (check_param ($_POST, 'MODIF_RULE')){	
	$name_exist=verify_name($_POST['RULE_NAME'],"and rule != ".$_POST['OLD_MODIF']);
	if ($name_exist == 'NAME_NOT_EXIST'){
		delete_rule($_POST['OLD_MODIF']);
		add_rule($_POST['RULE_NAME'],$_POST,$_POST['OLD_MODIF']);
		echo "<script>alert('".$l->g(711)."');</script>";	
	}
	else{
	echo "<script>alert('".$l->g(670)."');</script>";	
	}
}
//form name
$form_name = "rules";
//show all rules
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
			$list_fields= array('ID_RULE'=>'RULE',
								'RULE_NAME'=>'RULE_NAME',
								'SUP'=>'RULE',
								'MODIF'=>'RULE',
								);
			$table_name="DOWNLOAD_AFFECT_RULES";
			$default_fields= array('ID_RULE'=>'ID_RULE','RULE_NAME'=>'RULE_NAME','SUP'=>'SUP','MODIF'=>'MODIF');
			$list_col_cant_del=array('ID_RULE'=>'ID_RULE','SUP'=>'SUP','MODIF'=>'MODIF');
			$queryRules = 'SELECT distinct ';
			foreach ($list_fields as $key=>$value){
				if($key != 'SUP')
				$queryRules .= $value.',';		
			} 
			$queryRules=substr($queryRules,0,-1);
			$queryRules .= " from download_affect_rules ";
			printEnTete($l->g(673));
			echo "<br>";
		tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryRules,$form_name,80);
		echo "<br>";
	
//Modif a rule => get this values 
if (check_param ($_POST, 'MODIF') != "" and check_param ($_POST, 'OLD_MODIF') != $_POST['MODIF']){
	$sql="select priority,cfield,op,compto,rule_name 
			from download_affect_rules 
		 where rule=? 
			order by priority";
	$res = mdb2_query( $sql, $_SESSION["readServer"], "integer", $_POST['MODIF']);
	$i=1;
	while ($val = mdb2_fetch_assoc( $res )){
		$_POST['PRIORITE_'.$i]=$val['priority'];
		$_POST['CFIELD_'.$i]=$val['cfield'];
		$_POST['OP_'.$i]=$val['op'];
		$_POST['COMPTO_'.$i]=$val['compto'];
		$_POST['RULE_NAME']=$val['rule_name'];
		$i++;
	}
	$_POST['NUM_RULES']=$i-2;
}

//new rule
if (check_param ($_POST, 'NEW_RULE') or check_param ($_POST, 'NUM_RULES') or check_param ($_POST, 'MODIF') != ""){
	if (check_param ($_POST, 'MODIF') != "")
	$modif=$_POST['MODIF'];
	else
	$modif=check_param ($_POST, 'OLD_MODIF');
	$numero=check_param ($_POST, 'NUM_RULES')+1;
	$tab_nom=$l->g(674).show_modif(check_param ($_POST, 'RULE_NAME'),"RULE_NAME","0");
	$tab="<table align='center'>";
	$i=1;
	while($i<$numero+1){
		if ($i==1)
		$entete='YES';
		else
		$entete='NO';
	$tab.=fields_conditions_rules($i,$entete);
	$i++;
	}
	echo $tab_nom;
	echo $tab;
	echo "</tr></table>";
	echo "<a onclick='return pag(".$numero.",\"NUM_RULES\",\"rules\")'><font color=green>".$l->g(682)."</font></a>&nbsp<a onclick='return pag(\"RAZ\",\"RAZ\",\"rules\");'><font color=\"red\">".$l->g(113)."</font></a><br><br>";
	if (check_param ($_POST, 'MODIF') != "" or check_param ($_POST, 'OLD_MODIF') != "")
	echo "<input type='submit'  value='".$l->g(625)."' name='MODIF_RULE' onclick='return check();'>";	
	else
	echo "<input type='submit'  value='".$l->g(683)."' name='ADD_RULE' onclick='return check();'>";	
	echo "<input type='hidden' id='NUM_RULES' name='NUM_RULES' value=''>";
	echo "<input type='hidden' id='RAZ' name='RAZ' value=''>";
	echo "<input type='hidden' id='OLD_MODIF' name='OLD_MODIF' value='".$modif."'>";
}else{	
echo "<input type='submit'  value='".$l->g(685)."' name='NEW_RULE'>";	
}
echo "</form>";
?>
