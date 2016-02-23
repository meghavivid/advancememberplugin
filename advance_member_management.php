<?php
/*
Plugin Name: Advance Member Management
Plugin URI: http://localhost/wordpress_test/
Description: Advance Member Management
Version: 3.0.0
Author: Automattic
Author URI: http://localhost/wordpress_test/
License: 
Text Domain: AMM
*/


/* AMM : Create tables */
function amm_install_tabels() {
    global $wpdb;
	$table_name = $wpdb->prefix . "amm_register_by_invitations";
	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	 `amm_id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `invitation_code` varchar(12) NOT NULL,
	  `invitation_limit` int(11) NOT NULL,
	  PRIMARY KEY (`amm_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;";
		
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	
	$table_name = $wpdb->prefix . "amm_register_by_invitation_users";
	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	  `amm_users_id` int(11) NOT NULL AUTO_INCREMENT,
	  `amm_id` int(11) NOT NULL,
	  `invitee_user_id` int(11) NOT NULL,
	  `invited_user_id` int(11) NOT NULL,
	   `levels` longtext NOT NULL,
	  PRIMARY KEY (`amm_users_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;";
	
	dbDelta($sql);
	
	$table_name = $wpdb->prefix . "amm_referaal";
	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	  `id` int(12) NOT NULL AUTO_INCREMENT,
	  `user_id` int(12) NOT NULL,
	  `referaal_id` int(12) NOT NULL,
	  `level` varchar(255) NOT NULL,
	  `level_description` varchar(255) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;";
	
	dbDelta($sql);
	
	// AMM : Add role of Advance member Admin
	add_role( 'advance_member_admin', 'Advance member Admin', array( 'read' => true, 'level_0' => true ) );
	  
	$settings_option = get_option("amm-settings");
	if($settings_option == "") {		
		$settings_Arr = array();
		$settings_Arr["invitation_limit"] = 0;	
		$settings_Arr["invitation_code_method"] = "random";
		$settings_Arr["affiliate_amount"] = "30";
		$settings_Arr["send_register_mail"] = "no";
		$settings_Arr["amm_login_redirect"] = "";
		$settings_Arr["amm_register_redirect"] = "";
		$content = 'Hi, [username] <br />
				
				User Name: [username]
				Please visit the following link to set your password. <br />
				[activationlink]
				<br /><br />
				Regards,
				Admin
				';
		$settings_Arr["amm_mail_content"] =$content;		
		$content = 'Dear [user_name] <br />Thanks for your registration, your account is pending for approval.
		<br />Thanks.';	
		$settings_Arr["amm_mail_approve_content"] =$content;	
	}
	else 
	{
		$settings_Arr = array();
		$settings_Arr1 = json_decode($settings_option);
		if(isset($settings_Arr1->invitation_limit) && $settings_Arr1->invitation_limit != "")
			$settings_Arr["invitation_limit"] = $settings_Arr1->invitation_limit;	
		else
			$settings_Arr["invitation_limit"] = 0;	
		if(isset($settings_Arr1->invitation_code_method) && $settings_Arr1->invitation_code_method != "")
			$settings_Arr["invitation_code_method"] =$settings_Arr1->invitation_code_method;	
		else
			$settings_Arr["invitation_code_method"] = "random";	
		if(isset($settings_Arr1->affiliate_amount) && $settings_Arr1->affiliate_amount != "")
			$settings_Arr["affiliate_amount"] =$settings_Arr1->affiliate_amount;	
		else
			$settings_Arr["affiliate_amount"] = "30";	
		if(isset($settings_Arr1->send_register_mail) && $settings_Arr1->send_register_mail != "")
			$settings_Arr["send_register_mail"] =$settings_Arr1->send_register_mail;	
		else
			$settings_Arr["send_register_mail"] = "no";	
		if(isset($settings_Arr1->amm_login_redirect) && $settings_Arr1->amm_login_redirect != "")
			$settings_Arr["amm_login_redirect"] =$settings_Arr1->amm_login_redirect;	
		else
			$settings_Arr["amm_login_redirect"] = "";	
		if(isset($settings_Arr1->amm_register_redirect) && $settings_Arr1->amm_register_redirect != "")
			$settings_Arr["amm_register_redirect"] =$settings_Arr1->amm_register_redirect;	
		else
			$settings_Arr["amm_register_redirect"] = "";	
		if(isset($settings_Arr1->amm_mail_content) && $settings_Arr1->amm_mail_content != "")
			$settings_Arr["amm_mail_content"] =$settings_Arr1->amm_mail_content;	
		else
		{	
			$content = 'Hi, [username] <br />
					
					User Name: [username]
					Please visit the following link to set your password. <br />
					[activationlink]
					<br /><br />
					Regards,
					Admin
					';
			$settings_Arr["amm_mail_content"] =$content;	
		}		
		if(isset($settings_Arr1->amm_mail_approve_content) && $settings_Arr1->amm_mail_approve_content != "")
			$settings_Arr["amm_mail_approve_content"] =$settings_Arr1->amm_mail_approve_content;	
		else
		{
			$content = 'Dear [user_name] <br />Thanks for your registration, your account is pending for approval.
			<br />Thanks.';	
			$settings_Arr["amm_mail_approve_content"] =$content;	
		}
	}
	update_option("amm-settings",json_encode($settings_Arr));
			
	/* Generate Invitation Code for admin */
	global $current_user;
	
	if(isset($current_user->caps["administrator"]))
	{
		global $wpdb; 
		$check_exist = $wpdb->get_row(
			$wpdb->prepare(
				"select * from ". $wpdb->prefix."amm_register_by_invitations where user_id=%d",
				$current_user->ID
			)
		);			
		
		if($check_exist == "")
		{
			$user_invitation_code = amm_generate_random_invitation_code($current_user->ID);
			
			$wpdb->insert(
				$wpdb->prefix."amm_register_by_invitations",
				array(
					"user_id"=>$current_user->ID,
					"invitation_code"=>$user_invitation_code,
					"invitation_limit"=>0
				),
				array(
					"%d",
					"%s",
					"%d"
				)
				
			);
	
		}
		$wpdb->delete( $wpdb->prefix."posts", array( 'post_title' =>"AMM Activate" ) );
		$wpdb->delete( $wpdb->prefix."posts", array( 'post_title' =>"AMM Lost Password" ) );
		$wpdb->delete( $wpdb->prefix."posts", array( 'post_title' =>"AMM Set Password" ) );
		
			// Create activation post object
		$my_post = array(
		  'post_title'    => 'AMM Activate',
		  'post_content'  => '[amm_activate]',
		  'post_status'   => 'publish',
		  'post_author'   => $current_user->ID,
		  'post_type' => 'page'
		);

		// Insert the activation page into the database
		wp_insert_post( $my_post );
		
		$my_post = array(
		  'post_title'    => 'AMM Lost Password',
		  'post_content'  => '[amm_lost_password]',
		  'post_status'   => 'publish',
		  'post_author'   => $current_user->ID,
		  'post_type' => 'page'
		);

		// Insert the activation page into the database
		wp_insert_post( $my_post );
		$my_post = array(
		  'post_title'    => 'AMM Set Password',
		  'post_content'  => '[amm_set_new_password]',
		  'post_status'   => 'publish',
		  'post_author'   => $current_user->ID,
		  'post_type' => 'page'
		);

		// Insert the activation page into the database
		wp_insert_post( $my_post );
	}
}
register_activation_hook( __FILE__, 'amm_install_tabels' );
/* AMM : End Create tables */

/* AMM :  Drop tables */
register_deactivation_hook(__FILE__, 'amm_tables_uninstall_action');
function amm_tables_uninstall_action () 
{
	global $wpdb;	
	$table_name = $wpdb->prefix . "amm_register_by_invitations";
	$wpdb->query("delete from $table_name");
	
	$table_name = $wpdb->prefix . "amm_register_by_invitation_users";
	$wpdb->query("delete from $table_name");
	
	$table_name = $wpdb->prefix . "amm_referaal";
	$wpdb->query("delete from $table_name");
	
}
/* AMM : End Drop tables */

/* AMM : Add invitation code field in register form  */
add_action( 'register_form', 'amm_register_form' );
function amm_register_form() {		
		
		$invitation_code = ( ! empty( $_POST['invitation_code'] ) ) ? trim( $_POST['invitation_code'] ) : '';
		$first_name = ( ! empty( $_POST['first_name'] ) ) ? trim( $_POST['first_name'] ) : '';
		$last_name = ( ! empty( $_POST['last_name'] ) ) ? trim( $_POST['last_name'] ) : '';
		$bank_account = ( ! empty( $_POST['bank_account'] ) ) ? trim( $_POST['bank_account'] ) : '';
		$id_no = ( ! empty( $_POST['id_no'] ) ) ? trim( $_POST['id_no'] ) : '';
		$phone_number = ( ! empty( $_POST['phone_number'] ) ) ? trim( $_POST['phone_number'] ) : '';
		
		if(isset($_REQUEST["amm_front_end"]) && $_REQUEST["amm_front_end"] == "amm_front")
		{
			
			$invitation_code = "";
			$first_name = "";
			$last_name = "";
			$bank_account = "";
			$id_no = "";
			$phone_number = "";
		}	
		
		if(is_user_logged_in()) {
			global $current_user;
			$current_user = wp_get_current_user();
			
			$invitation_code = get_user_meta( $current_user->ID, 'invitation_code', true );		
			$first_name= get_user_meta( $current_user->ID, 'first_name', true);		
			$last_name = get_user_meta( $current_user->ID, 'last_name', true);		
			$bank_account = get_user_meta( $current_user->ID, 'bank_account', true);		
			$id_no = get_user_meta( $current_user->ID, 'id_no', true );		
			$phone_number = get_user_meta( $current_user->ID, 'phone_number',true);		
		}
			
        ?>
		<p>
            <label for="first_name"><?php  _e( 'First Name', 'AMM' ) ?> <span>*</span><br />
            <input type="text" name="first_name" id="first_name" class="input" value="<?php echo $first_name; ?>" size="25" /></label>
        </p>
		<p>
            <label for="last_name"><?php  _e( 'Last Name', 'AMM' ) ?> <span>*</span><br />
            <input type="text" name="last_name" id="last_name" class="input" value="<?php  echo  $last_name; ?>" size="25" /></label>
        </p>
		<p>
            <label for="bank_account"><?php  _e( 'Bank Account', 'AMM' ) ?><br />
            <input type="text" name="bank_account" id="bank_account" class="input" value="<?php  echo  $bank_account; ?>" size="25" /></label>
        </p>
		<p>
            <label for="id_no"><?php  _e( 'ID Number', 'AMM' ) ?><br />
            <input type="text" name="id_no" id="id_no" class="input" value="<?php  echo  $id_no; ?>" size="25" /></label>
        </p>
		<p>
            <label for="phone_number"><?php  _e( 'Phone Number', 'AMM' ) ?><br />
            <input type="text" name="phone_number" id="phone_number" class="input" value="<?php  echo  $phone_number; ?>" size="25" /></label>
        </p>
        <p>
            <label for="invitation_code"><?php  _e( 'Invitation Code', 'AMM' ) ?><br />
            <input <?php if(is_user_logged_in()) { ?> disabled <?php } ?> type="text" name="invitation_code" id="invitation_code" class="input" value="<?php echo $invitation_code; ?>" size="25" /></label>
        </p>
        <?php
}
/* AMM : End Add invitation code field in register form */

/* AMM : Check validation of invitation code  */
add_filter( 'registration_errors', 'amm_registration_errors', 10, 3 );
function amm_registration_errors( $errors, $sanitized_user_login, $user_email ) {
	global $wpdb;
	if ( empty( $_POST['invitation_code'] ) || ! empty( $_POST['invitation_code'] ) && trim( $_POST['invitation_code'] ) == '' ) {
		$errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: You must add invitation code.', 'AMM' ) );
	}
	else if($_POST['invitation_code'] != "")
	{
		/*$check_invitation_code = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code='".$_POST['invitation_code']."'");*/
		$check_invitation_code = $wpdb->get_row(
			$wpdb->prepare(
				"select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",
				$_POST['invitation_code']
			)
		)	;
		if($check_invitation_code == "")
		{		
			$errors->add( 'invitation_code_error', __( '<strong>ERROR</strong>: Invitation Code is not correct. Please check again .', 'AMM' ) );	
		}		
	}
	if($check_invitation_code != "" ) {
		$settings_Arr = json_decode(get_option("amm-settings"));
		if($settings_Arr->invitation_limit == 0)
		{
			$errors->add( 'invitation_code_error',__("Sorry Registration Disabled By Invitation Code","AMM"));
		}	
		else 
		{
			if($settings_Arr->invitation_limit != -1)
			{
				$get_invitation_user_id = $wpdb->get_row(
					$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",
					$_POST['invitation_code']
					)
				)	;	
				if($get_invitation_user_id != "")
				{
					if($get_invitation_user_id->invitation_limit >= $settings_Arr->invitation_limit)
					{
						$err = 'This user has invited '.$get_invitation_user_id->invitation_limit.' people already, and can’t not invite more. Please find another member to invite you';	
						$errors->add( 'invitation_code_error','invitation limit reach');
					}
				}
			}
		}
	}
	return $errors;
}
/* AMM : End Check validation of invitation code  */

/* AMM : Save invitation code for user  */
add_action( 'user_register', 'amm_user_register' );
function amm_user_register( $user_id ) {
	if ( ! empty( $_POST['invitation_code'] ) && !isset($_REQUEST["amm_front_end"])) {
		global $wpdb;
		update_user_meta( $user_id, 'invitation_code', trim( $_POST['invitation_code'] ) );		
		update_user_meta( $user_id, 'first_name', trim( $_POST['first_name'] ) );		
		update_user_meta( $user_id, 'last_name', trim( $_POST['last_name'] ) );		
		update_user_meta( $user_id, 'bank_account', trim( $_POST['bank_account'] ) );		
		update_user_meta( $user_id, 'id_no', trim( $_POST['id_no'] ) );		
		update_user_meta( $user_id, 'phone_number', trim( $_POST['phone_number'] ) );		
		update_user_meta( $user_id, 'member_status', "pending" );				
		update_user_meta( $user_id, 'approve_status', "pending" );	
			
			
		/*$get_invitation_user_id = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code='".$_POST['invitation_code']."'");*/
		$get_invitation_user_id = $wpdb->get_row(
			$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",
			$_POST['invitation_code']
			)
		)	;	
		$user_invitation_code = amm_generate_random_invitation_code($user_id);
				
		$wpdb->insert( 
				$wpdb->prefix."amm_register_by_invitations", 
				array( 
					'user_id' => $user_id, 
					'invitation_code' => $user_invitation_code					
				), 
				array( 
					'%d', 
					'%s' 
				) 
			);
		$amm_id = $wpdb->insert_id;								
		$wpdb->insert( 
			$wpdb->prefix."amm_register_by_invitation_users", 
			array( 
				'amm_id' => $get_invitation_user_id->amm_id, 
				'invitee_user_id' => $get_invitation_user_id->user_id,
				'invited_user_id' => $user_id
			), 
			array( 
				'%d', 
				'%d',
				'%d'	
			) 
		);
		
		/* Update invitation limit */
		$invitation_limit = $get_invitation_user_id->invitation_limit + 1;
		$wpdb->update( 
			$wpdb->prefix."amm_register_by_invitations", 
			array( 
				'invitation_limit' => $invitation_limit
			), 
			array("amm_id"=>$get_invitation_user_id->amm_id),
			array( 
				'%d'
			) ,
			array("%d")
		);
		/*$inseret_user_ids = $wpdb->query("insert into ".$wpdb->prefix."amm_register_by_invitations(user_id,invitation_code)
										values(".$user_id.",'".$user_invitation_code."')");	
		$amm_id = $wpdb->insert_id;								
		$update_user_ids = $wpdb->query("insert into ".$wpdb->prefix."amm_register_by_invitation_users(amm_id,invitee_user_id,invited_user_id)
										values(".$get_invitation_user_id->amm_id.",".$get_invitation_user_id->user_id.",".$user_id.")");											*/
																		
	}
}
/* AMM : End Save invitation code for user  */	

/* AMM : Generate Invitation code randomly and check in database for duplication  */
function amm_generate_random_invitation_code($user_id) {	
	while(1) {
		
		$settings_Arr = json_decode(get_option("amm-settings"));
		$code_method = $settings_Arr->invitation_code_method;
		global $wpdb;
		if($code_method != "random")
		{
			if(isset($settings_Arr->amm_prefix) && $settings_Arr->amm_prefix != "")
				$amm_prefix = $settings_Arr->amm_prefix;
			else 
				$amm_prefix =$wpdb->prefix;
			$prefix = substr($amm_prefix,0,strlen($amm_prefix));
			$leading_zero_length = 10 - (strlen($prefix) + strlen($user_id));	
			$ttoal_ledings = 	$leading_zero_length   +  strlen($user_id);
			$number = sprintf('%0'.$ttoal_ledings.'d', $user_id);
			return $prefix.$number;
		}
		else { 
			$time =  microtime(true);
			$three_digits = explode(".",$time);
			$length = 10 - strlen($three_digits[1]);		
			$alphabets = range('A','Z');
			$numbers = range('0','9');
			$additional_characters = array('_','.');
			$final_array = array_merge($alphabets,$numbers,$additional_characters);
				
			$invitation_code = '';
		 
			while($length--) {
			  $key = array_rand($final_array);
			  $invitation_code .= $final_array[$key];
			}
			global $wpdb;
			$user_invitation_code =  $three_digits[1]. $invitation_code;
			/*$check_exisit_invitation_code = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code='".$user_invitation_code."'");*/
			$check_exisit_invitation_code = $wpdb->get_row(
				$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",
				$user_invitation_code)
			)	;
			if($check_exisit_invitation_code != "")
				amm_generate_random_invitation_code($user_id);
			else 
				return $three_digits[1]. $invitation_code;
		}
	}
}
/* AMM : End Generate Invitation code randomly and check in database for duplication  */  

/* AMM : Invitation Code Shortcode (Display current logged in user Invitation code  */
add_shortcode('amm_invitation_code', 'amm_invitation_code_function'); 
function amm_invitation_code_function()
{
	if(is_user_logged_in())
	{
		$current_user = wp_get_current_user();
		global $wpdb;
		/*$get_invitation_user_id = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id='".$current_user->ID."'");*/
		$get_invitation_user_id = $wpdb->get_row(
			$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id=%d",
			$current_user->ID)
		)	;
		
		if($get_invitation_user_id == "")
		{			
			$user_invitation_code = amm_generate_random_invitation_code($current_user->ID);
			/*$update_user_ids = $wpdb->query("insert into ".$wpdb->prefix."amm_register_by_invitations(user_id,invitation_code)
										values(".$current_user->ID.",'".$user_invitation_code."')");				*/
										
			$wpdb->insert(
				$wpdb->prefix."amm_register_by_invitations",
				array(
					"user_id"=>$current_user->ID,
					"invitation_code"=>$user_invitation_code
				),
				array(
					"%d",
					"%s"
				)				
			);
			$get_invitation_user_id = $wpdb->get_row(
				$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id=%d",
				$current_user->ID)
			);
			
			//echo "Your invitation code : ".$get_invitation_user_id->invitation_code;		
		}
		if($get_invitation_user_id != "") {
			$approve_status = get_user_meta($current_user->ID,"approve_status","true");
			global $current_user;
	
			if(!isset($current_user->caps["administrator"]))
			{
				if($approve_status != "approve")
					printf( __( "Your account is pending for approval, and you are not able to invite othes at this moment.<br />", 'AMM' ));		
			}	
			printf( __( "Your invitation code : %s", 'AMM' ), $get_invitation_user_id->invitation_code );	
		}
		else 
		{
			_e( "No invitation code is there for you", 'AMM' );
		}
	}
	else 
	{
		_e( "Please login to view your invitation code", 'AMM' );
		//echo "Please login ` view your invitation code";
	}
}
/* AMM : End Invitation Code Shortcode (Display current logged in user Invitation code  */  

