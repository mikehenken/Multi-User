<?php
	/*
	Plugin Name: Multi User
	Description: Adds Multi-User Management Section'
	Version: 1.5
	Author: Mike Henken
	Author URI: http://michaelhenken.com/
	*/

	// get correct id for plugin
	$thisfile = basename(__FILE__, ".php");

	// register plugin
	register_plugin($thisfile, // ID of plugin, should be filename minus php
	'Multi User',
	'1.5',
	'Mike Henken', // Author of plugin
	'http://www.michaelhenken.com/', // Author URL
	'Adds Multi-User Management - Edit all options for current users and manage permissions.', // Plugin Description
	'settings', // Page type of plugin
	'mm_admin' // Function that displays content
	);

	// activate hooks //
	//Add Sidebar Item In Settings Page
	add_action('settings-sidebar', 'createSideMenu', array($thisfile, 'User Management'));
	//Make the multiuser_perm() function run before each admin page loads
	add_action('header', 'mm_permissions');
	add_action('settings-user', 'mm_gs_settings_pg');

class MultiUser 
{
	public function __construct()
	{
		$old_add_file = GSPLUGINPATH.'user-managment-add.php';
		if(file_exists($old_add_file))
		{
			$success = unlink($old_add_file);
			if($success)
			{
				print "<div class=\"updated\" style=\"display: block;\">$old_add_file Has Been Successfully Deleted.<br/>This file was deleted because it is no longer needed for this plugin.</div>";
			}
			else
			{
				print "<div class=\"updated\" style=\"display: block;\"><span style=\"color:red;font-weight:bold;\">ERROR!!</span> - Unable To Delete $old_add_file<br/>You could delete $old_add_file if you would like. <br/>It is no longer needed for this plugin.</div>";
			}
		}
	}
	public function mmUserFile($get_Data, $data_Type = "")
	{
		if(get_cookie('GS_ADMIN_USERNAME') != "")
		{
			$current_user = get_cookie('GS_ADMIN_USERNAME');
			$dir = GSUSERSPATH . $current_user . ".xml";
			$user_file = simplexml_load_file($dir) or die("Unable to load XML file!");
			
			if($data_Type == "")
			{
				$return_user_data = $user_file->PERMISSIONS->$get_Data;
				return $return_user_data;
			}
			elseif($data_Type != "") 
			{
				$return_user_data = $user_file->$get_Data;
				return $return_user_data;
			}
		}
	}

	public function mmProcessSettings()
	{
		if(get_cookie('GS_ADMIN_USERNAME') != "")
		{
			global $xml;
			$perm = $xml->addChild('PERMISSIONS');
			$perm->addChild('PAGES', $this->mmUserFile('PAGES'));
			$perm->addChild('FILES', $this->mmUserFile('FILES'));
			$perm->addChild('THEME', $this->mmUserFile('THEME'));
			$perm->addChild('PLUGINS', $this->mmUserFile('PLUGINS'));
			$perm->addChild('BACKUPS', $this->mmUserFile('BACKUPS'));
			$perm->addChild('SETTINGS', $this->mmUserFile('SETTINGS'));
			$perm->addChild('SUPPORT', $this->mmUserFile('SUPPORT'));
			$perm->addChild('EDIT', $this->mmUserFile('EDIT'));
			$perm->addChild('LANDING', $this->mmUserFile('LANDING'));
			$perm->addChild('ADMIN', $this->mmUserFile('ADMIN')); 
		}
	}
	
	public function mmDeleteUser()
	{
		$deletename = $_GET['deletefile'];
		$thedelete = GSUSERSPATH . $deletename . '.xml';
		$success = unlink($thedelete);
		if($success)
		{
			print "<div class=\"updated\" style=\"display: block;\">$deletename Has Been Successfully Deleted</div>";
		}
		else
		{
			print "<div class=\"updated\" style=\"display: block;\"><span style=\"color:red;font-weight:bold;\">ERROR!!</span> - Unable To Delete File, Please Check Error Log Or Turn On Debug Mode</div>";
		}
		$this->mmManageUsersForm();
	}	
	
