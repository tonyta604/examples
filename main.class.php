<?php

	class SpocPbxHelper {
		
		public function __construct() {
		}

		public function __call($method, $args) {
			echo "unknown method " . $method;
			return false;
		}
		
		public function get_new_line(){
			return "\r\n";			
		}

		public function write_conf_file($filename, $content, $filepath=FILE_PATH) {

			// Write to file
			$filename_path = $filepath.$filename;
			$return_result = "";

			$file_parts = pathinfo($filename);
			switch($file_parts['extension']){
				case "xml":
					$content = $this->auto_generate_file_text('xml').$content;
					break;
				case "sh":
					$content = $this->auto_generate_file_text('sh').$content;
					break;
				default:
					$content = $this->auto_generate_file_text().$content;
					break;
			}
			
			try {
				//if (file_exists($filename)) {
				$fh = fopen($filename_path, "w");
				if (!$fh) {
					$return_result = "<strong>Permission denied.</strong> Could not write to file ".$filename_path;
				} else {
					fwrite($fh, $content);
					fclose($fh);
				}

			} catch (Exception $e) {
				$return_result = $e;
			}
		
			return $return_result;
		}

		public function auto_generate_file_text($filetype='conf') {
			$newline = "\r\n";
			$text = '';
			$content = '; This is an auto generated file';

			switch($filetype){
				case 'xml':
					$text .= "<!--".$newline;
					for($i=0;$i < 5; $i++) {			
						$text .= $content.$newline;
					}	
					$text .= "-->".$newline;
					break;
				case 'sh':
					for($i=0;$i < 5; $i++) {			
						$text .= '#'.$content.$newline;
					}	
					break;
				default:
					for($i=0;$i < 5; $i++) {			
						$text .= $content.$newline;
					}
					break;

			}
			
			$text .= $newline;					
			return $text;
		}

		public function truncate($string,$length=15,$append="&hellip;") {
		  $string = trim($string);
		  
		  if(strlen($string) > $length) {
		    //$string = wordwrap($string, $length);
		    //$string = explode("\n", $string, 2);
		    //$string = $string[0] . $append;
		    $string = substr($string, 0, $length).$append;
		  }
		  return $string;
		}

		public function display_sip_users_sidebar($users) {
			
			$text = "";
			if (is_array($users)) {
				$text .= '<div id="sidebar_menu"><div id="sidebar_menu_inner" class="open">';
				$text .= '<div class="sidebar_menu_title"><h3><a href="/users.php" style="color:#fff;" title="Home"><i class="fa fa-home"></i></a>&nbsp;&nbsp;<i class="fa fa-angle-right"></i>&nbsp;&nbsp;Sip Users</h3></div>';
				$text .= '<table class="table table-condensed" style="margin:0;" id="sip_users_table"><thead><tr><th>Username</th><th>Ext</th></tr></thead><tbody>';
						
				foreach ($users as $k => $v) {														
					$username = $users[$k]->sip_username;
					$extension = $users[$k]->sip_mailbox;					
					$id =  $users[$k]->idsip_users;
					$username = $this->truncate($username);
					$text .= "<tr>";
					$text .= "<td class=\"td-username\" data-username=\"{$username}\"><a href=\"../create_sip_user.php?id=".$id."\">".$username."</a></td>";
					$text .= "<td class=\"td-extension\" data-extension=\"{$extension}\">".$extension."</td>";
					$text .= "</tr>";

				}
						
				$text .= '</tbody></table></div><div id="sidebar_menu_button"><a href="#" class="open"><i class="fa fa-times switch-icon"></i></a></div></div>';
			}

			return $text;
		}

	}

	class SpocPbx extends SpocPbxHelper
	{
		protected $id;
		protected $lastInsertId;
		protected $mysqli;
		protected $debug;
		
		function __construct() {		
			parent::__construct();
			$this->SpocPbx = $this->SpocPbxHelper;		
		}
		
		public function __get($property)
		{
			switch ($property)
			{
				case 'id':
					return $this->id;
				case 'lastInsertId':
					return $this->lastInsertId;									
				case 'debug':
					return $this->debug;
				case 'mysqli':
					return $this->mysqli;
			}
		}

		public function __set($property, $value)
		{
			switch ($property)
			{
				case 'id':
					$this->id = $value;
					break;
				case 'lastInsertId':
					$this->lastInsertId = $value;
					break;
				case 'debug':
					$this->debug = $value;
					break;
				case 'mysqli':
					$this->mysqli = $value;
					break;
				//etc.
			}
		}

		public function __call($method, $args) {
			echo "unknown method " . $method;
			return false;
		}

		
		private function ref_values($array) {
			$refs = array();
		
			foreach ($array as $key => $value) {
				$refs[$key] = &$array[$key];
			}
		
			return $refs;
		}
		
		public function query($query) {
			
			$results = "";
			
			$result = $this->mysqli->query($query);
				
			while ( $row = $result->fetch_object() ) {
				$results[] = $row;
			}
				
			return $results;
		}
		
		public function load($query) {
				
			$result = $this->mysqli->query($query);
		
			if ($result->num_rows > 0) {
				return true;
			}
						
			return false;
		}
		
		
		public function select($query, $data, $format) {
				
			$results = array();
			//Prepare our query for binding
			$stmt = $this->mysqli->prepare($query);
			
			//Normalize format
			$format = implode('', $format);
			$format = str_replace('%', '', $format);
				
			// Prepend $format onto $values
			array_unshift($data, $format);
				
			//Dynamically bind values
			call_user_func_array( array( $stmt, 'bind_param'), $this->ref_values($data));
			
			//Execute the query
			$stmt->execute();
				
			//Fetch results
			$result = $stmt->get_result();
				
			//Create results object
			while ($row = $result->fetch_object()) {
				$results[] = $row;
			}
		
			return $results;
		}
		
		
		public function update($table, $data, $format, $where, $where_format) {
			// Check for $table or $data not set
			if ( empty( $table ) || empty( $data ) ) {
				return false;
			}
				
			// Connect to the database
			$db = $this->mysqli;
				
			// Cast $data and $format to arrays
			$data = (array) $data;
			$format = (array) $format;
				
			// Build format array
			$format = implode('', $format);
			$format = str_replace('%', '', $format);
			$where_format = implode('', $where_format);
			$where_format = str_replace('%', '', $where_format);
			$format .= $where_format;
				
			list( $fields, $placeholders, $values ) = $this->prep_query($data, 'update');
				
			//Format where clause
			$where_clause = '';
			$where_values = '';
			$count = 0;
				
			foreach ( $where as $field => $value ) {
				if ( $count > 0 ) {
					$where_clause .= ' AND ';
				}
		
				$where_clause .= $field . '=?';
				$where_values[] = $value;
		
				$count++;
			}
		
			// Prepend $format onto $values
			array_unshift($values, $format);
			$values = array_merge($values, $where_values);
		
			// Prepary our query for binding
			$stmt = $db->prepare("UPDATE {$table} SET {$placeholders} WHERE {$where_clause}");
				
			// Dynamically bind values
			call_user_func_array( array( $stmt, 'bind_param'), $this->ref_values($values));
				
			// Execute the query
			$stmt->execute();
				
			// Check for successful insertion
			if ( $stmt->affected_rows ) {
				return true;
			}
				
			return false;
		}
		
	
		public function insert($table, $data, $format) {
			// Check for $table or $data not set
			if ( empty( $table ) || empty( $data ) ) {
				return false;
			}
				
			// Connect to the database
			$db = $this->mysqli;
				
			// Cast $data and $format to arrays
			$data = (array) $data;
			$format = (array) $format;
				
			// Build format string
			$format = implode('', $format);
			$format = str_replace('%', '', $format);
				
			list( $fields, $placeholders, $values ) = $this->prep_query($data);
				
			// Prepend $format onto $values
			array_unshift($values, $format);
		
			// Prepary our query for binding
			$stmt = $db->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
		
			// Dynamically bind values
			call_user_func_array( array( $stmt, 'bind_param'), $this->ref_values($values));
				
			// Execute the query
			$stmt->execute();
			
			// Check for successful insertion
			if ( $stmt->affected_rows ) {				
				$this->lastInsertId = $stmt->insert_id;
				return true;
			}
				
			return false;
		}
		
		public function delete($table, $field = "ID", $id) {
			// Connect to the database
			$db = $this->mysqli;
				
			// Prepary our query for binding
			$stmt = $db->prepare("DELETE FROM {$table} WHERE {$field} = ?");
				
			// Dynamically bind values
			$stmt->bind_param('d', $id);
			
			// Execute the query
			$stmt->execute();
											
			// Check for successful insertion
			if ($stmt->affected_rows > 0 ) {
				return true;
			}
			
		}

		public function delete_custom($table, $where) {
			// Connect to the database
			$db = $this->mysqli;

			// Prepary our query for binding
			$stmt = $db->prepare("DELETE FROM {$table} WHERE {$where}");
				
			// Dynamically bind values
			//$stmt->bind_param('d', $id);
			
			// Execute the query
			$stmt->execute();
											
			// Check for successful insertion
			if ($stmt->affected_rows > 0 ) {
				return true;
			}
			
		}

		
		private function prep_query($data, $type='insert') {
			// Instantiate $fields and $placeholders for looping
			$fields = '';
			$placeholders = '';
			$values = array();
				
			// Loop through $data and build $fields, $placeholders, and $values
			foreach ( $data as $field => $value ) {
				$fields .= "{$field},";
				$values[] = $value;
		
				if ( $type == 'update') {
					$placeholders .= $field . '=?,';
				} else {
					$placeholders .= '?,';
				}
		
			}
				
			// Normalize $fields and $placeholders for inserting
			$fields = substr($fields, 0, -1);
			$placeholders = substr($placeholders, 0, -1);
				
			return array( $fields, $placeholders, $values );
		}


		public function get_global_extention_length() {
			$extension_length_arr = $this->query('SELECT value FROM spocpbx_system_settings WHERE name IN ("extension_length") AND active = 1');
			return $extension_length_arr[0]->value;
		}
	}

	class Ldap extends SpocPbx {

		protected $filename = "";

		function __construct() {		
			parent::__construct();
			$this->Ldap = $this->SpocPbx;		
			$this->filename = $_SERVER['DOCUMENT_ROOT'].'/ldap/ldap_connection.json';
		}
		

		public function is_user_imported($username) {
			return !empty($this->query("SELECT * FROM sip_users WHERE sip_islocaluser=2 AND sip_username='{$username}'"));
		}

		public function import_user() {

			// username
			// account password
			// voiemail pin
			// first name
			// middle name
			// last name
			// extension
			// caller id
			// 

		}

		public function reset_password() {			
			$json_data = file_get_contents($this->filename);
			$contentsDecoded = json_decode($json_data);	
			$contentsDecoded->password = '';
			$json_data = json_encode($contentsDecoded);	
			file_put_contents($filename, $json_data);
		}

		public function is_auto_connect() {
			try {
				//$filename =  $_SERVER['DOCUMENT_ROOT']."/ldap/ldap_connection.json";
				$json_data = file_get_contents($this->filename);
				$ldapConnection = json_decode($json_data);
				
			} 
		    catch (adLDAPException $e) {
		        echo $e; 
		        return false;
		        exit();
		    }

		    if (!empty($ldapConnection) && 
		    	$ldapConnection->primary_host != '' && 
		    	$ldapConnection->secondary_host != '' && 
		    	$ldapConnection->dn != '' && 
		    	$ldapConnection->username != '' && 
		    	$ldapConnection->password != '' && 
		    	$ldapConnection->active == 1 && 
		    	!isset($_SESSION['ldap_userinfo'])) {
		    	return true;
		    }

		    return false;

		}

	}

	class Incoming extends SpocPbx {

		function __construct() {		
			parent::__construct();
			$this->Incoming = $this->SpocPbx;		
		}

		public function get_all_incoming_calls() {
			return $this->query('SELECT * FROM spocpbx_incoming_calls ORDER BY sort');
		}		

		public function get_max_sort() {
			$result = $this->query('SELECT max(sort) as sort FROM spocpbx_incoming_calls');
			$sort_max = 0;
			if (is_array($result)) {
				$sort_max = $result[0]->sort;
			}
			return $sort_max;
		}

		public function is_did_exist($did, $id) {
			return !empty($this->select('SELECT * FROM spocpbx_incoming_calls WHERE did = ? AND id <> ? ',array('did'=>$did, 'id'=>$id),array('%dd')));
		}

		public function get_ring_groups_members_extension($id) {
			$CallManagement = new CallManagement();
			$CallManagement->mysqli = $this->mysqli;
			$results = $CallManagement->get_ring_groups_members_extension($id);			
			$count = 0;
			$text = "";
			if (is_array($results)) {
				foreach ($results as $k => $v) {
					$extension = $results[$k]->sip_mailbox;
					if ($count == 0) {
						$text .= "Local/{$extension}";
					} else {
						$text .= "&Local/{$extension}";
					}
					$count++;
				}
			}
			return $text;
		}

		public function get_ring_group_name($id) {
			$CallManagement = new CallManagement();
			$CallManagement->mysqli = $this->mysqli;
			$name = $CallManagement->get_ring_group_name($id);
			return $name;
			
		}
		public function resort_order() {
			$results = $this->get_all_incoming_calls();
			if (is_array($results)) {
				$count = 1;
				foreach ($results as $k => $v) {
					$this->update('spocpbx_incoming_calls', array('sort' => $count), array('%d'), array('id'=>$results[$k]->id), array('%d'));
					$count++;
				}
			}
		}

		public function get_sip_user_extension($id) {
			$text = "";
			$results = $this->query("SELECT sip_mailbox FROM sip_users WHERE idsip_users = {$id}");

			if (is_array($results)) {
				$text = "Local/{$results[0]->sip_mailbox}";
			}
			return $text;
		}

		public function incoming_calls_conf() {
			
			$newline = $this->get_new_line();
			$results = $this->get_all_incoming_calls();
			$text = "";		
			$filename = "spocpbx-extensions-incoming.conf";
			$text .= "[incoming]{$newline}{$newline}";
			$text .= "include => incoming-custom{$newline}{$newline}";

			
			if (is_array($results)) {							
				foreach ($results as $k => $v) {
					$id = $results[$k]->id;					
					$name = $results[$k]->name;
					$note = $results[$k]->note;
					$did = $results[$k]->did;
					$ringtone = $results[$k]->ringtone;
					$extension = $results[$k]->extension;
					$active = $results[$k]->active;
					
					if ($active == 1) {
						if (strpos($extension, 'ring_group_') !== false) {
							$rg_id = filter_var($extension, FILTER_SANITIZE_NUMBER_INT);
							$dial_extension = $this->get_ring_groups_members_extension($rg_id);
							//get sip user extension list
						}
						else {
							$sip_user_id = filter_var($extension, FILTER_SANITIZE_NUMBER_INT);	
							$dial_extension = $this->get_sip_user_extension($sip_user_id);
							//get sip user extension
						}

						$text .= "; {$name} {$newline}";
						if ($note != '') {
							$text .= "exten => {$did},1,NoOp(\"{$note}\"){$newline}";
						} else {
							$text .= "exten => {$did},1,NoOp(){$newline}";
						}			
						if ($ringtone != '') {
	 						$text .= "same => n,SIPAddHeader(\"Alert-Info: <{$ringtone}>\"){$newline}";
	 					}
						$text .= "same => n,Dial($dial_extension){$newline}";
						$text .= "same => n,Hangup(){$newline}{$newline}";
					}
				}
			}
			
			return $this->write_conf_file($filename,$text);
		}
	}

	class Outgoing extends SpocPbx {

		function __construct() {		
			parent::__construct();
			$this->Outgoing = $this->SpocPbx;		
		}

		public function get_all_outgoing_calls() {
			return $this->query('SELECT * FROM spocpbx_outgoing_calls ORDER BY sort');
		}		

		public function get_max_sort() {
			$result = $this->query('SELECT max(sort) as sort FROM spocpbx_outgoing_calls');
			$sort_max = 0;
			if (is_array($result)) {
				$sort_max = $result[0]->sort;
			}
			return $sort_max;
		}

		public function get_trunk_name($id) {
			$result = $this->query("SELECT sip_username FROM sip_users WHERE sip_islocaluser = 0 AND idsip_users = {$id}");		
			$name = "";
			if (is_array($result)) {
				$name = $result[0]->sip_username;
			}
			return $name;
		}

		public function outgoing_calls_conf() {
			
			$newline = $this->get_new_line();
			$results = $this->get_all_outgoing_calls();
			$text = "";		
			$filename = "spocpbx-extensions-outgoing.conf";
			$text .= "[outgoing]{$newline}{$newline}";
			$text .= "include => outgoing-custom{$newline}{$newline}";

			if (is_array($results)) {							
				foreach ($results as $k => $v) {
					$id = $results[$k]->id;					
					$name = $results[$k]->name;
					$note = $results[$k]->note;
					$pattern_digits = $results[$k]->pattern_digits;
					if (!is_numeric($pattern_digits)) {
						$pattern_digits = '_'.$pattern_digits;
					}
					$pattern_trim = $results[$k]->pattern_trim;
					if ($pattern_trim != '') {
						$pattern_trim = ':'.$pattern_trim;
					}
					$pattern_prepend = $results[$k]->pattern_prepend;
					$active = $results[$k]->active;
					$sip_users_id = $results[$k]->sip_users_id;
					$trunk_name = $this->get_trunk_name($sip_users_id);
					if ($active == 1) {
						$text .= 'exten => '.$pattern_digits.',1,Dial(SIP/'.$trunk_name.'/'.$pattern_prepend.'${EXTEN'.$pattern_trim.'})'.$newline;
					}

				}
			}
			
			return $this->write_conf_file($filename,$text);
		}

	}
		
?>