/* AMM : Shortcode to List All registered user who comes to site through current logged in user  */
add_shortcode('amm_view_invited_users', 'amm_view_invited_users_function'); 
function amm_view_invited_users_function()
{
	$current_user = wp_get_current_user();
	global $wpdb;
	if(is_user_logged_in())
	{
	/*	$get_invitation_user_id = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id='".$current_user->ID."'");			*/
		$get_invitation_user_id = $wpdb->get_row(
			$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id=%d",
			$current_user->ID)
		)	;
		/*$invited_users = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id='".$current_user->ID."'");			*/
		$invited_users = $wpdb->get_results(
			$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id=%d",$current_user->ID)
		);
		
		if($invited_users == "")
		{
			?>
			<div class="amm_list_invited_users">
				<?php _e( "Sorry No users registered to site through your invitation code", 'AMM' ); ?>
				
			</div>
			<?php
		}
		else { 
			
			?>
			<div class="amm_list_invited_users">
				<table>
					<tr>
						<th><?php _e( "User Name", 'AMM' ); ?></th>
						<th><?php _e( "Registered Date", 'AMM' ); ?></th>
					</tr>
				<?php 
				foreach($invited_users as $obj_invited_users) { 
					$invited_user = get_user_by( 'id', $obj_invited_users->invited_user_id);
					?>
					<tr>
						<td><?php _e($invited_user->display_name,"AMM"); ?></td>
						<td><?php _e(date("Y-m-d",strtotime($invited_user->user_registered)),"AMM");?></td>
					</tr>					
					<?php
				}
				?>
				</table>
			</div>
			<?php
		}
	}
	else 
	{
	?>
		<div class="amm_list_invited_users">
			<?php _e("Sorry Please login to view your invited users","AMM"); ?>
		</div>
	<?php
	}
}
/* AMM : End Shortcode to List All registered user who comes to site through current logged in user  */ 
 
/* AMM : Add plugin setting menu for admin  */
add_action( 'admin_menu', 'amm_plugin_page' );

function amm_plugin_page(){
	
	add_menu_page( 'Advance Member Management', 'Advance Member Management', 'manage_options', 'amm-settings', 'amm_settings_function' ); 
	add_submenu_page('amm-settings','Advance Member Management','AMM Settings','manage_options','amm-settings');
	add_submenu_page('amm-settings','Manage Members','Manage Members','manage_options','amm-manage-memberes','amm_manage_memberes_function');
	
	add_submenu_page('amm-settings','Configuration','Configuration','manage_options','amm-conf-settings','amm_conf_settings_function');	
	 $role = get_role( 'advance_member_admin' );

    // This only works, because it accesses the class instance.
    // would allow the author to edit others' posts for current theme only
    $role->add_cap( 'amm_pages' ); 
	add_users_page('Advance Member Management', 'Advance Member Management', 'amm_pages', 'amm-settings', 'amm_settings_function');
}