	public function mmProcessEditUser()
	{
		// check if new password was provided
		if (isset($_POST['userpassword'])) 
		{
			$pwd1 = $_POST['userpassword'];
			if ($pwd1 != '') 
			{
				$NPASSWD = passhash($pwd1);
			}
			else 
			{
				$NPASSWD = $_POST['nano']; 
			}
		}

		// GRAB DATA FROM FORM FORM
		$NUSR = $_POST['usernamec'];
		$usrfile = $_POST['usernamec'] . '.xml';
		$NLANDING = $_POST['Landing'];
		if($NLANDING == "pages.php") 
		{
			$NLANDING == "";
		}

		if (isset($_POST['usernamec'])) 
		{
			// Edit user xml file - This coding was mostly taken from the 'settings.php' page..
			$xml = new SimpleXMLElement('<item></item>');
			$xml->addChild('USR', $NUSR);
			$xml->addChild('PWD', $NPASSWD);
			$xml->addChild('EMAIL', $_POST['useremail']);
			$xml->addChild('HTMLEDITOR', $_POST['usereditor']);
			$xml->addChild('TIMEZONE', $_POST['ntimezone']);
			$xml->addChild('LANG', $_POST['userlng']);
			$perm = $xml->addChild('PERMISSIONS');
			$perm->addChild('PAGES', $_POST['Pages']);
			$perm->addChild('FILES', $_POST['Files']);
			$perm->addChild('THEME', $_POST['Theme']);
			$perm->addChild('PLUGINS', $_POST['Plugins']);
			$perm->addChild('BACKUPS', $_POST['Backups']);
			$perm->addChild('SETTINGS', $_POST['Settings']);
			$perm->addChild('SUPPORT', $_POST['Support']);
			$perm->addChild('EDIT', $_POST['Edit']);
			$perm->addChild('LANDING', $NLANDING);
			$perm->addChild('ADMIN', $_POST['Admin']);
			if (!XMLsave($xml, GSUSERSPATH . $usrfile)) 
			{
				$error = "Did Not Save File - ERROR!";
				echo $error;
			}
			
			// Redirect after script is completed... I will make the script submit via ajax later
			else 
			{
			  print '<div class="updated" style="display: block;">Your changes have been saved.</div>';
			}
			$this->mmManageUsersForm();
		}
	}
	public function mmAddUser()
	{
		//Set User File, Username, And Password From Submission
		$usrfile = strtolower($_POST['usernamec']);
		$usrfile	= $usrfile . '.xml';
		$NUSR = strtolower($_POST['usernamec']);
		$pwd1       = $_POST['userpassword'];
		$NPASSWD = passhash($pwd1);

		// create user xml file - This coding was mostly taken from the 'settings.php' page..
		createBak($usrfile, GSUSERSPATH, GSBACKUSERSPATH);
		if (file_exists(GSUSERSPATH . _id($NUSR).'.xml.reset')) { unlink(GSUSERSPATH . _id($NUSR).'.xml.reset'); }
		$xml = new SimpleXMLElement('<item></item>');
		$xml->addChild('USR', $NUSR);
		$xml->addChild('PWD', $NPASSWD);
		$xml->addChild('EMAIL', $_POST['useremail']);
		$xml->addChild('HTMLEDITOR', $_POST['usereditor']);
		$xml->addChild('TIMEZONE', $_POST['ntimezone']);
		$xml->addChild('LANG', $_POST['userlng']);
		$perm = $xml->addChild('PERMISSIONS');
		$perm->addChild('PAGES', $_POST['Pages']);
		$perm->addChild('FILES', $_POST['Files']);
		$perm->addChild('THEME', $_POST['Theme']);
		$perm->addChild('PLUGINS', $_POST['Plugins']);
		$perm->addChild('BACKUPS', $_POST['Backups']);
		$perm->addChild('SETTINGS', $_POST['Settings']);
		$perm->addChild('SUPPORT', $_POST['Support']);
		$perm->addChild('EDIT', $_POST['Edit']);
		$perm->addChild('LANDING', $_POST['Landing']);
		$perm->addChild('ADMIN', $_POST['Admin']);
		if (! XMLsave($xml, GSUSERSPATH . $usrfile) ) {
		$error = i18n_r('CHMOD_ERROR');
		}
		// Redirect after script is completed... I will make the script submit via ajax later
			else 
			{
				print '<div class="updated" style="display: block;">'.$NUSR.' Has Been Created.</div>';
			}
		//Show Manage Form
		$this->mmManageUsersForm();
	}
	
