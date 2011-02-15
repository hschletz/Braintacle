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
//Modified on $Date: 2008-03-04 17:07:55 $$Author: dliroulet $($Revision: 1.19 $)

@set_time_limit(0); 
error_reporting(E_ALL);
require_once ('require/function_mdb2.php');
require_once ('require/function_misc.php');
?>
<html>
<head>
<TITLE>OCS Inventory Installation</TITLE>
<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
</head><body>

<?php 
printEnTeteInstall("OCS Inventory Installation");

if( isset($fromAuto) && $fromAuto==true)
echo "<center><br><font color='green'><b>Current installed version ".$valUpd["tvalue"]." is lower than this version (".GUI_VER.") automatic install launched</b></red><br></center>";

if( isset($fromdbconfig_out) && $fromdbconfig_out==true)
echo "<center><br><font color='green'><b>DB configuration not completed. Automatic install launched</b></red><br></center>";

/*
if(!isset($_POST["name"])) {
	if( $hnd = @fopen("dbconfig.inc.php", "r") ) {
		fclose($hnd);
		require("dbconfig.inc.php");
		$_POST["name"] = $_SESSION["COMPTE_BASE"];
		$_POST["pass"] = $_SESSION["PSWD_BASE"];
		$_POST["host"] = $_SESSION["SERVEUR_SQL"];
	}
	else {
		$_POST["name"] = "root";
		$_POST["pass"] = "";
		$_POST["host"] = "localhost";
	}
	$firstAttempt=true;
}*/ 

if(!function_exists('session_start')) {	
	echo "<br><center><font color=red><b>ERROR: Sessions for PHP is not properly installed.<br>Try installing the php4-session package.</b></font></center>";
	die();
}

if(!function_exists('xml_parser_create')) {	
	echo "<br><center><font color=orange><b>WARNING: XML for PHP is not properly installed, you will not be able to use ipdiscover-util.</b></font></center>";
}

if(!class_exists('MDB2')) {	
	echo "<br><center><font color=red><b>ERROR: PEAR::MDB2 class is not properly installed.<br>Try installing by running 'pear install MDB2' on the command line. Don't forget to install the database driver, too (for example 'pear install MDB2_Driver_pgsql')</b></font></center>";
	die();
}

if(!function_exists('imagefontwidth')) {	
	echo "<br><center><font color=orange><b>WARNING: GD for PHP is not properly installed.<br>You will not be able to see any graphical display<br>Try uncommenting \";extension=php_gd2.dll\" (windows) by removing the semicolon in file php.ini, or try installing the php4-gd package (Linux).</b></font></center>";
}

if(!function_exists('openssl_open')) {	
	echo "<br><center><font color=orange><b>WARNING: OpenSSL for PHP is not properly installed.<br>Some automatic deployment features won't be available<br>Try uncommenting \";extension=php_openssl.dll\" (windows) by removing the semicolon in file php.ini, or try installing the php4-openssl package (Linux).</b></font></center>";
}

@mkdir($_SERVER["DOCUMENT_ROOT"]."/download");
$pms = "post_max_size";
$umf = "upload_max_filesize";

$valTpms = ini_get( $pms );
$valTumf = ini_get( $umf );

$valBpms = return_bytes( $valTpms );
$valBumf = return_bytes( $valTumf );

if( $valBumf>$valBpms )
	$MaxAvail = $valTpms;
else
	$MaxAvail = $valTumf;

echo "<br><center><font color=orange><b>NOTICE: You will not be able to build any deployment package with size 
greater than $MaxAvail.<br>You must raise both post_max_size and upload_max_filesize in your php.ini to encrease this limit.</b></font></center>";

require ('fichierConf.class.php');