function amm_manage_memberes_function()
{
	global $wpdb;
	?>
	<style>
	.all_invitations { 
		border: 1px solid gray;
		border-bottom: 0px;
		border-right: 0px;
		width: 98%;
	}
	.all_invitations th { 
	    padding: 10px;
		border-bottom: 1px solid gray;
		border-right: 1px solid gray;
	}
	.all_invitations td { 
	    padding: 10px;
		border-bottom: 1px solid gray !important;
		border-right: 1px solid gray;
		font-weight: normal;
	}
	.amm_filteres
	{
		margin-bottom: 10px;
	}
	.amm_loader 
	{		
		position: absolute;
		width: 100%;
		height: 100%;
		top: 0;
		z-index: 99999;
		text-align: center;
		display: none;
	}
	.amm_loader img 
	{
		position: absolute;
		top: 40%;
		left: 40%;
	}
	</style>
	<?php /* Display All users data with total count */ ?>	
	<h3><?php _e("All Users Details","AMM"); ?></h3>
	<div class="amm_notify"></div>
	<div class="amm_filteres">
		<label><b>Search By Any Field<b>&nbsp;&nbsp;&nbsp;</label>
		<input type="text" value="" id="amm_search" /><input type="button" class="button button-primary button-large srch_button" value="Search"/>
	</div>
	<div class="manage_members">
	<table class="all_invitations" id="amm_tblData">
		<tr>
			<th><?php _e("User Name","AMM"); ?></th>
			<th><?php _e("Registered Date","AMM"); ?></th>
			<th><?php _e("Invitation Code","AMM"); ?></th>
			<th><?php _e("First Name","AMM"); ?></th>
			<th><?php _e("Last Name","AMM"); ?></th>
			<th><?php _e("Bank Account Number","AMM"); ?></th>
			<th><?php _e("ID No","AMM"); ?></th>
			<th><?php _e("Phone Number","AMM"); ?></th>
			<th><?php _e("Total Registered Users","AMM"); ?></th>
			<th><?php _e("Status","AMM"); ?></th>
			<th><?php _e("Action","AMM"); ?></th>
			<th></th>
		</tr>
	<?php 
		$items_per_page = 5;
		$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		$offset = ( $page * $items_per_page ) - $items_per_page;
		if(!isset($_REQUEST["search"]) || $_REQUEST["search"] == "")
		{
			$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations limit ".$offset.",".$items_per_page);				
			$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations");		
		}
		else 			
		{
			$args = array(
						'meta_query' => array(
						'relation'=>'OR',
						array(
							'key'     => 'first_name',
							'value'   => $_REQUEST["search"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'last_name',
							'value'   => $_REQUEST["search"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'bank_account',
							'value'   => $_REQUEST["search"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'id_no',
							'value'   => $_REQUEST["search"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'phone_number',
							'value'   => $_REQUEST["search"],
							'compare' => 'LIKE'
						)
					)
				);
			$res_Arr= array();
			$user_query = new WP_User_Query( $args );
			if ( ! empty( $user_query->results ) ) {
				foreach ( $user_query->results as $user ) {
					array_push($res_Arr,$user->ID);	
				}
			}
			if(!empty($res_Arr))
			{
				$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id in(".implode(",",$res_Arr).") limit ".$offset.",".$items_per_page);				
				$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations where user_id in(".implode(",",$res_Arr).")");		
			}
			
		}
		
		//$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations limit ".$offset.",".$items_per_page);		
		//$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations");		

		foreach($all_invitations as $obj_all_invitations)
		{
			$invited_user = get_user_by( 'id', $obj_all_invitations->user_id);
			if(!empty($invited_user)) { 
			/*$invited_users = $wpdb->get_row("select count(*) totals from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id='".$obj_all_invitations->user_id."'");					*/
			$invited_users = $wpdb->get_row($wpdb->prepare("select count(*) totals from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id=%d",$obj_all_invitations->user_id));
			$member_status = get_user_meta($invited_user->ID,"approve_status",true);
		
			if($member_status == "pending" || $member_status == "")
				$member_status = "pending";	
			$first_name = get_user_meta( $invited_user->ID, 'first_name', true);		
			$last_name = get_user_meta( $invited_user->ID, 'last_name', true);		
			$bank_account = get_user_meta( $invited_user->ID, 'bank_account', true );		
			$id_no = get_user_meta( $invited_user->ID, 'id_no', true);		
			$phone_number = get_user_meta( $invited_user->ID, 'phone_number', true);		
			$enable_login = get_user_meta( $invited_user->ID, 'enable_login', true );
			?>
			<tr>
				<td><?php echo $invited_user->display_name; ?></td>
				<td><?php echo date("Y-m-d",strtotime($invited_user->user_registered)); ?></td>
				<td><?php echo $obj_all_invitations->invitation_code; ?></td>
				<td><?php echo $first_name; ?></td>
				<td><?php echo $last_name; ?></td>
				<td><?php echo $bank_account; ?></td>
				<td><?php echo $id_no; ?></td>
				<td><?php echo $phone_number; ?></td>
				<td><?php echo $invited_users->totals; ?></td>
				<td class="<?php echo $invited_user->ID ?>_status"><?php echo strtoupper($member_status); ?></td>
				<td class="<?php echo $invited_user->ID ?>_action">
				<input type="hidden" class="enable_login enable_login_<?php echo $invited_user->ID?>" value="<?php echo $enable_login; ?>" />
				<?php  if($member_status == "pending") { 
					?>
					<a href="javascript:void(0);" data-user-id="<?php echo $invited_user->ID ?>" class="amm_member_status" onclick="amm_member_status(this)">Approve</a>
					<?php
				} else if($enable_login != "yes"){ ?> 
				<a href='javascript:void(0)' class='amm_member_status amm_resend'  onclick="amm_member_status(this)" data-user-id="<?php echo $invited_user->ID ?>">Resend Approval Link</a>
				<?php } ?></td>
				<td>
					<a href="<?php get_edit_user_link( $invited_user->ID ) ?> ">View Profile</a>
				</td>
			</tr>
			<?php
			}
		}
	?>
	</table>
	<?php 
	if(isset($total_all_invitations))
		$total = $total_all_invitations->total;
	else
		$total = 0;
	if(isset($_REQUEST['search']))
		$search = $_REQUEST['search'];
	else 
		$search = '';	
	echo paginate_links( array(
		'base' => add_query_arg( array('cpage'=> '%#%' ,'search'=>$search)),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($total / $items_per_page),
		'current' => $page
	));
	?>
	</div>
	<div class="amm_loader">
		<img src="<?php echo plugins_url( 'amm-loader.gif', __FILE__ ); ?>" />
	</div>
	<script type="text/javascript">
	
		jQuery(".srch_button").click(function(){
			var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
			jQuery.post(
				ajaxurl, 
				{
					'action': 'amm_search_table',
					'keyword': jQuery('#amm_search').val()
				}, 
				function(response){					
					jQuery(".manage_members").html(response);
				}
			);	
			
		});
		
		function amm_member_status(ele) { 
		//jQuery(".amm_member_status").click(function(){		
			//var ele = jQuery(this);
			jQuery(".amm_loader").css("display","block");
			if(jQuery(ele).hasClass("amm_resend"))
			{
				var resend = "yes";
			}
			else 
			{
				var resend = "no";
			}
			var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
			jQuery.post(
				ajaxurl, 
				{
					'action': 'amm_approve_member',
					'user_id': jQuery(ele).attr("data-user-id"),
					'resend' : resend
				}, 
				function(response){
					if(response == "success")
					{
						jQuery("." + jQuery(ele).attr("data-user-id") + "_status").html("APPROVE");
						if(jQuery(".enable_login_" + jQuery(ele).attr("data-user-id")).val() != "yes")
							jQuery("." + jQuery(ele).attr("data-user-id") + "_action").html("<a  onclick='amm_member_status(this)' data-user-id='"+ jQuery(ele).attr("data-user-id") +"' href='javascript:void(0)' class='amm_member_status amm_resend'>Resend Approval Link</a>");
						else
							jQuery("." + jQuery(ele).attr("data-user-id") + "_action").html("");	
						jQuery(".amm_notify").html("Member status changed to approve");
						jQuery(".amm_notify").fadeOut(2500);
					}
					else 
					{
						jQuery(".amm_notify").html("There is some problem to perform this operation");
						jQuery(".amm_notify").fadeOut(2500);
					}
					jQuery(".amm_loader").css("display","none");
				}
			);				
		//});
		}
	</script>
	<?php
}
function amm_conf_settings_function()
{
	if(isset($_REQUEST['amm_config']))
	{
		$settings_Arr = array();
		if($_REQUEST["invitation_limit"] < -1)
			_e("invitation limit can not be less then -1","AMM");
		
		if(isset($_REQUEST["invitation_limit"]) && $_REQUEST["invitation_limit"] != "" && $_REQUEST["invitation_limit"] != 0)
			$settings_Arr["invitation_limit"] = $_REQUEST["invitation_limit"];
		else 
			$settings_Arr["invitation_limit"] = 0;	
		if(isset($_REQUEST["invitation_code_method"]) && $_REQUEST["invitation_code_method"] != "")
			$settings_Arr["invitation_code_method"] = $_REQUEST["invitation_code_method"];
		if(isset($_REQUEST["amm_prefix"]) && $_REQUEST["amm_prefix"] != "")
			$settings_Arr["amm_prefix"] = $_REQUEST["amm_prefix"];
		else 
			$settings_Arr["amm_prefix"] = "";	
		
		if(isset($_REQUEST["amm_mail_content"]) && $_REQUEST["amm_mail_content"] != "")
			$settings_Arr["amm_mail_content"] = $_REQUEST["amm_mail_content"];
		else 
		{
			$content = 'Hi, [username] <br />
			
			User Name: [username]
			Please visit the following link to set your password. <br />
			[activationlink]
			<br /><br />
			Regards,
			Admin
			';
			$settings_Arr["amm_mail_content"] = $content;
		}	
		if(isset($_REQUEST["affiliate_amount"]) && $_REQUEST["affiliate_amount"] != "" && $_REQUEST["affiliate_amount"] != 0)
			$settings_Arr["affiliate_amount"] = $_REQUEST["affiliate_amount"];
		else 
			$settings_Arr["affiliate_amount"] = 0;	
		
		if(isset($_REQUEST["send_register_mail"]) && $_REQUEST["send_register_mail"] != "" )
			$settings_Arr["send_register_mail"] = "yes";
		else 
			$settings_Arr["send_register_mail"] = "no";	
		if(isset($_REQUEST["amm_login_redirect"]) && $_REQUEST["amm_login_redirect"] != "" )
			$settings_Arr["amm_login_redirect"] = $_REQUEST["amm_login_redirect"];
		else 
			$settings_Arr["amm_login_redirect"] = "";	
		if(isset($_REQUEST["amm_register_redirect"]) && $_REQUEST["amm_register_redirect"] != "" )
			$settings_Arr["amm_register_redirect"] = $_REQUEST["amm_register_redirect"];
		else 
			$settings_Arr["amm_register_redirect"] = "";	
		if(isset($_REQUEST["amm_mail_approve_content"]) && $_REQUEST["amm_mail_approve_content"] != "" && $_REQUEST["affiliate_amount"] != 0)
			$settings_Arr["amm_mail_approve_content"] = $_REQUEST["amm_mail_approve_content"];
		else 
		{
			$settings_Arr["amm_mail_approve_content"] =  'Dear [user_name] <br />Thanks for your registration, your account is pending for approval.
			<br />Thanks.';	;	
		}
		update_option("amm-settings",json_encode($settings_Arr));
		 
	}
	$settings_Arr = json_decode(get_option("amm-settings"));
	$invitation_limit = 0;
	
	if(isset( $settings_Arr->invitation_limit) &&  $settings_Arr->invitation_limit != "") 
	{
		$invitation_limit = $settings_Arr->invitation_limit;
	}
	$invitation_code_method = __('radom','AMM');
	if(isset( $settings_Arr->invitation_code_method) && $settings_Arr->invitation_code_method != "") 
	{
		$invitation_code_method = $settings_Arr->invitation_code_method;
	}
	$amm_prefix = "";
	if(isset( $settings_Arr->amm_prefix) && $settings_Arr->amm_prefix != "") 
	{
		$amm_prefix = $settings_Arr->amm_prefix;
	}
	$amm_mail_content = "";
	if(isset( $settings_Arr->amm_mail_content) && $settings_Arr->amm_mail_content != "") 
	{
		$amm_mail_content = $settings_Arr->amm_mail_content;
	}
	$affiliate_amount = "30";
	if(isset( $settings_Arr->affiliate_amount) && $settings_Arr->affiliate_amount != "") 
	{
		$affiliate_amount = $settings_Arr->affiliate_amount;
	}
	if(isset( $settings_Arr->amm_mail_approve_content) && $settings_Arr->amm_mail_approve_content != "") 
	{
		$amm_mail_approve_content = $settings_Arr->amm_mail_approve_content;
	}
	else 
	{
		$amm_mail_approve_content = "";
	}
	if(isset( $settings_Arr->send_register_mail) && $settings_Arr->send_register_mail != "") 
	{
		$send_register_mail = $settings_Arr->send_register_mail;
	}
	else 
	{
		$send_register_mail = "no";
	}
	if(isset( $settings_Arr->amm_login_redirect) && $settings_Arr->amm_login_redirect != "") 
	{
		$amm_login_redirect = $settings_Arr->amm_login_redirect;
	}
	else 
	{
		$amm_login_redirect = "";
	}
	if(isset( $settings_Arr->amm_register_redirect) && $settings_Arr->amm_register_redirect != "") 
	{
		$amm_register_redirect = $settings_Arr->amm_register_redirect;
	}
	else 
	{
		$amm_register_redirect = "";
	}
	
	?>
	<style>
	.config_divs {
		margin: 10px;
	}
	.config_divs label {
		display: block;
		font-weight: bold;
	} 
	.configform .button-large
	{
		margin: 10px;
	}
	.configform h2 {
	    border-bottom: 1px solid black;
		padding-bottom: 10px;
	}	
	</style>
	<form method="post" action="" class="configform">
		<h2>AMM Configuration Settings</h2>
		<div class="config_divs">
			<label><?php _e("Invitation Limit","AMM"); ?></label>				
			<input type="text" name="invitation_limit" value="<?php echo $invitation_limit; ?>" />
		</div>	
		<div class="config_divs">
			<label><?php _e("Send Registration Email","AMM"); ?></label>				
			<input type="checkbox" name="send_register_mail" value="yes" <?php if($send_register_mail == "yes"){ ?> checked <?php } ?> />Send Register Mail
		</div>	
		<div class="config_divs">
			<label><?php _e("Login Redirect Path","AMM"); ?></label>				
			<input type="text" name="amm_login_redirect" value="<?php echo $amm_login_redirect; ?>" />
		</div>
		<div class="config_divs">
			<label><?php _e("Register Redirect Path","AMM"); ?></label>				
			<input type="text" name="amm_register_redirect" value="<?php echo $amm_register_redirect; ?>" />
		</div>		
		<div class="config_divs">
			<label><?php _e("Invitation Code Generate Method","AMM"); ?></label>	
			<input type="radio" name="invitation_code_method" class="invitation_code_method" value="random" <?php if($invitation_code_method == "random" ) { echo "checked"; } ?>/> <?php _e("Random","AMM"); ?>
			<input type="radio" name="invitation_code_method" class="invitation_code_method" value="memberid" <?php if($invitation_code_method == "memberid" ) { echo "checked"; } ?>/> <?php _e("Member ID","AMM"); ?>
			<br />
			<?php if($invitation_code_method == "memberid" ) { ?>
			<div class="amm_prefix" style="display:block" >
			<?php } else { ?>
			<div class="amm_prefix" style="display:none" >
			<?php } ?>			
				<label><?php _e("Prefix","AMM"); ?></label>	
				<input type="text" name="amm_prefix" class="" value="<?php echo $amm_prefix; ?>" />
			</div>
		</div>
		<div class="config_divs">
			<label><?php _e("Mail Content","AMM"); ?></label>		
			<?php 
			$content = 'Hi, [username] <br />
			
			User Name: [username]
			Please visit the following link to set your password. <br />
			[activationlink]
			<br /><br />
			Regards,
			Admin
			';
			if($amm_mail_content != "")
				$content = $amm_mail_content;
			$editor_id = 'amm_mail_content';			
			$settings = array( 'media_buttons' => false );
			wp_editor( $content, $editor_id,$settings );
			?>			
		</div>	
		<div class="config_divs">
			<label><?php _e("Approval Content","AMM"); ?></label>		
			<?php 
			
			$content = 'Dear [user_name] <br />Thanks for your registration, your account is pending for approval.
			<br />Thanks.';	
			if($amm_mail_approve_content != "")
				$content = $amm_mail_approve_content;
			$editor_id = 'amm_mail_approve_content';			
			$settings = array( 'media_buttons' => false );
			wp_editor( $content, $editor_id,$settings );
			?>			
		</div>	
		<div class="config_divs">
			<label><?php _e("Affiliate Amount","AMM"); ?></label>				
			<input type="text" name="affiliate_amount" value="<?php echo $affiliate_amount; ?>" />
		</div>	
		<input type="submit" value="Save" class="button button-primary button-large" name="amm_config"/>
	</form>
	<script type="text/javascript">
		jQuery(".invitation_code_method").change(function(){
			if(jQuery(this).val() == "random")
			{
					jQuery(".amm_prefix").css("display","none");
			}
			else 
			{
					jQuery(".amm_prefix").css("display","block");
			}
		});
	</script>
	<?php
}
function amm_settings_function(){
	?>
	<style>
	.geenrate_admin_activation_code
	{
		margin-top: 15px;
	}
	.geenrate_admin_activation_code label 
	{
		font-weight: bold;
	}
	.generate_activation_code , .save_code
	{
		margin-top: 20px !important;
	}
	
	</style>
	<?php 
		$current_user = wp_get_current_user();
		global $wpdb;
		/*$get_invitation_user_id = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id='".$current_user->ID."'");		*/
		$get_invitation_user_id = $wpdb->get_row(
			$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id=%d",$current_user->ID)
		);
	?>
	<div class="geenrate_admin_activation_code">
		<div class="success_message"></div>
		<label class="invitation_code_lbl"><?php _e(do_shortcode('[amm_invitation_code]'),"AMM") ; ?></label>
		<input type="hidden" class="hidden_invite_code" value="<?php echo $get_invitation_user_id->invitation_code; ?>" />
		<br />
		<input type="button" class="generate_activation_code button button-primary button-large" value="Generate Activation Code" />
		<input type="button" class=" button button-primary button-large save_code" value="Save Code" />
	</div>
		
	
	<script type="text/javascript">
		
		jQuery(".generate_activation_code").click(function(){
			var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
			jQuery.post(
				ajaxurl, 
				{
					'action': 'amm_generate_activation_code',
					'user_id': '<?php echo $current_user->ID; ?>'
				}, 
				function(response){
					jQuery(".invitation_code_lbl").html("Your invitation code : " + response);
					jQuery(".hidden_invite_code").val(response);
				}
			);
		});
		jQuery(".save_code").click(function(){
			var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
			jQuery.post(
				ajaxurl, 
				{
					'action': 'amm_save_code',
					'code': jQuery(".hidden_invite_code").val()
				}, 
				function(response){
					jQuery(".success_message").html(response);
					jQuery(".success_message").fadeOut(2000);
				}
			);
		});
		
	</script>
	<?php
}
/* AMM : End Add plugin setting menu for admin  */


/* AMM : Ajax hook to generate random activation code  */
add_action( 'wp_ajax_amm_generate_activation_code', 'amm_generate_activation_code' );
add_action( 'wp_ajax_nopriv_amm_generate_activation_code', 'amm_generate_activation_code' );

function amm_generate_activation_code() {
	
    echo amm_generate_random_invitation_code($_REQUEST['user_id']);
	exit;
}
/* AMM : End Ajax hook to generate random activation code  */

/* AMM : Ajax hook to generate save activation code  */
add_action( 'wp_ajax_amm_save_code', 'amm_save_code' );
add_action( 'wp_ajax_nopriv_amm_save_code', 'amm_save_code' );

function amm_save_code() {
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$user_invitation_code = $_REQUEST["code"];
	global $wpdb;
	$check_exist = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id='".$user_id."'");			
	if($check_exist == "")
	{
		$update_user_ids = $wpdb->insert( 
				$wpdb->prefix."amm_register_by_invitations", 
				array( 
					'user_id' => $user_id, 
					'invitation_code' => $user_invitation_code
				), 
				array( 
					'%d', 
					'%s' 
				) 
			);
		/*$update_user_ids = $wpdb->query("insert into ".$wpdb->prefix."amm_register_by_invitations(user_id,invitation_code)
									values(".$user_id.",'".$user_invitation_code."')");*/
		_e("Invitation Code Saved Successfully. Please copy the Code","AMM");					
	}
	else 	
	{
		/*$update_user_ids = $wpdb->query("update ".$wpdb->prefix."amm_register_by_invitations set invitation_code= '".$user_invitation_code."' where user_id=".$user_id);*/
		$wpdb->update( 
			$wpdb->prefix."amm_register_by_invitations", 
			array( 
				'invitation_code' =>$user_invitation_code
				
			), 
			array( 'user_id' =>$user_id ), 
			array( 
				'%s'
			), 
			array( '%d' ) 
		);

		/*$update_user_ids = $wpdb->query($wpdb->prepare
			("update ".$wpdb->prefix."amm_register_by_invitations set invitation_code= %s where user_id=%d",$user_invitation_code,$user_id)
			);*/
		_e("Invitation Code Updated Successfully. Please copy the Code","AMM");
	}
	exit;
}
/* AMM : Ajax hook to generate save activation code  */

/* AMM: Front Side Register Form Function and other related functions */
add_shortcode("amm_register_form","amm_register_form_function");
function amm_register_form_function() { 

	if(is_user_logged_in())
	{
		_e("You Are Already Logged in Site. Sorry you can not register <br />","AMM");
	}
	else {
?>	
	<style>
	.notification_rbi {
		border: 1px solid lightgray;
		color: red;
		padding: 10px;
	}	
	</style>
	<?php 
	if(isset($_REQUEST["amm_front_end"]) && $_REQUEST["amm_front_end"] == "amm_front")
	{
		$settings_Arr = json_decode(get_option("amm-settings"));
		if($settings_Arr->send_register_mail == "no") {
			_e("Registration complete.","AMM");
		}
		else {
			_e("Registration complete. Please check your e-mail.","AMM");
		}
		
	}
	$settings_Arr = json_decode(get_option("amm-settings"));
	?>	
	<div class="notification_rbi" style="display: none;"></div>
	<form name="registerform" id="registerform" class="front_registerform" action="" method="post">
		
		<p>
			<label for="user_login"><?php  _e( 'User Name', 'AMM' ) ?><br>
			<input type="text" name="user_login" id="user_login" class="input" value="" size="25"></label>
		</p>
		<p>
			<label for="user_email"><?php  _e( 'E-mail', 'AMM' ) ?><br>
			<input type="text" name="user_email" id="user_email" class="input" value="" size="25"></label>
		</p>
		<?php
		
		if($settings_Arr->send_register_mail == "no") { ?>
		<p>
			<label for="user_password"><?php  _e( 'Password', 'AMM' ) ?><br></label>
			<input type="password" class="amm_register_password" name="user_password" value="" />
			<label for="user_confirm_password"><?php  _e( 'Confirm Password', 'AMM' ) ?><br></label>
			<input type="password" class="amm_register_password2" name="user_password2" value="" />
			<div class="amm_register_password_info"></div>
		</p>
		<?php } ?>
		<?php do_action("register_form"); ?>	
		<p class="cpatach_code_p">
		<label for="captcha_code" class="captach_code_lable"><?php  _e( 'Captcha', 'AMM' ) ?></label>
		<img class="imgcaptcha_code" src="<?php echo site_url(); ?>/wp-content/plugins/advance_member_management/captcha.php?rand=<?php echo substr(rand(),0,4);?>" id='captchaimg'><br>
        <input type="text" value="" name="captcha_code" id="captcha_code" />
		<a href="javascript:void(0)" onclick="refresh_captcha(this)"><?php  _e( 'Refresh Captcha', 'AMM' ) ?></a>
		</p>
		<br class="clear">
		<input type="hidden" name="redirect_to" value="">		
		<input type="hidden" name="amm_front_end" id="amm_front_End" value="amm_front" />
		<p class="submit"><input type="button" name="wp-submit" id="wp-submit" class="front_register button button-primary button-large" value="Register"></p>
	</form>
	<script type="text/javascript">
		function refresh_captcha()
		{
			jQuery(".imgcaptcha_code").detach();
			var random = (parseInt(Math.floor(Math.random() * 100000)));
			var random_new = random.toString();
			jQuery(".captach_code_lable").after('<img class="imgcaptcha_code" src="<?php echo site_url(); ?>/wp-content/plugins/advance_member_management/captcha.php?rand='+ parseInt(random_new.substr(0,4)) +'" id="captchaimg">');
		}
		if(jQuery('.amm_register_password').length > 0) {
			var password1       = jQuery('.amm_register_password'); //id of first password field
			var password2       = jQuery('.amm_register_password2'); //id of second password field
			var passwordsInfo   = jQuery('.amm_register_password_info'); //id of indicator element
		}
		passwordStrengthCheck_register(password1,password2,passwordsInfo); //call password check function
		function passwordStrengthCheck_register(password1, password2, passwordsInfo)
		{
			//Must contain 5 characters or more
			var WeakPass = /(?=.{5,}).*/; 
			//Must contain lower case letters and at least one digit.
			var MediumPass = /^(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
			//Must contain at least one upper case letter, one lower case letter and one digit.
			var StrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
			//Must contain at least one upper case letter, one lower case letter and one digit.
			var VryStrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])(?=\S*?[^\w\*])\S{5,}$/; 
			
			jQuery(password1).on('keyup', function(e) {
				if(VryStrongPass.test(password1.val()))
				{
					passwordsInfo.removeClass().addClass('vrystrongpass').html("Very Strong! (Awesome, please don't forget your pass now!)");
				}   
				else if(StrongPass.test(password1.val()))
				{
					passwordsInfo.removeClass().addClass('strongpass').html("Strong! (Enter special chars to make even stronger");
				}   
				else if(MediumPass.test(password1.val()))
				{
					passwordsInfo.removeClass().addClass('goodpass').html("Good! (Enter uppercase letter to make strong)");
				}
				else if(WeakPass.test(password1.val()))
				{
					passwordsInfo.removeClass().addClass('stillweakpass').html("Still Weak! (Enter digits to make good password)");
				}
				else
				{
					passwordsInfo.removeClass().addClass('weakpass').html("Very Weak! (Must be 5 or more chars)");
				}
			});
			
			jQuery(password2).on('keyup', function(e) {				
				if(password1.val() !== password2.val())
				{
					passwordsInfo.removeClass().addClass('weakpass').html("Passwords do not match!");   
				}else{
					passwordsInfo.removeClass().addClass('goodpass').html("Passwords match!");  
				}
					
			});
		}
							
		function isValidEmailAddress(emailAddress) {
			var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
			return pattern.test(emailAddress);
		}
		jQuery(".front_register").click(function(){
			var error_message = "";
			if(jQuery(".amm_register_password").length > 0) 
			{
				if(jQuery('.amm_register_password').val().length < 5)
					error_message = error_message + "Passowrd Must be greater than 5 characters  <br />";
				if(jQuery('.amm_register_password').val() !== jQuery('.amm_register_password2').val())
					error_message = error_message + "Password and Confirm Password must match  <br />";						
			}
		
			if(jQuery("#user_login").val() == "")
				error_message = error_message + "User Name Can not be null <br />";
			else if (jQuery("#user_login").val().match(/[^a-zA-Z0-9_ ]/g)) 		
				error_message = error_message + "Invlaid User Name <br />";
			
			if(jQuery("#first_name").val() == "")
				error_message = error_message + "First Name Can not be null <br />";
			
			if(jQuery("#bank_account").val().length > 30 || jQuery("#bank_account").val().length < 10)
				error_message = error_message + "Bank Account must be between 10 to 30 characters <br />";
			
			if(jQuery("#id_no").val().length > 50 || jQuery("#id_no").val().length < 10)
				error_message = error_message + "ID number must be between 10 to 50 characters <br />";

			if(jQuery("#phone_number").val().length > 15 || jQuery("#phone_number").val().length < 10)
				error_message = error_message + "Phone number must be between 10 to 15 characters <br />";
			
			if(jQuery("#last_name").val() == "")
				error_message = error_message + "Last Name Can not be null <br />";
			
			
			if(jQuery("#user_email").val() == "")
				error_message = error_message + "User Email Can not be null <br />";
			else if (!isValidEmailAddress(jQuery("#user_email").val())) 		
				error_message = error_message + "Invlaid User Email <br />";	
			
			if(jQuery("#invitation_code").val() == "")
				error_message = error_message + "Invitation Can not be null <br />";
			/*else if(jQuery("#invitation_code").val().match(/[^a-zA-Z0-9_. ]/g))
				error_message = error_message + "Invalid Invitation Code <br />";	*/
			if(error_message == "") {	
				var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
				jQuery.post(
					
					ajaxurl, 
					{
						'action': 'amm_validate_register',
						'user_name': jQuery("#user_login").val(),
						'user_email':jQuery("#user_email").val(),
						'invitation_code':jQuery("#invitation_code").val(),
						'captcha_code': jQuery("#captcha_code").val()
					}, 
					function(response){
						
						error_message = error_message + response;			
						if(error_message != "")
						{
							jQuery(".notification_rbi").html(error_message);	
							jQuery(".notification_rbi").attr("style","display:block");
						}
						else 
						{
							jQuery(".front_registerform").submit();						
						}
					}
				);
			}	
			else if(error_message != "") 
			{
				jQuery(".notification_rbi").html(error_message);	
				jQuery(".notification_rbi").attr("style","display:block");
			}
			
		});
	</script>
<?php 
	}
}
add_action( 'wp_ajax_amm_validate_register', 'amm_validate_register' );
add_action( 'wp_ajax_nopriv_amm_validate_register', 'amm_validate_register' );

function amm_validate_register()
{
	if ( ! session_id() ) {
		session_start();
	} 
	global $wpdb;
	$error_message = "";
	if ( username_exists( $_REQUEST["user_name"] ) )
           $error_message .= "Username Exist <br /> ";
	if(!isset($_REQUEST["captcha_code"]) &&  $_REQUEST["captcha_code"] == "")
			$error_message .= "Captcha Code Is blank <br /> ";
	else if(isset($_SESSION["captcha_code"]) && $_SESSION["captcha_code"] != $_REQUEST["captcha_code"])
			$error_message .= "Captcha Code Not matching <br /> ";
    if ( email_exists($_REQUEST["user_email"] ) )        
           $error_message .= "Email Exist <br />";
	/*$check_invitation_code = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code='".$_POST['invitation_code']."'");		*/
	$check_invitation_code = $wpdb->get_row(
		$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",$_POST['invitation_code'])
	);	
	if($check_invitation_code == "")
	{		
		$error_message .= "Invitation Code is not correct <br />";
	}	
	else 
	{
		$check_member_approve = get_user_meta($check_invitation_code->user_id,"approve_status",true);
		$check_member_approveuser = new WP_User($check_invitation_code->user_id );

		if($check_member_approveuser->roles[0] == "administrator" || ($check_member_approveuser->roles[0] != "administrator" && $check_member_approve == "approve" )) {
			$settings_Arr = json_decode(get_option("amm-settings"));
			if($settings_Arr->invitation_limit == 0)
			{
				$error_message .= "Sorry Registration Disabled By Invitation Code <br />";
			}	
			else 
			{
				if($settings_Arr->invitation_limit != -1)
				{
					$get_invitation_user_id = $wpdb->get_row(
						$wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",
						$_POST['invitation_code']
						)
					)	;	
					$res = get_userdata($get_invitation_user_id->user_id);
					$user_roles_res = $res->roles;
					if(!in_array("advance_member_admin",$user_roles_res))
					{
						if(!in_array("administrator",$user_roles_res))
						{
							if($get_invitation_user_id != "")
							{
								if($get_invitation_user_id->invitation_limit >= $settings_Arr->invitation_limit)
								{
									$error_message .= 'This user has invited '.$get_invitation_user_id->invitation_limit.' people already, and can’t not invite more. Please find another member to invite you';	
								}
							}
						}
					}
				}
			}
		}
		else 
		{
			$error_message .= "Member Is not approved by admin";
		}
	}
	_e ($error_message,"AMM");
	exit;
}
add_action("init","amm_init");
function amm_init()
{	

	global $pagenow;
	if($pagenow == "wp-login.php" && isset($_REQUEST["action"]) && $_REQUEST["action"] == "lostpassword")
	{
		wp_redirect(get_permalink(get_page_by_title("AMM Lost Password")->ID));   exit();
	}
	if(isset($_REQUEST["amm_front_end"]) && $_REQUEST["amm_front_end"] == "amm_front")
	{
		
		global $wpdb;
		$userdata = array(
			'user_login'  =>  $_REQUEST["user_login"],
			'user_email'    =>  $_REQUEST["user_email"],
			'user_pass'   =>  ""  // When creating an user, `user_pass` is expected.
		);

		$user_id = wp_insert_user( $userdata ) ;
	
		if(!is_wp_error($user_id)) {
			update_user_meta( $user_id, 'invitation_code', trim( $_POST['invitation_code'] ) );		
			update_user_meta( $user_id, 'first_name', trim( $_POST['first_name'] ) );		
			update_user_meta( $user_id, 'last_name', trim( $_POST['last_name'] ) );		
			update_user_meta( $user_id, 'bank_account', trim( $_POST['bank_account'] ) );		
			update_user_meta( $user_id, 'id_no', trim( $_POST['id_no'] ) );		
			update_user_meta( $user_id, 'phone_number', trim( $_POST['phone_number'] ) );		
			update_user_meta( $user_id, 'member_status', "pending" );				
			update_user_meta( $user_id, 'approve_status', "pending" );				
			$settings_Arr = json_decode(get_option("amm-settings"));
			if($settings_Arr->send_register_mail == "no") {
				if(isset($_REQUEST['user_password']) && $_REQUEST['user_password'] != "")
				{
					wp_set_password($_REQUEST["user_password"],$user_id);
					update_user_meta( $user_id, 'enable_login', "yes" );						
				}	
			}	
			$get_invitation_user_id = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code='".$_POST['invitation_code']."'");
			
			$user_invitation_code = amm_generate_random_invitation_code($user_id);
			global $wpdb;
			$wpdb->insert( 
				$wpdb->prefix."amm_register_by_invitations", 
				array( 
					'user_id' => $user_id, 
					'invitation_code' => $user_invitation_code
				), 
				array( 
					'%d', 
					'%s' 
				) 
			);
			/*$inseret_user_ids = $wpdb->query("insert into ".$wpdb->prefix."amm_register_by_invitations(user_id,invitation_code) values(".$user_id.",'".$user_invitation_code."')");	*/
			$amm_id = $wpdb->insert_id;								
			$levels_res = $wpdb->get_row($wpdb->prepare(
				"select * from ".$wpdb->prefix."amm_register_by_invitation_users where invited_user_id=%d",$get_invitation_user_id->user_id
			));
			
			if(!empty($levels_res))
			{
				if(!empty($levels_res->levels))
					$level_a = json_decode($levels_res->levels);
				else 
					$level_a = array();
				array_push($level_a,$user_id);
			}
			else 
			{
				$level_a = array();
				array_push($level_a,$get_invitation_user_id->user_id);
				array_push($level_a,$user_id);
			}
			
			$wpdb->insert( 
				$wpdb->prefix."amm_register_by_invitation_users", 
				array( 
					'amm_id' => $get_invitation_user_id->amm_id, 
					'invitee_user_id' => $get_invitation_user_id->user_id,
					'invited_user_id' => $user_id,
					'levels'=>json_encode($level_a)
				), 
				array( 
					'%d', 
					'%d',
					'%d',
					'%s'
				) 
			);
			
			/* Update invitation limit */
			$invitation_limit = $get_invitation_user_id->invitation_limit + 1;
			$wpdb->update( 
				$wpdb->prefix."amm_register_by_invitations", 
				array( 
					'invitation_limit' => $invitation_limit
				), 
				array("amm_id"=>$get_invitation_user_id->amm_id),
				array( 
					'%d'
				) ,
				array("%d")
			);
			if($settings_Arr->send_register_mail == "no") {
				if(isset($_REQUEST['user_password']) && $_REQUEST['user_password'] != "")
				{
				}
			}
			else 			
			{
				$to = $_REQUEST["user_email"];
				$email = get_option('admin_email');
				$sender = get_option('name');
				//$message = 'Dear '.$_REQUEST["user_login"] ."<br />Thanks for your registration, your account is pending for approval.
				//<br />Thanks.";			
				$settings_Arr = json_decode(get_option("amm-settings"));
			
				$message = $settings_Arr->amm_mail_approve_content;
				$message = str_ireplace('[user_name]',$_REQUEST["user_login"],$message);
						
				$headers[] = 'MIME-Version: 1.0' . "\r\n";
				$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers[] = "X-Mailer: PHP \r\n";
				$headers[] = 'From: '.$sender.' < '.$email.'>' . "\r\n";
				$subject = "Thank you for registration";
				add_filter( 'wp_mail_content_type', 'amm_set_content_type' );

				$mail = wp_mail( $to, $subject, $message, $headers );
			}	
			if($settings_Arr->amm_register_redirect != "")
			{			
				?>
				<script type="text/javascript">
				window.location.href = '<?php echo $settings_Arr->amm_register_redirect; ?>';
				</script>
				<?php 
			}
		}
	}
}
function amm_set_content_type( $content_type ) {
	return 'text/html';
}
/* AMM: End Front Side Register Form Function and other related functions */

/* AMM: Display using which invitation code user is registered */
add_shortcode("amm_invited_by","amm_invited_by_function");
function amm_invited_by_function() { 
	if(is_user_logged_in()) {
		global $wpdb;
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		if(isset($current_user->caps["administrator"]))
		{
			_e("You Are Admin <br />","AMM");
		}
		else { 
			$invited_users = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitation_users where invited_user_id='".$user_id."'");					
			if($invited_users != "") {
			$invitation_code_data = $wpdb->get_row("select * from ". $wpdb->prefix."amm_register_by_invitations where amm_id=".$invited_users->amm_id);					

			
			$invitee_data = get_user_by("id",$invited_users->invitee_user_id);
			global $current_user;
		
			if($invitee_data != "")
			printf("You Are Invited By : %s (Invitation Code Used By You : %s)<br />",$invitee_data->display_name,$invitation_code_data->invitation_code);
	/*			_e("You Are Invited By : ".$invitee_data->display_name." (Invitation Code Used By You : ".$invitation_code_data->invitation_code.")<br />","AMM");*/
		}
		else 
		{
			_e("You are added by admin in site","AMM");
		}
		}
	}
	else 
	{
		_e("Please login to view your registration invitation code<br />","AMM");
	}
}
/* AMM: End Display using which invitation code user is registered */

/* AMM: Load plugin text-domain */
add_action( 'plugins_loaded', 'amm_myplugin_load_textdomain' );
function amm_myplugin_load_textdomain() {
  load_plugin_textdomain( 'AMM', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
/* AMM: End Load plugin text-domain */

/* AMM: Add Activation Shortcode */
add_shortcode("amm_activate","amm_activate_function");
function amm_activate_function() { 
	if(!is_user_logged_in()) {
		$get_ID = get_page_by_title("AMM Activate");
		if($get_ID) {
			if(is_page($get_ID->ID))
			{
				if(get_user_meta($_REQUEST["user"],"amm_activation",true) == "" )
				{
					_e("Sorry You already set password using this link previously","AMM");
				}
				else { 
				global $wpdb;
				$check_invitation_code = $wpdb->get_row($wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",$_REQUEST['key']));
				if(!empty($check_invitation_code))
				{
						?>
						<div class="amm_reset_password">
							<div class="amm_reset_notification"></div>
							<label>Reset password</label>
							<input type="password" class="amm_password" name="amm_password" value="" />
							<input type="password" class="amm_password2" name="amm_password2" value="" />
							<div class="amm_password_info"></div>
						</div>
						<input type="button" value="Reset Password" class="amm_reset_btn" />
						<script type="text/javascript">
							jQuery(".amm_reset_btn").click(function(){
								var flag = 1;
								if(jQuery(".amm_password").val() == "")
								{
									flag = 0;
									 jQuery('.amm_password_info').removeClass().addClass('blankpass').html("Password Can not be blank");
								}
								if(jQuery(".amm_password").val() !== jQuery(".amm_password2").val())
								{
									flag = 0;
								}
																
								if(flag == 1) 
								{
									var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
									jQuery.post(
										ajaxurl, 
										{
											'action': 'amm_reset_password',
											'user_id': '<?php echo $_REQUEST["user"]; ?>',
											'new_password':  jQuery('.amm_password').val(),
											'user_current_time': '<?php echo $_REQUEST["timestamp"] ?>'
										}, 
										function(response){
											if(response == "success")
											{
												jQuery(".amm_reset_notification").html("You have reset password successfully..");
												jQuery(".amm_reset_notification").fadeOut(2000,function(){
													window.location.reload();
												});
											}
											else 
											{
												jQuery(".amm_reset_notification").html("Something worng with link..");												
											}
										}
									);
								}									
							});
							jQuery(document).ready(function() {
									var password1       = jQuery('.amm_password'); //id of first password field
									var password2       = jQuery('.amm_password2'); //id of second password field
									var passwordsInfo   = jQuery('.amm_password_info'); //id of indicator element
									
									passwordStrengthCheck(password1,password2,passwordsInfo); //call password check function
									
								});

								function passwordStrengthCheck(password1, password2, passwordsInfo)
								{
									//Must contain 5 characters or more
									var WeakPass = /(?=.{5,}).*/; 
									//Must contain lower case letters and at least one digit.
									var MediumPass = /^(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
									//Must contain at least one upper case letter, one lower case letter and one digit.
									var StrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
									//Must contain at least one upper case letter, one lower case letter and one digit.
									var VryStrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])(?=\S*?[^\w\*])\S{5,}$/; 
									
									jQuery(password1).on('keyup', function(e) {
										if(VryStrongPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('vrystrongpass').html("Very Strong! (Awesome, please don't forget your pass now!)");
										}   
										else if(StrongPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('strongpass').html("Strong! (Enter special chars to make even stronger");
										}   
										else if(MediumPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('goodpass').html("Good! (Enter uppercase letter to make strong)");
										}
										else if(WeakPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('stillweakpass').html("Still Weak! (Enter digits to make good password)");
										}
										else
										{
											passwordsInfo.removeClass().addClass('weakpass').html("Very Weak! (Must be 5 or more chars)");
										}
									});
									
									jQuery(password2).on('keyup', function(e) {
										
										if(password1.val() !== password2.val())
										{
											passwordsInfo.removeClass().addClass('weakpass').html("Passwords do not match!");   
										}else{
											passwordsInfo.removeClass().addClass('goodpass').html("Passwords match!");  
										}
											
									});
								}

						
						</script>
						<?php
				}
				else 
				{
					_e("Link Is not Correct. Please go through your mail","AMM");
				}
			}
			}
			else 
			{
				_e("Something worng with the page","AMM");
			}
		}
		else 
		{
			_e("Something worng with the page","AMM");	
		}
	}
	else 
	{
		_e("You are already logged in site","AMM");
	}
}	
/* AMM: End of Add Activation Shortcode */

/* AMM: Ajax to reset password */
add_action( 'wp_ajax_amm_reset_password', 'amm_reset_password' );
add_action( 'wp_ajax_nopriv_amm_reset_password', 'amm_reset_password' );

function amm_reset_password() {

	if(get_user_meta($_REQUEST["user_id"],"user_current_time",true) == $_REQUEST["user_current_time"])
	{
		update_user_meta($_REQUEST["user_id"],"amm_activation","");
		wp_set_password($_REQUEST["new_password"],$_REQUEST["user_id"]);
		echo "success";
	}
	else	
	{
		echo "fail";
	}
	exit;
}
/* AMM: End of Ajax to reset password */

/* AMM: Ajax to change status of user  */
add_action( 'wp_ajax_amm_approve_member', 'amm_approve_member' );
add_action( 'wp_ajax_nopriv_amm_approve_member', 'amm_approve_member' );

function amm_approve_member() {
	error_reporting(0);
	
	global $wpdb;
	$user_id = $_REQUEST["user_id"];
	$user_invitation_code_res = $wpdb->get_row(
		$wpdb->prepare("select * from ".$wpdb->prefix."amm_register_by_invitations where user_id=%d", $_REQUEST["user_id"])
	);
	if ( class_exists( 'myCRED_AMM_member' ) && class_exists( 'myCRED_Hook' ) )
	{
		$myCRED_AMM_member_obj = new myCRED_AMM_member(array());
		$myCRED_AMM_member_obj->run();
	}
	if(function_exists("affiliate_wp") && $_REQUEST["resend"] == "no")
	{
		$settings_Arr = json_decode(get_option("amm-settings"));
		$default_amount = 30;
		if($settings_Arr->affiliate_amount != "")
		{
			$default_amount = $settings_Arr->affiliate_amount ;
		}
		$AffiliateWP_MLM_Admin = new AffiliateWP_MLM_Admin();
		$level_rates = ($AffiliateWP_MLM_Admin->get_level_rates());
		
		$referalls = get_referral_users($_REQUEST["user_id"] );
		$level_temp_cnt = 0;
		if(!empty($referalls))
		{
			$levels_Arr = json_decode($referalls->levels);
			for($ref_cnt = count($levels_Arr)-1;$ref_cnt >=0;$ref_cnt--)
			{
				$status = affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active';
				if(affiliate_wp()->affiliates->get_by( 'user_id', $levels_Arr[$ref_cnt] ))
				{
					$affiliate_res =affiliate_wp()->affiliates->get_by( 'user_id', $levels_Arr[$ref_cnt] );
					$affiliate_id = $affiliate_res->affiliate_id;
				}	
				else 	
					$affiliate_id = affiliate_wp()->affiliates->add( array( 'user_id' => $levels_Arr[$ref_cnt] ) );	

				
				if($ref_cnt <= count($levels_Arr)-2) 
				{
					$data_arrr = array();
					$data_arr["affiliate_id"] = $affiliate_id;
					$data_arr["amount"] = 0;
					$data_arr["reference"] =  $_REQUEST["user_id"];
					$data_arr["context"] = "";
					$data_arr["status"] = "paid";
					$rate_msg = '';
					if($ref_cnt < count($levels_Arr)-2)
					{
						if(isset($level_rates[$level_temp_cnt]) && $level_rates[$level_temp_cnt] != "")
						{
							
							$amount_Calculated = ($default_amount *  $level_rates[$level_temp_cnt]["rate"]) / 100;
							$rate_msg = $level_rates[$level_temp_cnt]["rate"];
						//	echo "fist".$level_rates[$level_temp_cnt]."===".$amount_Calculated."<br />";
						}
						else 
						{
							$mlm_rate = affiliate_wp()->settings->get( 'affwp_mlm_referral_rate' );
							$amount_Calculated = ($default_amount *  $mlm_rate) / 100;
							$rate_msg = $mlm_rate;							
							//echo "second".$rate_msg."===".$amount_Calculated."<br />";
						}
						$level_temp_cnt = $level_temp_cnt + 1;
					}	
					else 
					{
						$direct_rate = affiliate_wp()->settings->get( 'referral_rate' );
						$amount_Calculated = ($default_amount *  $direct_rate) / 100;
						$rate_msg = $direct_rate;	
						//	echo "thirf".$rate_msg."===".$amount_Calculated."<br />";
					}
					 
					$data_arr["description"] = "New Member Referal (Level ".$level_temp_cnt.") ".$rate_msg."%";
					
					$data_arr["amount"] = $amount_Calculated;
					if($rate_msg != "" || $rate_msg > 0)
					{						
						$add_Ref =  affiliate_wp()->referrals->add($data_arr);	
						//print_r($add_Ref);
						$wpdb->insert( 
							$wpdb->prefix."amm_referaal", 
							array( 
								'user_id' =>  $levels_Arr[$ref_cnt], 
								'referaal_id' => $add_Ref,
								'level'=>"Level ".$level_temp_cnt,
								'level_description'=>$data_arr["description"]
							), 
							array( 
								'%d', 
								'%d',
								'%s',
								'%s'		
							) 
						);		
						if ( class_exists( 'myCRED_AMM_member' ) && class_exists( 'myCRED_Hook' ) )
							do_action("myCRED_AMM_member",$levels_Arr[$ref_cnt] ,$amount_Calculated,$data_arr["description"]);					
					}
				}
			}
			
			for($ref_cnt = count($levels_Arr)-1;$ref_cnt >=1;$ref_cnt--)
			{
				
				$paffiliate_res =affiliate_wp()->affiliates->get_by( 'user_id', $levels_Arr[$ref_cnt-1] );
				$parent_affiliate_id = $paffiliate_res->affiliate_id;
				$caffiliate_res =affiliate_wp()->affiliates->get_by( 'user_id', $levels_Arr[$ref_cnt] );
				$carent_affiliate_id = $caffiliate_res->affiliate_id;
				$affiliate_data = array(
						'affiliate_id'        => $carent_affiliate_id,
						'parent_affiliate_id' => $parent_affiliate_id,
						'direct_affiliate_id' => $parent_affiliate_id
					);
				$result_parent = $wpdb->get_row($wpdb->prepare(
					"select  * from ".$wpdb->prefix."affiliate_wp_mlm_connections where affiliate_id=%d and affiliate_parent_id=%d and direct_affiliate_id=%d",$carent_affiliate_id,$parent_affiliate_id,$parent_affiliate_id
				));
				
				if($result_parent == "") { 
					affwp_mlm_add_affiliate_connections($affiliate_data);				
				}
			}
		}
	}
	
	$settings_Arr = json_decode(get_option("amm-settings"));
	$enable_login = get_user_meta( $user_id, 'enable_login', true );	
	
	if ( $user_id &&  $enable_login != "yes" ) {		
		$user_data = get_user_by("id",$user_id);
		update_user_meta($user_id,"amm_activation","pending");
		$code = sha1( $user_id . time() );
		$current_time = strtotime(date("Y-m-d H:i:s")) ;
		$activation_link = add_query_arg( array( 'key' => $user_invitation_code_res->invitation_code, 'user' => $user_id , 'timestamp'=>$current_time), get_permalink( get_page_by_title("AMM Activate")->ID));
		add_user_meta( $user_id, 'has_to_be_activated', $code, true );
		update_user_meta( $user_id, 'user_current_time', $current_time );
		$userdata = get_user_by("id",$user_id);
		$settings_Arr = json_decode(get_option("amm-settings"));
		
		$content = $settings_Arr->amm_mail_content;
		$content = str_ireplace('[activationlink]',$activation_link,$content);
		$content = str_ireplace('[username]',$userdata->user_login,$content);
		add_filter( 'wp_mail_content_type', 'amm_set_content_type' );
		wp_mail( $user_data->user_email, 'Registration Mail',apply_filters("the_content", $content));
	}
		
	//update_user_meta($_REQUEST["user_id"],"member_status","approve");
	update_user_meta($_REQUEST["user_id"],"approve_status","approve");
	
	if ( is_plugin_active( 'woocommerce_discount/woocommerce_discount.php' ) ) 
	{
		if(function_exists("woo_discount_approve_function"))
			woo_discount_approve_function($user_id);
	}
	ob_get_clean();
	
	echo "success";
	exit;
}
function get_referral_users($user_id)
{
	global $wpdb;
	$result = $wpdb->get_row($wpdb->prepare(
		"select * from ".$wpdb->prefix."amm_register_by_invitation_users where invited_user_id=%d",$user_id
	));
	if(!empty($result))
		return $result;
	else	
		return "";	
}
/* AMM: End of change status of user */

/* AMM : Login Shortcode */
add_shortcode('amm_login', 'amm_login_function'); 
function amm_login_function()
{
	if(is_user_logged_in())
	{
		_e("You Are already Login");
	}
	else { 
	?>
		<form id="ajaxlogin" action="login" method="post"><h3 class="h1"><?php _e('Login To', 'AMM') ?></h3>
			<p class="status"></p>
			<label for="username"><?php _e('User Name', 'AMM'); ?></label> <input id="username" name="username" type="text" >
			<label for="password"><?php _e('Password', 'AMM'); ?></label> <input id="password" name="password" type="password" >
			<br />
			<a href="<?php echo get_permalink(get_page_by_title('AMM Lost Password')->ID); ?>">Forgot Password ?</a>
			<button name="submit" type="submit" value="<?php _e('Login', 'AMM') ?>"><?php _e('Login', 'AMM') ?></button>
			<?php wp_nonce_field( 'ajax-login-nonce', 'amm_security' ); ?>
			
		</form>
		<script type="text/javascript">
		jQuery('form#ajaxlogin').on('submit', function(e){
			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>',
				data: { 
					'action': 'ammajaxlogin', //calls wp_ajax_nopriv_ammajaxlogin
					'username': jQuery('form#ajaxlogin #username').val(), 
					'password': jQuery('form#ajaxlogin #password').val(), 
					'amm_security': jQuery('form#ajaxlogin #amm_security').val() },
					success: function(data){
						jQuery('form#ajaxlogin p.status').html(data.message);
						if (data.loggedin == true){
							if(data.redirect == "")
								window.location.reload();
							else
								window.location.href= data.redirect;
						}
				}
			});
			e.preventDefault();
			return false;
		});

		</script>
	<?php
	}
}
add_action( 'wp_ajax_ammajaxlogin', 'amm_ajax_login' );
add_action( 'wp_ajax_nopriv_ammajaxlogin', 'amm_ajax_login' );

function amm_ajax_login(){

    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-login-nonce', 'amm_security' );

    // Nonce is checked, get the POST data and sign user on
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $settings_Arr = json_decode(get_option("amm-settings"));
		
	$user = get_user_by('login', $_POST['username']);
	if(!empty($user)) {
		if ( $user && wp_check_password( $_POST['password'], $user->data->user_pass, $user->ID) )
		{
			$enable_login = get_user_meta( $user->ID, 'enable_login', true );	
			if(get_user_meta($user->ID,"amm_activation",true) == "pending")
				echo json_encode(array('loggedin'=>false, 'message'=>__('Please activate account.')));
			else if(get_user_meta($user->ID,"approve_status",true) == "pending" && $enable_login != "yes" )
				echo json_encode(array('loggedin'=>true, 'message'=>__('Admin did not approve your account...')));
			else
			{
				$user_signon = wp_signon( $info, false );
				if ( is_wp_error($user_signon) ){
					echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));
				} else {
					wp_set_current_user($user->ID, $_POST['username']);
					wp_set_auth_cookie($user->ID);
					do_action('wp_login', $_POST['username']);
					$settings_Arr = json_decode(get_option("amm-settings"));
		
					if(isset( $settings_Arr->amm_login_redirect) && $settings_Arr->amm_login_redirect != "") 
					{
						$amm_login_redirect = $settings_Arr->amm_login_redirect;
					}
					else 
					{
						$amm_login_redirect = home_url();
					}
		
					echo json_encode(array('loggedin'=>true, 'redirect'=>$amm_login_redirect , 'message'=>__('Login successful, redirecting...')));
				}
			}
		} else {
			echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));
		}
	}
	else 
	{
		echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));		
	}
    die();
}
/* AMM : End of Login Shortcode */