	public function mmManageUsersForm()
	{
		# get all available language files
      $lang_handle = opendir(GSLANGPATH) or die("Unable to open ". GSLANGPATH);
      while ($lfile = readdir($lang_handle)) {
      	if( is_file(GSLANGPATH . $lfile) && $lfile != "." && $lfile != ".." )	{
      		$lang_array[] = basename($lfile, ".php");
      	}
      }
      if (count($lang_array) != 0) {
      	sort($lang_array);
      	$count = '0'; $sel = ''; $langs = '';
      	foreach ($lang_array as $larray){
      		$langs .= '<option value="'.$larray.'" >'.$larray.'</option>';
      		$count++;
      	}
      }

     //Get Available Timezones
      ob_start(); include ("../admin/inc/timezone_options.txt");$Timezone_Include = ob_get_contents();ob_end_clean();

		//Styles For Form
	?>
		<style>
			.text {
				width:160px !important;
			}
			.user_tr_header {
				border:0px;border-bottom:0px;border-bottom-width:0px;
			}
			.user_tr {
				border:0px;border-bottom:0px;border-bottom-width:0px;background:#F7F7F7;
			}
			.user_tr td{
				border:0px;border-bottom:0px;border-bottom-width:0px;background:#F7F7F7;
			}
			.user_sub_tr {
				border:0px;border-bottom:0px !important; border-bottom-width:0px !important;border-top:0px;border-top-width:0px !important;display:none
			}
			.user_sub_tr h3{
				font-size:14px; padding:0px;margin:0px;
			}
			.user_sub_tr td{
				border:0px;border-bottom:0px !important;border-bottom-width:0px !important;padding-top:6px !important; border-top: 0px !important;
			}
			.hiduser {
				display:none;
			}
			.user_sub_tr select{
				width:160px;
			}
			.perm label {
				clear:left
			}
			.perm_div {
				width:70px;height:40px;float:left;margin-left:4px;
			}
			.leftsec {
				width:180px;float:left;
			}
			.rightsec {
				width:180px;
			}
			.perm_select {
				width:220px;float:left;
			}
			.perm_div_2 {
				width:auto;float:left;padding-top:6px;
			}
			.acurser {
				cursor:pointer;text-decoration:underline;color:#D94136;position:absolute;margin-left:0px;
			}
			.hcurser {
				cursor:pointer;text-decoration:underline;color:#D94136;
			}
			.edit-pointer {
				cursor:pointer;
			}
		</style>
		

      <!-- Below is the 'Table Headers' For The user data -->
		<h3 class="floated">User Management</h3>
		<div class="edit-nav clearfix">
			<p>
				<a href="#" id="add-user">Add New User</a>
			</p>
			<p>
				<a href="load.php?id=user-managment&download_id=133" ONCLICK="decision('Are You Sure You Want To Update This Plugin?')">Update This Plugin</a>
			</p>
		</div>
		
		<table class="user_table">
		<tr>
			<th>Username:</th>
			<th>Email:</th>
			<th>HTML Editor:</th>
			<th>Edit</th>
		</tr>

<?php
      // Open Users Directory And Put Filenames Into Array
      $dir = "./../data/users/*.xml";

      // Make Edit Form For Each User XML File Found
      foreach (glob($dir) as $file) {
          $xml = simplexml_load_file($file) or die("Unable to load XML file!");


      // PERMISSIONS CHECKBOXES - Checks XML File To Find Existing Permissions Settings //

		// Pages
		if ($xml->PERMISSIONS->PAGES != "")
		{
			$pageschecked = "checked";
			$pages_dropdown = "";
		}
		else 
		{
			$pageschecked = "";
			$pages_dropdown = "<option value=\"pages.php\">Pages</option>";
		}

		//Files - uploads.php
		if ($xml->PERMISSIONS->FILES != "") 
		{
			$fileschecked = "checked";
		}
		else {$fileschecked = "";}

		//Theme
		if ($xml->PERMISSIONS->THEME != "") 
		{
			$themechecked = "checked";
		}
		else {$themechecked = "";}

		//Plugins
		if ($xml->PERMISSIONS->PLUGINS != "") 
		{
			$pluginschecked = "checked";
		}
		else {$pluginschecked = "";}

		//Backuops
		if ($xml->PERMISSIONS->BACKUPS != "") 
		{
			$backupschecked = "checked";
		}
		else {$backupschecked = "";}

		//Settings
		if ($xml->PERMISSIONS->SETTINGS != "") 
		{
			$settingschecked = "checked";
		}
		else {$settingschecked = "";}


		//Support
		if ($xml->PERMISSIONS->SUPPORT != "") 
		{
			$supportchecked = "checked";
		}
		else {$supportchecked = "";}

		//Admin
		if ($xml->PERMISSIONS->ADMIN != "") 
		{
			$adminchecked = "checked";
		}
		else {$adminchecked = "";}

		//Landing Page
		if ($xml->PERMISSIONS->LANDING != "pages.php") 
		{
			$landingselected = $xml->PERMISSIONS->LANDING;
		}
		else {$landingselected = "pages.php";}

		//Edit
		if ($xml->PERMISSIONS->EDIT != "") 
		{
			$editchecked = "checked";
		}
		else {$editchecked = "";}
		
		//Html Editor
		if ($xml->HTMLEDITOR == "") 
		{
			$htmledit = "No";
		} 
		else 
		{
			$htmledit = "Yes";
		}

		if ($htmledit == "No") 
		{
		  $cchecked = "";
		} 
		elseif ($htmledit == "Yes") 
		{
		  $cchecked = "checked";
		}

		//Below is the User Data

?>
       
		<script language="javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</script>
		 
		   
		<tr class="user_tr">
			<td>
				&nbsp;<?php echo $xml->USR; ?>
			</td>
			<td>
				&nbsp;<?php echo $xml->EMAIL; ?>
			</td>
			<td>
				&nbsp;<?php echo $htmledit; ?>
			</td>

			<!-- Edit Button (Expanded By Jquery Script) -->
			<td>
				<a style="" class="edit-pointer edit-user<?php echo $xml->USR; ?> acurser">Edit</a><a style="" class="hide-user<?php echo $xml->USR; ?> acurser hiduser">Hide</a>
			</td>
		</tr>

		<!-- Begin 'Edit User' Form -->
		<form method="post" action="load.php?id=user-managment">
		
		<!-- Edit Username -->
		<tr class="hide-div<?php echo $xml->USR; ?> user_sub_tr" style="">
		
			<td style=""></td>
			
			<!-- Edit Email -->
			<td style="">
				<input class="text" id="useremail" name="useremail" type="text" value="<?php echo $xml->EMAIL; ?>" />
			</td>

			<!-- HTML Editor Permissions -->
			<td  style="">
				<input name="usereditor" id="usereditor" type="checkbox" <?php echo $cchecked; ?> />
			</td>
			
		<!-- Change Password -->
		</tr>
		<tr class="hide-div<?php echo $xml->USR; ?> user_sub_tr" style="">

			<td style="">
				<label for="userpassword">Password:</label>
				<input autocomplete="off" class="text" id="userpassword" name="userpassword" type="password" value="" />
			</td>


			<!-- Change Language -->
			<td>
				<label for="userlng">Language:</label>
				<select name="userlng" id="userlng" class="text">
					<option value="<?php echo $xml->LANG; ?>"selected="selected"><?php echo $xml->LANG; ?></option>
					<?php echo $langs; ?>
				</select>
			</td>

			<!-- Change Timezone -->
			<td>
				<label for="ntimezone">Timezone:</label>
				<select class="text" id="ntimezone" name="ntimezone">
					<option value="<?php echo $xml->TIMEZONE; ?>"  selected="selected"><?php echo $xml->TIMEZONE; ?></option>
					<?php echo $Timezone_Include; ?>
				</select>
			</td>
		</tr>
         
		<!-- Permissions Checkboxes -->
		<tr class="hide-div<?php echo $xml->USR; ?> user_sub_tr perm" style="">
			<td colspan="4" height="16">
				<h3 style="">Permissions (<strong>Check Areas</strong> You Would Like <strong>To Block</strong> Access To)</h3>
			</td>
		</tr>
					
		<tr class="hide-div<?php echo $xml->USR; ?> user_sub_tr" style="">
			<td colspan="4">
			<div class="perm_div"><label>Pages</label>
				<input type="checkbox" name="Pages" value="no" <?php echo $pageschecked; ?> />
			</div>

			<div class="perm_div"><label>Files</label>
				<input type="checkbox" name="Files" value="no" <?php echo $fileschecked; ?> />
			</div>

			<div class="perm_div"><label>Theme</label>
				<input type="checkbox" name="Theme" value="no" <?php echo $themechecked; ?> />
			</div>

			<div class="perm_div"><label>Plugins</label>
				<input type="checkbox" name="Plugins" value="no" <?php echo $pluginschecked; ?> />
			</div>

			<div class="perm_div"><label>Backups</label>
				<input type="checkbox" name="Backups" value="no" <?php echo $backupschecked; ?> />
			</div>

			<div class="perm_div"><label>Settings</label>
				<input type="checkbox" name="Settings" value="no" <?php echo $settingschecked; ?> />
			</div>

			<div class="perm_div"><label>Support</label>
				<input type="checkbox" name="Support" value="no" <?php echo $supportchecked; ?> />
			</div>

			<div class="perm_div"><label>Edit</label>
				<input type="checkbox" name="Edit" value="no" <?php echo $editchecked; ?> />
			</div>

			<div class="perm_select"><label>Custom Landing Page (Optional)
				<a class="hcurser" title="This is where you can set an alternate landing page the user will arrive at upon logging in">?</a></label>
				<select name="Landing" id="userland" class="text">
					<option value="$landingselected" selected="selected"><?php echo $landingselected; ?></option>
					<?php echo $pages_dropdown; ?>
					<option value="theme.php">Theme</option>
					<option value="settings.php">Settings</option>
					<option value="support.php">Support</option>
					<option value="edit.php">Edit</option>
					<option value="plugins.php">Plugins</option>
					<option value="upload.php">Upload</option>
					<option value="backups.php">Backups</option>
				</select>
			</div>

			<div class="perm_div_2">
				<label>Disable Admin Access (Cannot Manage Users)</label>
				<input type="checkbox" id="Admin" name="Admin" value="no" <?php echo $adminchecked; ?> />
			</div>

			<div class="clear"></div>

			</td>
		</tr>

		<!-- Submit Form -->
		<tr class="hide-div<?php echo $xml->USR; ?> user_sub_tr perm" style="">
		<td>
			<input class="submit" type="submit" name="edit-user" value="Save Changes"/>
			&nbsp;&nbsp;&nbsp;<a class="hcurser" ONCLICK="decision('Are You Sure You Want To Delete <?php echo $xml->USR; ?>','load.php?id=user-managment&deletefile=<?php echo $xml->USR; ?>')">Delete User</a>
		</td>
		</tr>
		</div>
		<input type="hidden" name="nano" value="<?php echo $xml->PWD; ?>"/><input type="hidden" name="usernamec" value="<?php echo $xml->USR; ?>"/>
		</form>
     

    
<?php
 }
 echo "</table>";
 echo '<script type="text/javascript">';
      //For Each User XML Filed, Print jQuery To Show/Hide The 'Edit User' And 'Add User' Sections
      foreach (glob($dir) as $file) {
          $xml = simplexml_load_file($file) or die("Unable to load XML file!");
		  ?>
		  
          $(".edit-user<?php echo $xml->USR; ?>").click(function () {
			  $(".edit-user<?php echo $xml->USR; ?>").slideUp();         
			  $(".hide-user<?php echo $xml->USR; ?>").slideDown();        
			  $(".hide-div<?php echo $xml->USR; ?>").css('display','table-row');  
          });         
          $(".hide-user<?php echo $xml->USR; ?>").click(function () {         
			  $(".edit-user<?php echo $xml->USR; ?>").slideDown();          
			  $(".hide-user<?php echo $xml->USR; ?>").slideUp();         
			  $(".hide-div<?php echo $xml->USR; ?>").css('display','none');         
          });
          $("hideagain").click(function () {         
			  $(".edit-user<?php echo $xml->USR; ?>").slideUp();        
			  $(".hide-div<?php echo $xml->USR; ?>").css('display','none');    
          });
          $("#add-user").click(function () {       
			  $("#add-user").slideUp();       
			  $(".hide-div").slideDown();          
          });
	  <?php
      }
      echo "</script>";

                             // ADD USER FORM //
?>
    
 <!-- Below is the html form to add a new user.. It is proccesed with 'readxml.php' -->
      <div id="profile" class="hide-div section" style="display:none;margin-top:0px;">
      <form method="post" action="load.php?id=user-managment">
    <h3>Add New User</h3>
    <div class="leftsec">
      <p><label for="usernamec" >Username:</label><input class="text" id="usernamec" name="usernamec" type="text" value="" /></p>
    </div>
    <div class="rightsec">
      <p><label for="useremail" >Email :</label><input class="text" id="useremail" name="useremail" type="text" value="" /></p>
    </div>
    <div class="leftsec">
      <p><label for="ntimezone" >Timezone:</label>
      <select class="text" id="ntimezone" name="ntimezone">
      <option value="<?php echo $this->mmUserFile('TIMEZONE', true); ?>"  selected="selected"><?php echo $xml->TIMEZONE; ?></option>
          <?php echo $Timezone_Include; ?>
								</select>
      </select>
      </p>
    </div>
    <div class="rightsec">
      <p><label for="userlng" >Language:</label>
      <select name="userlng" id="userlng" class="text">
			<option value="en_US"selected="selected">English (en_US)</option>
           <?php echo $langs ?>
      </select>
      </p>
    </div>
     <div class="leftsec">
      <p><label for="userpassword" >Password:</label><input autocomplete="off" class="text" id="userpassword" name="userpassword" type="password" value="" /></p>
    </div>
     <div class="leftsec">
       <p class="inline" style="padding-top:24px;"><input name="usereditor" id="usereditor" type="checkbox" value="1" checked="checked" /> &nbsp;<label for="usereditor" >Enable the HTML editor</label></p>
    </div>
      <div class="clear"></div>
      <h3 style="font-size:14px;">Permissions (<strong>Check Areas</strong> You Would Like <strong>To Block</strong> Access To)</h3>
             <div class="perm_div"><label for="Pages">Pages</label>
                             <input type="checkbox" id="Pages" name="Pages" value="no" />
                             </div>

                             <div class="perm_div"><label for="Files">Files</label>
                             <input type="checkbox" id="Files" name="Files" value="no" />
                             </div>

                             <div class="perm_div"><label for="Theme">Theme</label>
                             <input type="checkbox" id="Theme" name="Theme" value="no" />
                             </div>

                             <div class="perm_div"><label for="Plugins">Plugins</label>
                             <input type="checkbox" id="Plugins" name="Plugins" value="no" />
                             </div>

                             <div class="perm_div"><label for="Backups">Backups</label>
                             <input type="checkbox" id="Backups" name="Backups" value="no" />
                             </div>

                             <div class="perm_div"><label for="Settings">Settings</label>
                             <input type="checkbox" id="Settings" name="Settings" value="no" />
                             </div>

                             <div class="perm_div"><label for="Support">Support</label>
                             <input type="checkbox" id="Support" name="Support" value="no" />
                             </div>

                             <div class="perm_div"><label for="Edit">Edit</label>
                             <input type="checkbox" id="Edit" name="Edit" value="no" />
                             </div>
                             <div style="clear:both"></div>

                             <div class="perm_select"><label for="userland">Custom Landing Page (Optional)
                             <a href="#" title="This is where you can set an alternate landing page the user will arrive at upon logging in">?</a></label>
                             <select name="Landing" id="userland" class="text">
                              <option value="" selected="selected"></option>
						      <option value="pages.php">Pages</option>
                              <option value="theme.php">Theme</option>
                              <option value="settings.php">Settings</option>
                              <option value="support.php">Support</option>
                              <option value="edit.php">Edit</option>
                              <option value="plugins.php">Plugins</option>
                              <option value="upload.php">Upload</option>
                              <option value="backups.php">Backups</option>
						      </select>
                             </div>

                             <div class="perm_div_2"><label for="Admin">Disable Admin Access (Cannot Manage Users)</label>
                             <input type="checkbox" id="Admin" name="Admin" value="no" />
                             </div>

                             <div class="clear"></div>



    <div class="rightsec">
      <p></p>
    </div>
    <div class="clear"></div>

    <p id="submit_line" >
      <span><input class="submit" type="submit" name="add-user" value="Add New User" /></span> 
	  &nbsp;&nbsp;<?php i18n('OR'); ?>&nbsp;&nbsp; <a class="cancel" href="settings.php?cancel"><?php i18n('CANCEL'); ?></a>
    </p></form>
    </div>
	
	<?php
	}
	
