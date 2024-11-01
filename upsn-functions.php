<?php
/**
* Function _fnupsnSetFeatureImage() set feature image by coding
* @param $post_id
* @param $image_url: upload path of image
*/

function _fnupsnSetFeatureImage($post_id, $image_url)
{
	require ( ABSPATH . 'wp-admin/includes/image.php' );
    $filetype = wp_check_filetype( $image_url );

    $args = array(
        'post_mime_type' => $filetype['type']
    );

    $thumb_id = wp_insert_attachment( $args, $image_url,  $post_id );

    $metadata = wp_generate_attachment_metadata( $thumb_id, $image_url );

    wp_update_attachment_metadata( $thumb_id, $metadata );

    update_post_meta( $post_id, '_thumbnail_id', $thumb_id );
}

/**
* Function upsn_current_page_url() return current url
*/
function upsn_current_page_url() {
	$pageURL = 'http';
	if( isset($_SERVER["HTTPS"]) ) {
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

/**
* Function upsnconvertBytes() calculate size in kb, mb and gb
* @param $value : value to calculate
*/
function upsnconvertBytes( $value ) {
    if ( is_numeric( $value ) ) {
        return $value;
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }
        return $qty;
    }
}
?>