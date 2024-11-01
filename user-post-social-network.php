<?php
/*
Plugin Name: User Post On Social Network
Description: Visitor can submit post on your website and on social network.
Version: 1.0
Author: Sajid Sayyad
License: GPL2
*/

ob_start();

require_once('upsn-functions.php');

add_action( 'admin_menu', 'upsn_menu' );

/* enqueue the scripts */
function upsn_scripts() {
	/* get server file size limit */
	$maxFileSize = upsnconvertBytes( ini_get( 'upload_max_filesize' ) );
	?>
	<script type='text/javascript'>var allowedFileSize = '<?php echo $maxFileSize; ?>';</script>
	<?php
	// Load jQuery/Css
	wp_enqueue_script('jquery');
	wp_enqueue_script('validate-script', plugins_url( '/js/jquery.validate.js' , __FILE__ ) );
	wp_enqueue_script( 'upsn-script', plugins_url( '/js/upsn_scripts.js' , __FILE__ ) );
	wp_enqueue_style( 'upsn-style', plugins_url( '/css/upsn_style.css' , __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'upsn_scripts' );

function upsn_menu() {
	add_options_page( 'upsn Options', 'User Post On Social Network', 'manage_options', 'user-post-social-network', 'upsn_plugin_options' );
}

function upsn_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	//save the app_info in the wp_options table
	if(isset($_POST['upsn_admin_submit_btn'])) {
		$option_name = 'upsn_app_info' ;
		$new_value = $_POST['appid'].' '.$_POST['appsecret'] ;

		if ( get_option( $option_name ) != $new_value ) {
			update_option( $option_name, $new_value );
		} else {
			$deprecated = ' ';
			$autoload = 'no';
			add_option( $option_name, $new_value, $deprecated, $autoload );
		}

		echo '<div class="updated settings-error" id="setting-error-settings_updated"> 
		<p><strong>Settings saved.</strong></p></div>';
	}

	//fetch app info if it already exists in table
	$app_info = explode( " ", get_option('upsn_app_info') );
	?>
	<div class="wrap">
		<div class='icon32' id='icon-options-general'><br></div>
		<h2>User Post On Social Network</h2>
		<form method='post'>
			<table class='form-table'>
				<tbody>
					<tr valign='top'>
						<th scope='row'>
							<label for='appid'>Facebook App Id</label>
						</th>
						<td>
							<input type='text' class='regular-text' id='appid' name='appid' value='<?php echo $app_info[0]; ?>'>
						</td>
					</tr>
					<tr valign='top'>
						<th scope='row'>
							<label for='appsecret'>Facebook App Secret</label>
						</th>
						<td>
							<input type='text' class='regular-text' id='appsecret' name='appsecret' value='<?php echo $app_info[1]; ?>'>
						</td>
					</tr>
				</tbody>	
			</table>
			<p class='submit'>
				<input id='submit' class='button-primary' type='submit' value='Save Changes' name='upsn_admin_submit_btn'>
			</p>
		</form>
	</div>
	<p>Please add the shortcode [upsn] in your post/page or you can add this line to use the plugin <code>if( function_exists(userpostsocialnetwork) ) { userpostsocialnetwork(); }</code> in your page template.</p>
	<?php
}

function userpostsocialnetwork() {
	//here we start the session to store the code return by facebook
	session_start();

	//fetch app info from wp_options table
	$app_info = explode( " ", get_option('upsn_app_info') );

	$app_id = $app_info[0];
	$app_secret = $app_info[1];
	$post_login_url = upsn_current_page_url();

	$find_code_in_url = strpos($post_login_url, "?code");

	if($find_code_in_url) {
		$post_login_url = substr($post_login_url, 0, ($find_code_in_url));
	}

	//get the code parameter from the url
	if(isset($_REQUEST["code"])) {
		//store the code return by facebook in session variable
		$_SESSION['code'] = $_REQUEST['code'];
	}

	//Obtain the access_token with publish_stream permission
	if( empty($_SESSION['code']) ) {
		$dialog_url= "http://www.facebook.com/dialog/oauth?"
		. "client_id=" .  $app_id 
		. "&redirect_uri=" . urlencode( $post_login_url)
		.  "&scope=publish_stream";

		//login image path
		$login_img =  '<img src="' .plugins_url( 'images/fb.jpeg' , __FILE__ ). '" > ';

		echo "<div id='content'> Login to submit post <a href='$dialog_url'>$login_img</a> </div>";
	}
	else {
		if(empty($_SESSION['access_token'])) {
			set_time_limit(0);
			$token_url="https://graph.facebook.com/oauth/access_token?"
			. "client_id=" . $app_id 
			. "&redirect_uri=" . urlencode( $post_login_url)
			. "&client_secret=" . $app_secret
			."&code=".$_SESSION['code'];

			$c = curl_init();
			if(!(is_ssl())) {
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			}
	        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($c, CURLOPT_URL, $token_url);
	        $response = curl_exec($c);
	        if($response == false) {
	        	echo 'Curl error: ' . curl_error($c);
	        }
	        curl_close($c);

			$params = null;
			parse_str($response, $params);
			$_SESSION['access_token'] = $params['access_token'];
		}

		// Show photo upload form to user and post to the Graph URL
		$graph_url= "https://graph.facebook.com/me/photos?"
		. "access_token=" .$_SESSION['access_token'];
		?>
		<?php
		//upload a photo to facebook using curl
		if(isset($_POST['upsn_user_submit_btn'])) {
			set_time_limit(0);
			$message = $_POST['message'];
			$args = array("message" => $message);
			//insert post in database
			// Create post object
			$my_post = array(
			  'post_title'    => $_POST['postname'],
			  'post_content'  => $message,
			  'post_status'   => 'pending',
			);

			// Insert the post into the database
			$intPostId = wp_insert_post( $my_post );

			if (isset($_FILES['source']['name']) && (!empty($_FILES['source']['name']))) :
				$upload = wp_upload_bits($_FILES['source']['name'], null, file_get_contents($_FILES['source']['tmp_name']));
				$source = $upload['file'];
				$args[basename($source)] = '@' . realpath($source);
				//set uploaded image as feature image
				_fnupsnSetFeatureImage($intPostId, $source);
			endif;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $graph_url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			if(!(is_ssl())) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			$data = curl_exec($ch);
	    	if($data === false) {
	        	echo 'Curl error: ' . curl_error($ch);
	    	} else {
	    		$msg = '<div style="color:#468847;background-color:#dff0d8;border-color:#d6e9c6;padding:8px 35px 8px 14px;">Post submitted successfully.</div>';
	    	}
	  	}
	  	?>
	  	<div id='upsn' style='margin:10px;'>
			<?php
			if( isset($msg) ) :
				echo $msg;
			endif;
			?>
			<form enctype="multipart/form-data" action="" method="post" id="frmupsn" name="frmupsn">
				<p>
					<label>Post Title <span class="upsn-required">*</span> </label>
			  		<input type="text" class="required" name="postname" size="30">
				</p>
				<p>
					<label>Post Content</label>
			  		<textarea name="message" rows="8" cols="45"></textarea>
				</p>
				<p>
					<label>Upload Image <span class="upsn-required">*</span> </label>
			  		<input name="source" class="required" type="file" id="upsn_source" accept="jpg|jpeg|png|gif">
				</p>
				<p>
					<input type="submit" name="upsn_user_submit_btn" value="Submit"/>
				</p>
			</form>
		</div>
	  	<?php
	}
}

//shortcode option
add_shortcode( 'upsn', 'userpostsocialnetwork' );
?>