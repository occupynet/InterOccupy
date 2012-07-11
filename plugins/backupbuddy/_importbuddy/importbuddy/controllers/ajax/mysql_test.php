<?php
$can_connect = false;
$connect_error = 'N/A';
$can_select = false;
$select_error = 'N/A';
$wordpress_exists = false;
$failure_encountered = false;

if ( false === @mysql_connect( $_POST['server'], $_POST['user'], $_POST['pass'] ) ) { // Couldnt connect to server or invalid credentials.
	$connect_error = mysql_error();
} else {
	$can_connect = true;
	
	if ( false === @mysql_select_db( $_POST['name'] ) ) { // 
		$can_select = false;
		$select_error = mysql_error();
	} else {
		$can_select = true;
		
		// Check number of tables already existing with this prefix.
		$result = mysql_query( "SHOW TABLES LIKE '" . mysql_real_escape_string( str_replace( '_', '\_', $_POST['prefix'] ) ) . "%'" );
		if ( mysql_num_rows( $result ) > 0 ) {
			$wordpress_exists = true;
		} else {
			$wordpress_exists = false;
		}
		unset( $result );
	}
}


// CAN CONNECT
echo '1. Logging in to server ... ';
if ( $can_connect === true ) {
	echo 'Success<br>';
} else {
	echo '<font color=red>Failed</font><br>';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;Error: ' . $connect_error . '<br>';
	$failure_encountered = true;
}


// CAN ACCESS DATABASE BY NAME
echo '2. Verifying database access & permission … ';
if ( $can_select === true ) {
	echo 'Success<br>';
} else {
	echo '<font color=red>Failed</font><br>';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;Error: ' . $select_error . '<br>';
	$failure_encountered = true;
}


// DOES WORDPRESS EXIST?
echo '3. Verifying no existing WP data ... ';
if ( $failure_encountered === true ) {
	echo 'N/A<br>';
} else {
	if ( $wordpress_exists !== true ) { // No existing WordPress.
		echo 'Success<br>';
	} else { // WordPress exists.
		if ( $_POST['wipe_database'] == '1' ) { // Option to wipe JUST MATCHING THIS PREFIX enabled.
			echo '<font color=red>Warning</font><br>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;WordPress already exists in this database with this prefix.<br>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;Tables with matching prefix will be wiped prior to import. Use caution.<br>';
		} else { // Not wiping. We have an error.
			if ( pb_backupbuddy::$options['ignore_sql_errors'] != false ) {
				echo '<font color=red>Warning</font><br>';
				echo '&nbsp;&nbsp;&nbsp;&nbsp;Option set to ignore existing tables. Use caution.<br>';
			} else {
				if ( $_POST['wipe_database_all'] == '1' ) { // Option to wipe ALL TABLES enabled.
					echo '&nbsp;&nbsp;&nbsp;&nbsp;Warning: WordPress already exists in this database with this prefix.<br>';
				} else {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;Error: WordPress already exists in this database with this prefix.<br>';
					$failure_encountered = true;
				}
			}
			
		}
	}
}


if ( $_POST['wipe_database_all'] == '1' ) { // Option to wipe ALL TABLES enabled.
	echo '<font color=red>Warning</font>';
	echo ' - <b>ALL TABLES</b> (based on settings) will be wiped prior to import. Use caution.<br>';
}


// Prefix only has allowed chars check.
if ( !preg_match('/^[a-z0-9_]+$/i', $_POST['prefix'] ) ) {
	echo '<font color=red>Warning: Prefix contains characters that are not allowed.</font><br>';
	$failure_encountered = true;
}


// OVERALL RESULT
echo '4. Overall mySQL test result ... ';
if ( $failure_encountered !== true ) {
	echo 'Success';
} else {
	echo '<font color=red>Failed</font><br>';
}




die();
?>