/* AMM : Shortcode for lost password  */
add_shortcode('amm_lost_password', 'amm_lost_password_function'); 
function amm_lost_password_function()
{
	 if( isset( $_POST['action'] ) && 'reset' == $_POST['action'] ) 
        {
            $email = trim($_POST['user_login']);
            
            if( empty( $email ) ) {
                $error = 'Enter a username or e-mail address..';
            } else if( ! is_email( $email )) {
                $error = 'Invalid username or e-mail address.';
            } else if( ! email_exists( $email ) ) {
                $error = 'There is no user registered with that email address.';
            } else {
                
                $random_password = wp_generate_password( 12, false );
                $user = get_user_by( 'email', $email );
                $member_status = get_user_meta($user->ID,"approve_status",true);
				if($member_status == "pending" || $member_status == "")
				{
					$error = 'Member is not approved yet ';
				}
				else {
					$update_user = wp_update_user( array (
							'ID' => $user->ID, 
							'user_pass' => $random_password
						)
					);
					if( $update_user ) {
						$to = $email;
						$subject = 'Your new password link';
						$sender = get_option('name');
						$current_time = strtotime(date("Y-m-d H:i:s"));
						global $wpdb;
						$user_invitation_code_res = $wpdb->get_row(
							$wpdb->prepare("select * from ".$wpdb->prefix."amm_register_by_invitations where user_id=%d",$user->ID )
						);
						$activation_link = '<a href="'.get_permalink( get_page_by_title("AMM Set Password")->ID)."?key=".$user_invitation_code_res->invitation_code."&user=".$user->ID ."&timestamp=".$current_time.'">Reset Password</a>';
//						$activation_link = add_query_arg( array( 'key' => $user_invitation_code_res->invitation_code, 'user' => $user->ID , 'timestamp'=>$current_time), get_permalink( get_page_by_title("AMM Set Password")->ID));
						$message = 'Your password reset link : '.$activation_link;						
						$headers[] = 'MIME-Version: 1.0' . "\r\n";
						$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers[] = "X-Mailer: PHP \r\n";
						$headers[] = 'From: '.$sender.' < '.$email.'>' . "\r\n";
						add_filter( 'wp_mail_content_type', 'amm_set_content_type' );
		
						$mail = wp_mail( $to, $subject, $message, $headers );
						if( $mail )
						{
							$success = 'Check your email address for you new password.';
						}					
						update_user_meta($user->ID,"lost_user_current_time",$current_time);
						update_user_meta($user->ID,"amm_lost_password","pending");
							
					} else {
						$error = 'Oops something went wrong updaing your account.';
					}
				}
            }
            
            if( ! empty( $error ) )
                echo '<div class="message"><p class="error"><strong>ERROR:</strong> '. $error .'</p></div>';
            
            if( ! empty( $success ) )
                echo '<div class="error_login"><p class="success">'. $success .'</p></div>';
        }
	?>
	  <form method="post">
        <fieldset>
            <p>Please enter your username or email address. You will receive a link to create a new password via email.</p>
            <p><label for="user_login">E-mail:</label>
                <?php $user_login = isset( $_POST['user_login'] ) ? $_POST['user_login'] : ''; ?>
                <input type="text" name="user_login" id="user_login" value="<?php echo $user_login; ?>" /></p>
            <p>
                <input type="hidden" name="action" value="reset" />
                <input type="submit" value="Get New Password" class="button" id="submit" />
            </p>
        </fieldset> 
    </form>
	<?php 
}
/* AMM : End Shortcode for lost password  */

