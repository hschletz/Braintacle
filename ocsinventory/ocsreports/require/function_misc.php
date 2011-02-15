<?php
//====================================================================================
// Helper functions
// Copyleft Holger Schletz 2009
// mailto: hschletz@users.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================


// case-insensitive replacement for array_search()
// needed because array_search() may no longer work due to MDB2 case conversion of field names.
function array_case_search($needle, $haystack)
{
        foreach($haystack as $key=>$val){
		if(strcasecmp ($val, $needle) == 0)
    	    		return $key;
	}
	return false;
}


// check parameter $array[$name] and return either $array[$name] or NULL if it does not exist.
// Optionally perform sanity checks based on regular expressions (NULL is treated like an empty string):
// If $re_required is given, the value must match.
// If $re_forbidden is given, the value must not match.
// $case_sensitive (default: true) determines whether these checks should be case sensitive.
// If the value does not pass these checks, an error message is logged and the script dies. 
function check_param ($array, $name, $re_required=NULL, $re_forbidden=NULL, $case_sensitive = true)
{
	if (array_key_exists ($name, $array)) {
		$value = $array[$name];
	} else {
		$value = NULL;
	}
	if ($case_sensitive) {
		$ereg = "ereg";
	} else {
		$ereg = "eregi";
	}
	if ($re_required != NULL and !$ereg ($re_required, $value)) {
		error_log ("Invalid parameter '$name': does not match regular expression '$re_required', value is: $value");
		die ("Invalid input. See Apache logs for details.");
	}
	if ($re_forbidden != NULL and $ereg ($re_forbidden, $value)) {
		error_log ("Invalid parameter '$name': matches regular expression '$re_forbidden', value is: $value");
		die ("Invalid input. See Apache logs for details.");
	}
	return $value;
}
?>