$l = new FichierConf("english");
$instOk = false;
if( isset($_POST["name"])) {
		if( (!$link=@mdb2_connect($_POST["driver"],$_POST["host"],$_POST["name"],$_POST["pass"],"ocsweb"))) {
		echo "<br><center><font color=red><b>ERROR: ".$l->g(249)." (host=".$_POST["host"]." name=".$_POST["name"].")<br>
			Error connecting to database server. Check your log files for further information.</b></font></center>";
	}
	else
		$instOk = true;
}
if( $hnd = @fopen("dbconfig.inc.php", "r") ) {
		fclose($hnd);
		require("dbconfig.inc.php");
		$valDrv = $_SESSION["MDB2_DRIVER"];
		$valNme = $_SESSION["COMPTE_BASE"];
		$valPass = $_SESSION["PSWD_BASE"];
		$valServ = $_SESSION["SERVEUR_SQL"];
} else {
		$valDrv = "";
		$valNme = "";
		$valPass = "";
		$valServ = "";
}

if( ! $instOk ) {

	echo "<br><form name='fsub' action='install.php' method='POST'><table width='100%'>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>"."MDB2 driver name"." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='driver' value='$valDrv'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(247)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='name' value='$valNme'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(248)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 type='password' name='pass' value='$valPass'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(250)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='host' value='$valServ'>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan='2' align='center'>
			<input class='bouton' name='enre' type='submit' value=".$l->g(13)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>
	
	</table></form>";
	die();
}

if($_POST["driver"] == "mysql") {
	echo "<br><center><font color=orange><b>NOTICE: If you encounter any problem with files insertion, try setting the global max_allowed_packet mysql value to at least 2M in your MySQL server config file.</font></center>";
}

if(isset($_POST["label"])) {
	
	if($_POST["label"]!="") {
		@mdb2_query( "DELETE FROM deploy WHERE NAME='label'");
		$query = "INSERT INTO deploy VALUES ('label',?)";
		mdb2_query($query, NULL, "blob", $_POST["label"]) or die(mdb2_error());
		echo "<br><center><font color=green><b>Label added</b></font></center>";
	}
	else {
		echo "<br><center><font color=green><b>Label NOT added (not tag will be asked on client launch)</b></font></center>";
	}
}

if(check_param ($_POST, "fin")=="fin") {
	// Configuration done, so try with account from config file
	if(!@mdb2_connect($_POST["driver"],$valServ,$valNme,$valPass,"ocsweb")) {
		echo "<br><center><font color=red><b>ERROR: Database authentication problem. (using host=".$_POST["host"]." login=ocs pass=ocs database=ocsweb).</b><br></font></center>";
		
		echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
		unlink("dbconfig.inc.php");
	}
	else {
		echo "<br><center><font color=green><b>Installation finished you can log in index.php with login=admin and pass=admin</b><br><br><b><a href='index.php'>Click here to enter OCS-NG GUI</a></b></font></center>";
	}	
	die();
}


if(!$ch = @fopen("dbconfig.inc.php","w")) {
	echo "<br><center><font color=red><b>ERROR: can't write in directory (on dbconfig.inc.php), please set the required rights in order to install ocsinventory (you should remove the write mode after the installation is successfull)</b></font></center>";
	die();
}

$keepuser=false;

	echo "<br><center><font color=black><b>Please wait, database update may take up to 30 minutes...";
	flush();

require_once 'MDB2/Schema.php';

function schema_errorCallback ($err) {
	unlink("dbconfig.inc.php");
	print "<p>ERROR: Could not update the Database.</p>\n<p>MDB2_Schema error was: ";
	print htmlspecialchars ($err->getMessage());
	print "</p>\n<p>";
	print htmlspecialchars ($err->getUserInfo());
	print "</p>";
	die();
}

$schema =& MDB2_Schema::factory($link, array ('force_defaults' => false));
if (PEAR::isError($schema)) {
	unlink("dbconfig.inc.php");
	die ("<p>ERROR: Could not create MDB2_Schema Object</p>");
}
$schema->setErrorHandling (PEAR_ERROR_CALLBACK, 'schema_errorCallback');

$previous_schema = $schema->getDefinitionFromDatabase();

// The schema XML is split into multiple files which MDB2_Schema cannot handle.
// We have to concatenate them into a single file first.
$temp = tmpfile();
if (!$temp)
	die ("Could not create temporary file");

if (!fwrite ($temp, <<<END
<?xml version="1.0" encoding="UTF-8" ?>
<database>
 <name>ocsweb</name>
 <create>true</create>
 <overwrite>false</overwrite>

END
))
	die ("Error writing to temporary file");