/* AMM : Change Lost Password URL  */
function amm_custom_login_lostpassword_url()
{    
    return get_permalink(get_page_by_title("AMM Lost Password")->ID);
}
add_filter("lostpassword_url", "amm_custom_login_lostpassword_url");
/* AMM : End Change Lost Password URL  */

/* AMM: Shortcode for New password with lost password */
add_shortcode("amm_set_new_password","amm_set_new_password_function");
function amm_set_new_password_function() { 
	if(!is_user_logged_in()) {
		$get_ID = get_page_by_title("AMM Set Password");
		if($get_ID) {
			if(is_page($get_ID->ID))
			{
				
				if(get_user_meta($_REQUEST["user"],"amm_lost_password",true) == "" )
				{
					_e("Sorry You already set password using this link previously","AMM");
				}
				else { 
				global $wpdb;
				$check_invitation_code = $wpdb->get_row($wpdb->prepare("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code=%s",$_REQUEST['key']));
				if(!empty($check_invitation_code))
				{
						?>
						<div class="amm_reset_password">
							<div class="amm_reset_notification"></div>
							<label>Reset password</label>
							<input type="password" class="amm_password" name="amm_password" value="" />
							<input type="password" class="amm_password2" name="amm_password2" value="" />
							<div class="amm_password_info"></div>
						</div>
						<input type="button" value="Reset Password" class="amm_reset_btn" />
						<script type="text/javascript">
							jQuery(".amm_reset_btn").click(function(){
								var flag = 1;
								if(jQuery(".amm_password").val() == "")
								{
									flag = 0;
									 jQuery('.amm_password_info').removeClass().addClass('blankpass').html("Password Can not be blank");
								}
								if(jQuery(".amm_password").val() !== jQuery(".amm_password2").val())
								{
									flag = 0;
								}
																
								if(flag == 1) 
								{
									var ajaxurl = '<?php _e(admin_url('admin-ajax.php'),"AMM"); ?>';
									jQuery.post(
										ajaxurl, 
										{
											'action': 'amm_lost_password',
											'user_id': '<?php echo $_REQUEST["user"]; ?>',
											'new_password':  jQuery('.amm_password').val(),
											'user_current_time': '<?php echo $_REQUEST["timestamp"] ?>'
										}, 
										function(response){
											if(response == "success")
											{
												jQuery(".amm_reset_notification").html("You have reset password successfully..");
												jQuery(".amm_reset_notification").fadeOut(2000,function(){
													window.location.reload();
												});
											}
											else 
											{
												jQuery(".amm_reset_notification").html("Something worng with link..");												
											}
										}
									);
								}									
							});
							jQuery(document).ready(function() {
									var password1       = jQuery('.amm_password'); //id of first password field
									var password2       = jQuery('.amm_password2'); //id of second password field
									var passwordsInfo   = jQuery('.amm_password_info'); //id of indicator element
									
									passwordStrengthCheck(password1,password2,passwordsInfo); //call password check function
									
								});

								function passwordStrengthCheck(password1, password2, passwordsInfo)
								{
									//Must contain 5 characters or more
									var WeakPass = /(?=.{5,}).*/; 
									//Must contain lower case letters and at least one digit.
									var MediumPass = /^(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
									//Must contain at least one upper case letter, one lower case letter and one digit.
									var StrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
									//Must contain at least one upper case letter, one lower case letter and one digit.
									var VryStrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])(?=\S*?[^\w\*])\S{5,}$/; 
									
									jQuery(password1).on('keyup', function(e) {
										if(VryStrongPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('vrystrongpass').html("Very Strong! (Awesome, please don't forget your pass now!)");
										}   
										else if(StrongPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('strongpass').html("Strong! (Enter special chars to make even stronger");
										}   
										else if(MediumPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('goodpass').html("Good! (Enter uppercase letter to make strong)");
										}
										else if(WeakPass.test(password1.val()))
										{
											passwordsInfo.removeClass().addClass('stillweakpass').html("Still Weak! (Enter digits to make good password)");
										}
										else
										{
											passwordsInfo.removeClass().addClass('weakpass').html("Very Weak! (Must be 5 or more chars)");
										}
									});
									
									jQuery(password2).on('keyup', function(e) {
										
										if(password1.val() !== password2.val())
										{
											passwordsInfo.removeClass().addClass('weakpass').html("Passwords do not match!");   
										}else{
											passwordsInfo.removeClass().addClass('goodpass').html("Passwords match!");  
										}
											
									});
								}

						
						</script>
						<?php
				}
				else 
				{
					_e("Link Is not Correct. Please go through your mail","AMM");
				}
			}
			}
			else 
			{
				_e("Something worng with the page","AMM");
			}
		}
		else 
		{
			_e("Something worng with the page","AMM");	
		}
	}
	else 
	{
		_e("You are already logged in site","AMM");
	}
}
/* AMM: End Shortcode for New password with lost password */