	public function mmCheckPermissions()
	{
		// echo $this->mmUserFile('SETTINGS'); // seems to be only for debug purposes!
		//Find Current script and trim path
		$current_file = $_SERVER["PHP_SELF"];
		$current_file = basename(rtrim($current_file, '/'));
		$current_script =  $_SERVER["QUERY_STRING"];

		//Settings.php permissions
		if ($current_file == "settings.php") {
		  if ($this->mmUserFile('SETTINGS') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
				   $settings_menu ="";
				   }
		}
		  if ($this->mmUserFile('SETTINGS') == "no") {
			  $settings_menu = ".settings {display:none !important;}";
			  $settings_footer = "$(\"a\").remove(\":contains('General Settings')\");";
		  }
				else {
				   $settings_menu ="";
				   $settings_footer = "";
				   }

		//backups.php permisions
		if ($current_file == "backups.php") {
		  if ($this->mmUserFile('BACKUPS') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
				   $backups_menu ="";
				   }
		}
		  if ($this->mmUserFile('BACKUPS') == "no") {
			  $backups_menu = ".backups {display:none !important;}";
			  $backups_footer = "$(\"a\").remove(\":contains('Backup Management')\");";
		  }
				else {
				   $backups_menu ="";
				   $backups_footer = "";
				   }

		//plugins.php permissions
		if ($current_file == "plugins.php") {
		  if ($this->mmUserFile('PLUGINS') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
				   $plugins_menu ="";
				   }
		}
		  if ($this->mmUserFile('PLUGINS') == "no") {
			  $plugins_menu = ".plugins {display:none !important;}";
			  $plugins_footer = "$(\"a\").remove(\":contains('Plugin Management')\");";
		  }
				else {
				   $plugins_menu ="";
				   $plugins_footer = "";
				   }

		//pages.php permissions - If pages is disabled, this coding will kill the pages script and redirect to the chosen alternate landing page
		if ($current_file == "pages.php") {
		  if ($this->mmUserFile('PAGES') == "no") {
			die('<meta http-equiv="refresh" content="0;url='.$this->mmUserFile('LANDING').'">');
		  }
				else {
				   $pages_menu ="";
				   }
		}
		  if ($this->mmUserFile('PAGES') == "no") {
			  $pages_menu = ".pages {display:none !important;}";
			  $pages_footer = "$(\"a\").remove(\":contains('Page Management')\");";
		  }
				else {
				   $pages_menu ="";
				   $pages_footer = "";
				   }

		//support.php & health-check.php permissions
		if ($current_file == "support.php") {
		  if ($this->mmUserFile('SUPPORT') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
					$support_menu = "";
				   }
		}
		 if ($this->mmUserFile('SUPPORT') == "no") {
			  $support_menu = ".support {display:none !important;}";
			  $support_footer = "$(\"a\").remove(\":contains('Support')\");";
		  }
				else {
					$support_menu = "";
					$support_footer = "";
				   }

		//uploads.php (files page) permissions
		if ($current_file == "upload.php") {
		  if ($this->mmUserFile('FILES') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
					 $files_menu = "";
					 $files_footer = "";
				   }
		}
		  if ($this->mmUserFile('FILES') == "no") {
			  $files_menu = ".files {display:none !important;}";
			  $files_footer = "$(\"a\").remove(\":contains('File Management')\");";
		  }
				else {
					 $files_menu = "";
					 $files_footer = "";
				   }

		//theme.php permissions
		if ($current_file == "theme.php") {
		  if ($this->mmUserFile('THEME') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
					$theme_menu = "";
				   }
		}
		 if ($this->mmUserFile('THEME') == "no") {
			  $theme_menu = ".theme {display:none !important;}";
			  $theme_footer = "$(\"a\").remove(\":contains('Theme Management')\");";
		  }
				else {
					$theme_menu = "";
					$theme_footer = "";
				   }

		//archive.php
		if ($current_file == "archive.php") {
		  if ($this->mmUserFile('BACKUPS') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {

				   }
		}

		//theme-edit.php permissions
		if ($current_file == "theme-edit.php") {
		  if ($this->mmUserFile('THEME') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {

				   }
		}

		//components.php permissions
		if ($current_file == "components.php") {
		  if ($this->mmUserFile('THEME') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {

				   }
		}


		//edit.php
		if ($current_file == "edit.php") {
		  if ($this->mmUserFile('EDIT') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
				else {
				  $edit_menu = "";
				   }
		}
		if ($this->mmUserFile('EDIT') == "no") {
			  $edit_footer = "$(\"a\").remove(\":contains('reate New Page')\");";
		  }
				else {
				  $edit_menu = "";
				  $edit_footer ="";
				   }

		//Admin - Do not allow permissions to edit users
		if ($current_script == "id=user-managment") {
		  if ($this->mmUserFile('ADMIN') == "no") {
			  die('You Do Not Have Permissions To Access This Page');
		  }
		}

		if ($this->mmUserFile('ADMIN') == "no") {
				$admin_footer = "$(\"a\").remove(\":contains('User Management')\");";
		  }
				else {
				  $admin_footer ="";
				   }

		//Hide Menu Items
		echo"<style type=\"text/css\">";

		echo $settings_menu . $backups_menu . $plugins_menu . $pages_menu . $support_menu . $files_menu . $theme_menu;

		echo "</style>";

		//Hide Footer Menu Items With Jquery
		echo "<script type=\"text/javascript\">";
		echo "\n";
		echo "$(document).ready(function() {";
		echo "\n";
		echo $files_footer . $settings_footer . "\n" . $backups_footer . "\n" . $plugins_footer . "\n" . $pages_footer . "\n" . $support_footer . "\n" . $theme_footer . "\n" . $edit_footer . "\n" . $admin_footer;
		echo "\n";
		echo " });";
		echo "</script>";
	}
	
	        public function DownloadPlugin($id)
        {
					$pluginurl = $this->DownloadPlugins($id, 'file');
					$pluginfile = $this->DownloadPlugins($id, 'filename_id');
					
					$data = file_get_contents($pluginurl);
					$fp = fopen($pluginfile, "wb");
					fwrite($fp, $data);
					fclose($fp);
					
					function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true)
					{
					  if ($zip = zip_open($src_file))
					  {
						if ($zip)
						{
						  $splitter = ($create_zip_name_dir === true) ? "." : "/";
						  if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";

						  // Create the directories to the destination dir if they don't already exist
						  create_dirs($dest_dir);

						  // For every file in the zip-packet
						  while ($zip_entry = zip_read($zip))
						  {
							// Now we're going to create the directories in the destination directories

							// If the file is not in the root dir
							$pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
							if ($pos_last_slash !== false)
							{
							  // Create the directory where the zip-entry should be saved (with a "/" at the end)
							  create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
							}

							// Open the entry
							if (zip_entry_open($zip,$zip_entry,"r"))
							{

							  // The name of the file to save on the disk
							  $file_name = $dest_dir.zip_entry_name($zip_entry);

							  // Check if the files should be overwritten or not
							  if ($overwrite === true || $overwrite === false && !is_file($file_name))
							  {
								// Get the content of the zip entry
								$fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

								file_put_contents($file_name, $fstream );
								// Set the rights
								chmod($file_name, 0755);
							  }

							  // Close the entry
							  zip_entry_close($zip_entry);
							}
						  }
						  // Close the zip-file
						  zip_close($zip);
						}
					  }
					  else
					  {
						return false;
					  }

					  return true;
					}

					/**
					 * This function creates recursive directories if it doesn't already exist
					 *
					 * @param String  The path that should be created
					 *
					 * @return  void
					 */
					function create_dirs($path)
					{
					  if (!is_dir($path))
					  {
						$directory_path = "";
						$directories = explode("/",$path);
						array_pop($directories);

						foreach($directories as $directory)
						{
						  $directory_path .= $directory."/";
						  if (!is_dir($directory_path))
						  {
							mkdir($directory_path);
							chmod($directory_path, 0777);
						  }
						}
					  }
					}
					
					$pluginname = $this->DownloadPlugins($id, 'name');

					 /* Unzip the source_file in the destination dir
					 *
					 * @param   string      The path to the ZIP-file.
					 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
					 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
					 * @param   boolean     Overwrite existing files (true) or not (false)
					 *
					 * @return  boolean     Succesful or not
					 */

					// Extract C:/zipfiletest/zip-file.zip to C:/another_map/zipfiletest/ and doesn't overwrite existing files. NOTE: It doesn't create a map with the zip-file-name!
					$success = unzip($pluginfile, "../plugins/", true, true);
					if ($success){
					  print '<div class="updated">'.$pluginname.' Was Succesfully Updated</div>';
					}
					else{
					  print "<div class=\"updated\">Error: DAMN! The Script Could Not Extract And CHMOD The Archive</div>";
					}
			$this->mmManageUsersForm();
	}
						
				public function DownloadPlugins($id, $get_field)
				{
					$my_plugin_id = $id; // replace this with yours

					$apiback = file_get_contents('http://get-simple.info/api/extend/?id='.$my_plugin_id);
					$response = json_decode($apiback);
					if ($response->status == 'successful') {
							// Successful api response sent back. 
							$get_field_data = $response->$get_field;
					}

            return $get_field_data;
        }
}

	function mm_admin()
	{
		$mm_admin = new MultiUser;
		
		if(!isset($_POST['usernamec'])  && !isset($_GET['deletefile']) && !isset($_POST['add-user']) && !isset($_GET['download_id']))
		{
			$mm_admin->mmManageUsersForm();
		}
		
		if(isset($_POST['edit-user']))
		{
			$mm_admin->mmProcessEditUser();
		}
		
		if(isset($_GET['deletefile']))
		{
			$mm_admin->mmDeleteUser();
		}
		
		if(isset($_POST['add-user']))
		{
			$mm_admin->mmAddUser();
		}
		
		if(isset($_GET['download_id']))
		{
			$mm_admin->DownloadPlugin($_GET['download_id']);
		}
	}
	
	function mm_permissions()
	{
		$mm_admin = new MultiUser;
		$mm_admin->mmCheckPermissions();
	}
	
	function mm_gs_settings_pg()
	{
		$mm_settings = new MultiUser;
		$mm_settings->mmProcessSettings();
	}
?>