$dir = opendir ("../../schema");
if (!$dir)
	die ("Can't get a handle for schema dir");

while (($file = readdir ($dir)) !== false ) {
	if (substr($file, -4) != '.xml')
		continue;
	$content = file_get_contents ("schema/$file");
	if ($content == false)
		die ("Error reading $file");
	if (!fwrite ($temp, $content))
		die ("Error writing to temporary file");
}

if (!fwrite ($temp, "</database>\n"))
	die ("Error writing to temporary file");

$meta = stream_get_meta_data ($temp);
$new_schema = $schema->parseDatabaseDefinitionFile($meta["uri"]);
fclose ($temp);

// The accountinfo table has a dynamic structure.
// Only the static part is defined in the XML file.
// The additional fields have to be preserved here.
if (array_key_exists('accountinfo', $previous_schema['tables']))
	$new_schema['tables']['accountinfo']['fields'] = array_merge ($previous_schema['tables']['accountinfo']['fields'], $new_schema['tables']['accountinfo']['fields']);

if ($schema->db->getOption ('use_transactions'))
        $schema->db->beginNestedTransaction();

$schema->updateDatabase($new_schema, $previous_schema);

// Table initialization is only done when the table is created for the first time.
// In case of an upgrade we have to update the table content manually.
foreach ($new_schema['tables'] as $name => $table) {
	if (!array_key_exists ($name, $previous_schema['tables']) or empty ($table['initialization']))
		continue;

	// We have to avoid duplicate entries that would violate primary keys or unique constraints.
	// Therefore we have to find a way to identify existing rows and determine whether the
	// row to be inserted would violate a constraint.
	// This is far from perfect yet because we will only detect single-column primary keys
	// and single-column unique constraints. Furthermore we assume only simple inserts.
	// Complex inserts like insert/select will produce unpredictable results!
	$constraints = array();
	foreach ($schema->db->reverse->tableInfo($name) as $column) {
		if (strpos ($column['flags'], 'primary_key') !== false or strpos ($column['flags'], 'unique_key') !== false)
			$constraints[$column['name']] = $column['mdb2type'];
	}
	if (empty ($constraints)) {
		// Without any constraint we would produce more and more entries on every upgrade. Abort.
		die ("<p>FATAL: Cannot reliably initialize table $name because the table has no constraints.</p>");
	}

	$skip_rows = array();
	foreach ($table['initialization'] as $command_index => $command) {
		if ($command['type'] != 'insert')
			continue;

		// Build the list of fields we have to check for existent values
		$fieldlist = array();
		foreach ($command['data']['field'] as $field) {
			if (array_key_exists ($field['name'], $constraints)) {
				$fieldlist[$field['name']] = $field['group']['data'];
		    }
		}

		$count = count ($fieldlist);
		if ($count == 0) {
			die ("<p>FATAL: Cannot reliably initialize table $name with unconstrained data.</p>");
		}
		// Check for existing rows that would prevent successful insertion.
		$query = "SELECT COUNT(*) FROM " . $schema->db->quoteIdentifier ($name) . " WHERE ";
		$i = 1;
		foreach ($fieldlist as $fieldname => $value) {
			$query .= $schema->db->quoteIdentifier ("$fieldname") . "=" . $schema->db->quote($value, $constraints[$fieldname]);
			if ($i < $count)
				$query .= " OR ";
			$i++;
		}
		$result = $schema->db->query ($query);

		if ($result->fetchOne()) {
			// Found existing row.
			$skip_rows[] = $command_index;
		}
	}
	// Sort results in reverse order to prevent index shifting while removing them.
	rsort ($skip_rows, SORT_NUMERIC);
	foreach ($skip_rows as $index) {
		unset ($table['initialization'][$index]);
	}
	$schema->initializeTable ($name, $table);
}

if ($schema->db->getOption ('use_transactions'))
        $schema->db->completeNestedTransaction();

$schema->disconnect();

echo "<br><center><font color=green><b>Database successfully generated/updated</b></font></center>";