/* AMM: Ajax to set lost password for user  */
add_action( 'wp_ajax_amm_lost_password', 'amm_lost_password' );
add_action( 'wp_ajax_nopriv_amm_lost_password', 'amm_lost_password' );

function amm_lost_password() {

	if(get_user_meta($_REQUEST["user_id"],"lost_user_current_time",true) == $_REQUEST["user_current_time"])
	{
		update_user_meta($_REQUEST["user_id"],"amm_lost_password","");
		wp_set_password($_REQUEST["new_password"],$_REQUEST["user_id"]);
		echo "success";
	}
	else	
	{
		echo "fail";
	}
	exit;
}	
/* AMM: End of Ajax to set lost password for user  */


/* AMM: Shortcode for Profile */
add_shortcode("amm_profile","amm_profile_function");
function amm_profile_function() { 
	$current_user = wp_get_current_user();
	if(isset($_REQUEST["amm_front_end_profile"]) && $_REQUEST["amm_front_end_profile"] == "amm_front_profile")
	{
		$user_id = $_REQUEST["user_profile_id"];
		update_user_meta( $user_id, 'first_name', trim( $_POST['first_name'] ) );		
		update_user_meta( $user_id, 'last_name', trim( $_POST['last_name'] ) );		
		update_user_meta( $user_id, 'bank_account', trim( $_POST['bank_account'] ) );		
		update_user_meta( $user_id, 'id_no', trim( $_POST['id_no'] ) );		
		update_user_meta( $user_id, 'phone_number', trim( $_POST['phone_number'] ) );								
		if($_REQUEST["amm_password"] != "" && isset($_REQUEST["amm_password"]))
			wp_set_password($_REQUEST["amm_password"],$user_id);
	}		
	global $wpdb;
	if(is_user_logged_in())
	{
?>
	<style>
		.notification_rbi {
			border: 1px solid lightgray;
			color: red;
			padding: 10px;
		}	
		</style>
	<div class="notification_rbi" style="display: none;"></div>
		
	<form name="registerform" id="registerform" class="front_registerform" action="" method="post">
		<input type="hidden" name="user_profile_id" id="user_profile_id" class="input" value="<?php echo $current_user->ID ?>" >
		
		<p>
			<label for="user_login"><?php  _e( 'User Name', 'AMM' ) ?><br>
			<input type="text" name="user_login" id="user_login" class="input" value="<?php echo $current_user->user_login ?>" size="25" disabled></label>
		</p>
		<p>
			<label for="user_email"><?php  _e( 'E-mail', 'AMM' ) ?><br>
			<input type="text" name="user_email" id="user_email" class="input" value="<?php echo $current_user->user_email ?>" size="25" disabled></label>
		</p>
		<?php do_action("register_form"); ?>	
		
		<br class="clear">
		
		<label>Reset password (keep this blank to do not change)</label>
		<input type="password" class="amm_password" name="amm_password" value="" />
		<input type="password" class="amm_password2" name="amm_password2" value="" />
		<div class="amm_password_info"></div>
							
		<input type="hidden" name="redirect_to" value="">		
		<input type="hidden" name="amm_front_end_profile" id="amm_front_End_profile" value="amm_front_profile" />
		<p class="submit"><input type="button" name="wp-submit" id="wp-submit" class="front_register_profile button button-primary button-large" value="Update"></p>
	</form>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		var password1       = jQuery('.amm_password'); //id of first password field
		var password2       = jQuery('.amm_password2'); //id of second password field
		var passwordsInfo   = jQuery('.amm_password_info'); //id of indicator element
		
		passwordStrengthCheck(password1,password2,passwordsInfo); //call password check function
		
	});

	function passwordStrengthCheck(password1, password2, passwordsInfo)
	{
		//Must contain 5 characters or more
		var WeakPass = /(?=.{5,}).*/; 
		//Must contain lower case letters and at least one digit.
		var MediumPass = /^(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
		//Must contain at least one upper case letter, one lower case letter and one digit.
		var StrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])\S{5,}$/; 
		//Must contain at least one upper case letter, one lower case letter and one digit.
		var VryStrongPass = /^(?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9])(?=\S*?[^\w\*])\S{5,}$/; 
		
		jQuery(password1).on('keyup', function(e) {
			if(VryStrongPass.test(password1.val()))
			{
				passwordsInfo.removeClass().addClass('vrystrongpass').html("Very Strong! (Awesome, please don't forget your pass now!)");
			}   
			else if(StrongPass.test(password1.val()))
			{
				passwordsInfo.removeClass().addClass('strongpass').html("Strong! (Enter special chars to make even stronger");
			}   
			else if(MediumPass.test(password1.val()))
			{
				passwordsInfo.removeClass().addClass('goodpass').html("Good! (Enter uppercase letter to make strong)");
			}
			else if(WeakPass.test(password1.val()))
			{
				passwordsInfo.removeClass().addClass('stillweakpass').html("Still Weak! (Enter digits to make good password)");
			}
			else
			{
				passwordsInfo.removeClass().addClass('weakpass').html("Very Weak! (Must be 5 or more chars)");
			}
		});
		
		jQuery(password2).on('keyup', function(e) {
			
			if(password1.val() !== password2.val())
			{
				passwordsInfo.removeClass().addClass('weakpass').html("Passwords do not match!");   
			}else{
				passwordsInfo.removeClass().addClass('goodpass').html("Passwords match!");  
			}
				
		});
	}

		jQuery(".front_register_profile").click(function(){
			var error_message = "";
			var password1       = jQuery('.amm_password'); //id of first password field
			var password2       = jQuery('.amm_password2'); //id of second password field
			var passwordsInfo   = jQuery('.amm_password_info'); //id of indicator element
									
			if(password1.val() !== password2.val())
				error_message = error_message + "Password not matching <br />";
									
			if(jQuery("#first_name").val() == "")
				error_message = error_message + "First Name Can not be null <br />";
			
			if(jQuery("#bank_account").val().length > 30 || jQuery("#bank_account").val().length < 10)
				error_message = error_message + "Bank Account must be between 10 to 30 characters <br />";
			
			if(jQuery("#id_no").val().length > 50 || jQuery("#id_no").val().length < 10)
				error_message = error_message + "ID number must be between 10 to 50 characters <br />";

			if(jQuery("#phone_number").val().length > 15 || jQuery("#phone_number").val().length < 10)
				error_message = error_message + "Phone number must be between 10 to 15 characters <br />";
			
			if(jQuery("#last_name").val() == "")
				error_message = error_message + "Last Name Can not be null <br />";
					
			if(error_message == "") {	
				jQuery(".front_registerform").submit();			
			}	
			else if(error_message != "") 
			{
				jQuery(".notification_rbi").html(error_message);	
				jQuery(".notification_rbi").attr("style","display:block");
			}
			
		});
	</script>
