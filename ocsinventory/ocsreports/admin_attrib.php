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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.4 $)
$sadmin_profil=1;
include('security.php');

require_once ('require/function_mdb2.php');

if( isset( $_GET["newtag"] ) ) {
	$tbi = $_GET["newtag"] ;
	@mdb2_query( "INSERT INTO tags(tag,login) VALUES(?,?)", $_SESSION["writeServer"], NULL, array ($tbi, $_GET["user"]) );
}

if( isset( $_GET["supptag"] ) ) {
	$tbd = $_GET["supptag"];
	@mdb2_query( "DELETE FROM tags WHERE tag=? AND login=?", $_SESSION["writeServer"], NULL, array ($tbd, $_GET["user"]) );
}

printEnTete($l->g(616)." ".$_GET["user"] );

$reqTags = "SELECT tag FROM tags WHERE login=? ORDER BY tag";
$resTags = mdb2_query( $reqTags, $_SESSION["readServer"], NULL, $_GET["user"] );

echo "<form name='newtaguser' action='index.php' method='GET'>";
echo "<br><table BORDER='0' WIDTH = '50%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
echo "<tr><td align='center'><FONT FACE='tahoma' SIZE='2'><b>".TAG_LBL."</b></font></td><td>&nbsp;</td></tr>";		

$x=0;
while( $valTags = mdb2_fetch_assoc( $resTags ) ) {
	$x++;
	echo "<TR height=20px bgcolor='". ($x%2==0 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne			
	echo "<td align=center><FONT FACE='tahoma' SIZE=2>"
	.$valTags["tag"]."</font></td><td align='center' width='25px'><a href='index.php?multi=31&user=".$_GET["user"]."&supptag=".urlencode($valTags["tag"])."'><img src='image/supp.png'></a></td></tr>";
}
echo "<TR height=30px bgcolor='#FFFFFF'>";	
echo "<td align='right' colspan='2'><FONT FACE='tahoma' SIZE=2>";
echo $l->g(617)." ".TAG_LBL.": <input type='text' id='newtag' name='newtag'><input type='submit'>";
echo "<input type='hidden' name='user' id='user' value='".$_GET["user"]."'>";
echo "<input type='hidden' name='multi' id='multi' value='31'>";
echo "</td></tr></table></form>";
?>