fwrite($ch,
"<?php\n" .
'$_SESSION["MDB2_DRIVER"] = "' . addslashes ($_POST["driver"]) . "\";\n" .
'$_SESSION["SERVEUR_SQL"] = "' . addslashes ($_POST["host"]) . "\";\n" .
'$_SESSION["COMPTE_BASE"] = "' . addslashes ($_POST["name"]) . "\";\n" .
'$_SESSION["PSWD_BASE"] = "' . addslashes ($_POST["pass"]) . "\";\n" .
"?>");
fclose($ch);
echo "<br><center><font color=green><b>Database config file successfully written.</b></font></center>";

// reconnect to the database
if( (!$link=@mdb2_connect($_POST["driver"],$_POST["host"],$_POST["name"],$_POST["pass"],"ocsweb"))) {
	echo "<br><center><font color=red><b>ERROR: " . $l->g(249) . " (host=".$_POST["host"]." name=".$_POST["name"].")<br>
		Error connecting to database server. Check your log files for further information.</b></font></center>";
}

$nberr=0;

if ($_POST["driver"] == "mysql") {
echo "<br><center><font color=black><b>Database engine checking...";
flush();
//TODO: dernieres tables
$tableEngines = array("hardware"=>"InnoDB","accesslog"=>"InnoDB","bios"=>"InnoDB","memories"=>"InnoDB","slots"=>"InnoDB",
"controllers"=>"InnoDB","download_available"=>"InnoDB","download_enable"=>"InnoDB","download_history"=>"InnoDB",
"engine_mutex"=>"MEMORY","prolog_conntrack"=>"MEMORY",
"registry"=>"InnoDB","monitors"=>"InnoDB","ports"=>"InnoDB","storages"=>"InnoDB","drives"=>"InnoDB","inputs"=>"InnoDB",
"modems"=>"InnoDB","networks"=>"InnoDB","printers"=>"InnoDB","sounds"=>"InnoDB","videos"=>"InnoDB","softwares"=>"InnoDB",
"accountinfo"=>"InnoDB","netmap"=>"InnoDB","devices"=>"InnoDB", "locks"=>"HEAP", "conntrack"=>"HEAP");

$mysql = $link->getConnection();

$nbconv = 0;
$erralter = false;
foreach( $tableEngines as $tbl=>$eng ) {
	if( $res = mysql_query("show table status like '$tbl'", $mysql) ) {
		$val = mysql_fetch_array( $res );
		if( $val["Engine"] == $eng ) {
			echo ".";
			flush();
		}
		else {
			$nbconv++;
			echo ".";
			flush();
			if( ! $resAlter = mysql_query("ALTER TABLE $tbl engine='$eng'", $mysql) ) {
				$nberr++;
				$erralter = true;
				echo "</b></font></center><br><center><font color=red><b>ERROR: Alter query failed</b><br>";
				echo "<b>mysql error: ".mysql_error($mysql)." (err:".mysql_errno($mysql).")</b></font></center>";
			}
		}
	}
	else {
		echo "</b></font></center><br><center><font color=red><b>ERROR: Show table status query failed</b><br>";
		echo "<b>mysql error: ".mysql_error($mysql)." (err:".mysql_errno($mysql).")</b></font></center>";
		$nberr++;
		$erralter = true;
	}
}
$oneInnoFailed = false;
$oneHeapFailed = false;
foreach( $tableEngines as $tbl=>$eng ) {
	if( $res = mysql_query("show table status like '$tbl'", $mysql) ) {
		$val = mysql_fetch_array( $res );
		if( (strcasecmp($val["Engine"],$eng) != 0) && (strcasecmp($eng,"InnoDB") == 0) && $oneInnoFailed == false ) {
			echo "<br><br><center><font color=red><b>ERROR: InnoDB conversion failed, install InnoDB  mysql engine support on your server<br>or you will experience severe performance issues.<br>
			(Try to uncomment \"#skip-innodb\" in your mysql config file.)<br>Reinstall when corrected.</b></font><br>";
			$oneInnoFailed = true;
		}
		if ( (strcasecmp($val["Engine"],$eng)!=0) && (strcasecmp($eng,"HEAP")) && (strcasecmp($val["Engine"],"MEMORY")!=0) && $oneHeapFailed == false  ) {
			echo "<br><br><center><font color=red><b>ERROR: HEAP conversion failed, install HEAP mysql engine support on your server<br>or you will experience severe performance issues.</b></font><br>";
			$oneHeapFailed = true;
		}
	}
	else {
		echo "</b></font></center><br><center><font color=red><b>ERROR: Show table status query failed</b><br>";
		echo "<b>mysql error: ".mysql_error($mysql)." (err:".mysql_errno($mysql).")</b></font></center>";
		$nberr++;
		$erralter = true;
	}
}

if( ! $erralter ) {
	echo "</b></font></center><br><center><font color=green><b>Database engine successfully updated ($nbconv table(s) altered)</b></font></center>";
}
}

if($nberr) {
	echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
	unlink("dbconfig.inc.php");
	die();
}
$nberr=0;
$dir = "files";
$filenames = Array("ocsagent.exe");
$dejaLance=0;
$filMin = "";

mdb2_query("DELETE FROM deploy");
foreach($filenames as $fil) {
	$filMin = $fil;
	if ( $ledir = @opendir("files")) {
		while($filename = readdir($ledir)) {
			if(strcasecmp($filename,$fil)==0 && strcmp($filename,$fil)!=0  ) {
				//echo "<br><center><font color=green><b>$fil case is '$filename'</b></font></center>";
				$fil = $filename;
			}
		}
		closedir($ledir);
	}
	else {
		echo "<br><center><font color=orange><b>WARNING: 'files' directory missing, can't import $fil from it</b></font></center>";
	}
	
	if($fd = @fopen($dir."/".$fil, "r")) {
		$contents = fread($fd, filesize ($dir."/".$fil));
		fclose($fd);	
		$query = "INSERT INTO deploy (name, content) VALUES(?,?)";
		
		if(!mdb2_query($query, NULL, array("text", "blob"), array ($filMin, $contents))) {
			if(mdb2_errno()==MDB2_ERROR_ALREADY_EXISTS || mdb2_errno()==MDB2_ERROR_CONSTRAINT) {
					$dejaLance++;
					continue;
			}
			if($_POST["driver"] == "mysql" && mysql_errno($mysql)==2006) {
				echo "<br><center><font color=red><b>ERROR: $fil was not inserted. You need to set the max_allowed_packet mysql value to at least 2M</b></font></center>";
				echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
				unlink("dbconfig.inc.php");
				die();
			} 
			echo "<br><center><font color=red><b>ERROR: $fil not inserted</b><br>";
			echo "<b>Database error: " .mdb2_error()."</b></font></center>";
			$nberr++;
		}
	}
	else {
		echo "<br><center><font color=orange><b>WARNING: ".$dir."/".$fil." missing, if you do not reinstall the DEPLOY feature won't be available</b></font></center>";
		$errNorm = true;
	}
}

if($dejaLance>0)	
	echo "<br><center><font color=orange><b>WARNING: One or more files were already inserted</b></font></center>";

if(!$nberr&&!$dejaLance&&!$errNorm)
	echo "<br><center><font color=green><b>Deploy files successfully inserted</b></font></center>";

mdb2_query("DELETE FROM files");
$nbDeleted = mdb2_affected_rows();
if( $nbDeleted > 0)
	echo "<br><center><font color=green><b>Table 'files' truncated</b></font></center>";
else
	echo "<br><center><font color=green><b>Table 'files' was empty</b></font></center>";

if($nberr) {
	echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
	unlink("dbconfig.inc.php");
	die();
}

$row = 1;
$handle = @fopen("subnet.csv", "r");

if( ! $handle ) {
	echo "<br><center><font color=green><b>No subnet.csv file to import</b></font></center>";
}
else {
	$errSub = 0;
	$resSub = 0;
	$dejSub = 0;
	echo "<hr><br><center><font color=green><b>Inserting subnet.csv networks</b></font></center>";
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
	
		$ipValide = "(([0-9]{1,3}\.){3}[0-9]{1,3})";
		$masqueEntier = "([0-9]{1,3})";
		$masqueValide = "(($ipValide|$masqueEntier)[ ]*$)";
		$exp = $ipValide."[ ]*/[ ]*".$masqueValide;

		if( preg_match(":$exp:",$data[2],$res) ) {
			
			if( @mdb2_query("INSERT INTO subnet(netid, name, id, mask)
			VALUES (?,?,?,?)", array ("text", "text", "integer", "text"), array ($res[1], $data[0], $data[1], $res[4])) ) {
				$resSub++;
				//echo "<br><center><font color=green><b>
				//Network => name: ".$data[0]." ip: ".$res[1]." mask: ".$res[4]." id: ".$data[1]." successfully inserted</b></font></center>";
			}
			else {
				if( mdb2_errno() != MDB2_ERROR_ALREADY_EXISTS && mdb2_errno() != MDB2_ERROR_CONSTRAINT) {
					$errSub++;
					echo "<br><center><font color=red><b>ERROR: Could not insert network ".$data[0]." in the subnet table, error ".mdb2_errno().": ".mdb2_error()."</b></font></center>";
				}
				else
					$dejSub++;
			}
		}
		else {
			$errSub++;
			echo "<br><center><font color=orange><b>WARNING: Network ".$data[0]." was not inserted (invalid ip or mask: ".$data[2].")</b></font></center>";
		}
	}
	fclose($handle);
	echo "<br><center><font color=green><b>Subnet was imported=> $resSub successful, <font color=orange>$dejSub were already imported</font>, <font color=red>$errSub failed</font></b></font></center><hr>";
	
}


echo "<br><center><font color=green><b>Network netid computing. Please wait...</b></font></center>";
flush();

$reqDej = "SELECT COUNT(id) as nbid FROM networks WHERE ipsubnet IS NOT NULL";
$resDej = mdb2_query($reqDej) or die(mdb2_error());
$valDej = mdb2_fetch_assoc($resDej);
$errNet = 0;
$sucNet = 0;
$dejNet = $valDej["nbid"];

$reqNet = "SELECT hardware_id, id, ipaddress, ipmask FROM networks WHERE ipsubnet='' OR ipsubnet IS NULL";
$resNet = mdb2_query($reqNet) or die(mdb2_error());
while ($valNet = mdb2_fetch_assoc($resNet) ) {
	$netid = getNetFromIpMask( $valNet["ipaddress"], $valNet["ipmask"] );
	if( !$netid || $valNet["ipaddress"]=="" || $valNet["ipmask"]=="" ) {
		$errNet++;
	}
	else {
		mdb2_query("UPDATE networks SET ipsubnet=? WHERE hardware_id=? AND id=?", array("text", "integer", "integer"), array ($netid, $valNet["hardware_id"], $valNet["id"]));
		if( mdb2_errno() != MDB2_OK) {
			$errNet++;
			echo "<br><center><font color=red><b>ERROR: Could not update netid to $netid, error ".mdb2_errno().": ".mdb2_error()."</b></font></center>";
		}
		else {
			$sucNet++;
		}
	}	
}
echo "<br><center><font color=green><b>Network netid was computed=> $sucNet successful, <font color=orange>$dejNet were already computed</font>, <font color=red>$errNet were not computable</font></b></font></center>";

echo "<br><center><font color=green><b>Netmap netid computing. Please wait...</b></font></center>";
flush();

$reqDej = "SELECT COUNT(mac) as nbid FROM netmap WHERE netid IS NOT NULL";
$resDej = mdb2_query($reqDej) or die(mdb2_error());
$valDej = mdb2_fetch_assoc($resDej);
$errNet = 0;
$sucNet = 0;
$dejNet = $valDej["nbid"];

$reqNet = "SELECT mac, ip, mask FROM netmap WHERE netid='' OR netid IS NULL";
$resNet = mdb2_query($reqNet) or die(mdb2_error());
while ($valNet = mdb2_fetch_assoc($resNet) ) {
	$netid = getNetFromIpMask( $valNet["ip"], $valNet["mask"] );
	if( !$netid || $valNet["ip"]=="" || $valNet["mask"]=="" ) {
		$errNet++;
	}
	else {
		mdb2_query("UPDATE netmap SET netid=?, date=CURRENT_TIMESTAMP WHERE mac=? AND ip=?", array ("text", "text", "text"), array ($netid, strtoupper ($valNet["mac"]), $valNet["ip"]));
		if( mdb2_errno() != MDB2_OK) {
			$errNet++;
			echo "<br><center><font color=red><b>ERROR: Could not update netid to $netid, error ".mdb2_errno().": ".mdb2_error()."</b></font></center>";
		}
		else {
			$sucNet++;
		}
	}	
}
echo "<br><center><font color=green><b>Netmap netid was computed=> $sucNet successful, <font color=orange>$dejNet were already computed</font>, <font color=red>$errNet were not computable</font></b></font></center>";

//ORPH	
echo "<br><center><font color=green><b>Cleaning orphans...";
flush();
//TODO: orphelins dans nouvelle tables
$tables=Array("accountinfo","bios","controllers","drives",
	"inputs","memories","modems","monitors","networks","ports","printers","registry",
	"slots","softwares","sounds","storages","videos","devices");
$cleanedNbr = 0;

foreach( $tables as $laTable) {
		
	$reqSupp = "DELETE FROM $laTable WHERE hardware_id NOT IN (SELECT DISTINCT(id) FROM hardware)";
	$resSupp = @mdb2_query( $reqSupp );
	if( mdb2_errno() != MDB2_OK) {
		echo "</b></font></center><br><center><font color=red><b>ERROR: Could not clean $laTable, error ".mdb2_errno().": ".mdb2_error()."</b></font></center>";
	}
	else {
		if( $cleaned = mdb2_affected_rows() )
			$cleanedNbr += $cleaned;			
	}
	echo ".";
}	
echo "</b></font></center><br><center><font color=green><b>$cleanedNbr orphan lines deleted</b></font></center>";
flush();

//NETMAP
echo "<br><center><font color=green><b>Cleaning netmap...";
flush();
$cleanedNbr = 0;
		
$reqSupp = "DELETE FROM netmap WHERE netid NOT IN(SELECT DISTINCT(ipsubnet) FROM networks)";
$resSupp = @mdb2_query( $reqSupp );
if( mdb2_errno() != MDB2_OK) {
	echo "</b></font></center><br><center><font color=red><b>ERROR: Could not clean netmap, error ".mdb2_errno().": ".mdb2_error()."</b></font></center>";
}
else {
	if( $cleaned = mdb2_affected_rows() )
		$cleanedNbr += $cleaned;			
}

echo "</b></font></center><br><center><font color=green><b>$cleanedNbr netmap lines deleted</b></font></center>";
flush();
/*
echo "<br><center><font color=green><b>Building software cache. Please wait...</b></font></center>";
flush();
mysql_query("TRUNCATE TABLE softwares_name_cache") or die(mysql_error());
mysql_query("INSERT INTO softwares_name_cache(name) SELECT DISTINCT name FROM softwares") or die(mysql_error());

echo "<br><center><font color=green><b>Building registry cache. Please wait...</b></font></center>";
flush();
mysql_query("TRUNCATE TABLE registry_regvalue_cache") or die(mysql_error());
mysql_query("INSERT INTO registry_regvalue_cache(regvalue) SELECT DISTINCT regvalue FROM registry") or die(mysql_error());
*/
function printEnTeteInstall($ent) {
	echo "<br><table border=1 class= \"Fenetre\" WIDTH = '62%' ALIGN = 'Center' CELLPADDING='5'>
	<th height=40px class=\"Fenetre\" colspan=2><b>".$ent."</b></th></table>";
}

?><br>
<center>
<form name='taginput' action='install.php' method='post'><b>
<font color='black'>Please enter the label of the windows client tag input box:<br>
(Leave empty if you don't want a popup to be shown on each agent launch).</font></b><br><br>
	<input name='label' size='40'>
	<input type='hidden' name='fin' value='fin'>
	<input type='hidden' name='driver' value='<?php echo $_POST["driver"];?>'>
	<input type='hidden' name='name' value='<?php echo $_POST["name"];?>'>
	<input type='hidden' name='pass' value='<?php echo $_POST["pass"];?>'>
	<input type='hidden' name='host' value='<?php echo $_POST["host"];?>'>
	<input type=submit>
	
</form></center>
<?php 

function getNetFromIpMask($ip, $mask) {	
	return ( long2ip(ip2long($ip)&ip2long($mask)) ); 
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        // Le modifieur 'G' est disponible depuis PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

?>