<?php 
	}
	else 
	{
		_e("Please do login to Edit profile");
	}
}
/* AMM: End Shortcode for Profile */

/* AMM: Ajax to filter Data */
add_action( 'wp_ajax_amm_search_table', 'amm_search_table' );
add_action( 'wp_ajax_nopriv_amm_search_table', 'amm_search_table' );

function amm_search_table() {
		global $wpdb;
	?>
		<table class="all_invitations" id="amm_tblData">
		<tr>
			<th><?php _e("User Name","AMM"); ?></th>
			<th><?php _e("Registered Date","AMM"); ?></th>
			<th><?php _e("Invitation Code","AMM"); ?></th>
			<th><?php _e("First Name","AMM"); ?></th>
			<th><?php _e("Last Name","AMM"); ?></th>
			<th><?php _e("Bank Account Number","AMM"); ?></th>
			<th><?php _e("ID No","AMM"); ?></th>
			<th><?php _e("Phone Number","AMM"); ?></th>
			<th><?php _e("Total Registered Users","AMM"); ?></th>
			<th><?php _e("Status","AMM"); ?></th>
			<th><?php _e("Action","AMM"); ?></th>
			<th></th>
		</tr>
	<?php 
		$items_per_page = 5;
		$page = 1;
		$offset = ( $page * $items_per_page ) - $items_per_page;
		if($_REQUEST["keyword"] == "")
		{
			$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations limit ".$offset.",".$items_per_page);				
			$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations");		
		}
		else 			
		{
			$args = array(
						'meta_query' => array(
						'relation'=>'OR',
						array(
							'key'     => 'first_name',
							'value'   => $_REQUEST["keyword"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'last_name',
							'value'   => $_REQUEST["keyword"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'bank_account',
							'value'   => $_REQUEST["keyword"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'id_no',
							'value'   => $_REQUEST["keyword"],
							'compare' => 'LIKE'
						),
						array(
							'key'     => 'phone_number',
							'value'   => $_REQUEST["keyword"],
							'compare' => 'LIKE'
						)
					)
				);
			$res_Arr= array();
			$user_query = new WP_User_Query( $args );
			
			if ( ! empty( $user_query->results ) ) {
				foreach ( $user_query->results as $user ) {
					array_push($res_Arr,$user->ID);	
				}
			}
			
			if(!empty($res_Arr))
			{
				$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations where user_id in(".implode(",",$res_Arr).") limit ".$offset.",".$items_per_page);				
				$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations where user_id in(".implode(",",$res_Arr).")");		
			}
			else 
			{
				$all_invitations = $wpdb->get_results("select * from ". $wpdb->prefix."amm_register_by_invitations where invitation_code like '%".$_REQUEST["keyword"]."%' limit ".$offset.",".$items_per_page);				
				$total_all_invitations = $wpdb->get_row("select count(*) total from ". $wpdb->prefix."amm_register_by_invitations where invitation_code like '%".$_REQUEST["keyword"]."%'");				
			}
			
		}
		foreach($all_invitations as $obj_all_invitations)
		{
			$invited_user = get_user_by( 'id', $obj_all_invitations->user_id);
			if(!empty($invited_user)) { 
			/*$invited_users = $wpdb->get_row("select count(*) totals from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id='".$obj_all_invitations->user_id."'");					*/
			$invited_users = $wpdb->get_row($wpdb->prepare("select count(*) totals from ". $wpdb->prefix."amm_register_by_invitation_users where invitee_user_id=%d",$obj_all_invitations->user_id));
			$member_status = get_user_meta($invited_user->ID,"approve_status",true);
		
			if($member_status == "pending" || $member_status == "")
				$member_status = "pending";	
			$first_name = get_user_meta( $invited_user->ID, 'first_name', true);		
			$last_name = get_user_meta( $invited_user->ID, 'last_name', true);		
			$bank_account = get_user_meta( $invited_user->ID, 'bank_account', true );		
			$id_no = get_user_meta( $invited_user->ID, 'id_no', true);		
			$phone_number = get_user_meta( $invited_user->ID, 'phone_number', true);		
			
			?>
			<tr>
				<td><?php echo $invited_user->display_name; ?></td>
				<td><?php echo date("Y-m-d",strtotime($invited_user->user_registered)); ?></td>
				<td><?php echo $obj_all_invitations->invitation_code; ?></td>
				<td><?php echo $first_name; ?></td>
				<td><?php echo $last_name; ?></td>
				<td><?php echo $bank_account; ?></td>
				<td><?php echo $id_no; ?></td>
				<td><?php echo $phone_number; ?></td>
				<td><?php echo $invited_users->totals; ?></td>
				<td class="<?php echo $invited_user->ID ?>_status"><?php echo strtoupper($member_status); ?></td>
				<td class="<?php echo $invited_user->ID ?>_action"><?php  if($member_status == "pending") { 
					?>
					<a href="javascript:void(0);" data-user-id="<?php echo $invited_user->ID ?>" class="amm_member_status" onclick="amm_member_status(this)">Approve</a>
					<?php
				} else { ?> 
				<a href='javascript:void(0)' class='amm_member_status amm_resend'  onclick="amm_member_status(this)" data-user-id="<?php echo $invited_user->ID ?>">Resend Approval Link</a>
				<?php } ?></td>
				<td>
					<a href="<?php get_edit_user_link( $invited_user->ID ) ?> ">View Profile</a>
				</td>
			</tr>
			<?php
			}
		}
	?>
	</table>
	<?php 
	if(!empty($all_invitations)) 
	{
		if(isset($total_all_invitations))
			$total = $total_all_invitations->total;
		else
			$total = 0;
		if(isset($_REQUEST['keyword']))
			$search = $_REQUEST['keyword'];
		else 
			$search = '';	
		echo paginate_links( array(
			'base' => add_query_arg( array('page'=>'amm-manage-memberes','cpage'=> '%#%' ,'search'=>$search),site_url().'/wp-admin/admin.php'),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => ceil($total / $items_per_page),
			'current' => $page
		));
	}
	exit;
}
/* AMM: End Ajax to filter Data */

add_action("init","creds_details");
function register_amm_mycred_hook( $installed ) {

	$installed['amm_approve_member'] = array(
		'title'       => __( 'AMM Approve Member', 'mycred' ),
		'description' => __( 'Awards %_plural% for amm member approve.', 'mycred' ),
		'callback'    => array( 'myCRED_AMM_member' )
	);

	return $installed;

}
function creds_details() { 
add_filter( 'mycred_setup_hooks', 'register_amm_mycred_hook' );


/**
 * Load Schreikasten Hook
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_AMM_member' ) && class_exists( 'myCRED_Hook' ) ) :

	/**
	 * The Custom Hook Class
	 * Built of the abstract myCRED_Hook class, we only need to define
	 * a contruct, a run method, hook execution methods and if needed settings
	 * and setting sanitation.
	 * @version 1.0
	 */
	class myCRED_AMM_member extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = 'mycred_default' ) {

			parent::__construct( array(
				'id'       => 'amm_approve_member',
				'defaults' => array(
						'creds'  => 0,
						'log'    => '%plural% for amm_member',
						'limit'  => '0'
					)
				
			), $hook_prefs, $type );

		}

		public function run() {

			add_action( 'myCRED_AMM_member',    array( $this, 'myCRED_AMM_member_function' ) ,10,3);
		}

		/**
		 * New Comment
		 * @version 1.0
		 */
		public function myCRED_AMM_member_function( $user_id , $amount ,$description ) {
			$this->core->add_creds(
				'amm_approve_member',
				$user_id,
				$amount,
				$description
			);
		}


	}

endif;
}

/* AMM: Shortcode for User Name */
add_shortcode("amm_user_name","amm_user_name_function");
function amm_user_name_function() { 
	if(is_user_logged_in())
	{
		global $current_user;
		printf( __( "<br />You are logged in as  : %s", 'AMM' ), $current_user->user_login);
		
	}
	else 
	{
		_e("You are not logged in");
	}
}
/* AMM: End Shortcode for User Name */

/* AMM: Shortcode for Referaal List */
add_shortcode("amm_referaal_list","amm_referaal_list_function");
function amm_referaal_list_function() { 
	if(is_user_logged_in())
	{
		global $current_user;
		$mlm_affiliate_filename =  str_replace("\\","/",WP_PLUGIN_DIR)  ."/affiliatewp-multi-level-marketing/includes/class-admin.php";
		if(file_exists($mlm_affiliate_filename))
		{
			include_once($mlm_affiliate_filename);
			if(class_exists("AffiliateWP_MLM_Admin"))
			{
				$AffiliateWP_MLM_Admin = new AffiliateWP_MLM_Admin();
				$level_rates = ($AffiliateWP_MLM_Admin->get_level_rates());
				
				global $wpdb;
				$count_rate = 1;
				?>
				<table>
					<tr>
						<th>Level</th>
						<th>Count</th>
					</tr>
					<?php 
					$settings_Arr = json_decode(get_option("amm-settings"));							
					$invitation_limit = $settings_Arr->invitation_limit;
					$level_result = $wpdb->get_results(
								$wpdb->prepare("select * from ".$wpdb->prefix."amm_referaal where level=%s and user_id=%d","Level 0",$current_user->ID)
					);
					
					?>
					<tr>
							<td>Level 0 (Direct Referal)</td>
							<td>
							<?php 
							if(count($level_result) > 0)
							{
								echo count($level_result) ."/".pow($invitation_limit , 1);
							}
							else 
							{
								echo "0/".pow($invitation_limit , 1);
							}
							?>
						</td>
					</tr>
					<?php
					for($level_count=0;$level_count < count($level_rates); $level_count++)
					{
							$level_result = $wpdb->get_results(
								$wpdb->prepare("select * from ".$wpdb->prefix."amm_referaal where level=%s and user_id=%d","Level ".($level_count+1),$current_user->ID)
							);
							?>
							<tr>
							<td><?php echo "Level ".($level_count + 1); ?></td>
							<td>
							<?php 
							if(count($level_result) > 0)
							{
								echo count($level_result) ."/".pow($invitation_limit ,($level_count+2));
							}
							else 
							{
								echo "0/".pow($invitation_limit ,($level_count+2));
							}
							?>
							</td>
							</tr>
							<?php
					}
					?>
				</table>
				<?php
			}
		}
		else 
		{
			_e("Affilaite MLM is not installed");
		}
	}
	else 
	{
		_e("Please do login to view the list");
	}
}
/* AMM: End Shortcode for Referaal List */