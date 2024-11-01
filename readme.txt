=== User Post On Social Network ===
Contributors: sajid
Tags: facebook, wordpress, wordpress post on facebook
Requires at least: 3.5
Tested up to: 3.9.2
Stable tag: 1.0
License: GPLv2 or later

This plugin allow user to create a post on your site and at the same time post it on social network.

== Description == 

This plugin allow user to create a post on your site and at the same time post it on social network.

PS: You'll need a curl enabled on your server to achieve the target.

== Installation ==
1. Upload the plugin to your blog, Activate it, then enter your facebook app details.
2. To use the plugin - add shortcode [upsn] in your post/page. Or you can add if( function_exists('userpostsocialnetwork') ) { userpostsocialnetwork(); } in your page template.

== Frequently Asked Questions ==
= How do i submit the post? =
1. First you need to login with facebook using our plugin.
2. After login you can view the post submission form.

= How do i upload large size image using this plugin? =
1. To upload large file using this plugin you have to change 'upload_max_filesize ' and 'post_max_size' in php.ini file.
2. Keep same size for 'upload_max_filesize' and 'post_max_size' in php.ini.

== Screenshots ==
1. facebook app details
2. Front End
3. Front End Form
4. Facebook Wall