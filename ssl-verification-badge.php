<?php
/**
 * Plugin Name: SSL Verification Badge
 * Version: 1.0
 * Description: This plugin adds a badge on your website telling customers your site is verified to be secure by checking your current SSL
 * Author: Philip Jewell
 * Author URI: https://philipjewell.com
 * Plugin URI: https://philipjewell.com
 * Text Domain: ssl-verification-badge
 * Domain Path: /languages
 * @package SSL Verification Badge
 * License: SSLVB_LICENSE
 */
add_action( 'wp_footer', 'sslvb_ssl_verify' );
function sslvb_ssl_verify() {
    $dir = plugin_dir_url( __FILE__ );
    $domain = $_SERVER['HTTP_HOST'];
    //take up to two levels of subdomains off so we can pull the base domain to do NS lookup
    $host_names = explode(".", $domain);
    $ns = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
    $sslresults = get_headers('http://'.$domain);

    $sslurl = "https://".$domain;
    $orignal_parse = parse_url($sslurl, PHP_URL_HOST);
    $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
    $read = stream_socket_client("ssl://".$orignal_parse.":443", $errno, $errstr, 30,   STREAM_CLIENT_CONNECT, $get);
     $cert = stream_context_get_params($read);
     $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
     $validFrom = date('M d, Y', $certinfo['validFrom_time_t']);
     $validTo = date('M d, Y', $certinfo['validTo_time_t']);
     //search if domain is found as a SAN on the certificate, also checks wildcard
        $SANs = $certinfo['extensions']['subjectAltName'] . '&lt;br&gt;';
	$SANs = preg_replace('#DNS:#', '', $SANs);
        $SANs = preg_replace('#,#', '<br>', $SANs);

    $today = date('Y m d');
    $expiration = date('Y m d', $certinfo['validTo_time_t']);
    //if today's date is greater than the expiration date, warn the user
    if ($today > $expiration) {
        $notice = "<a target='_blank' href='https://www.sslshopper.com/ssl-checker.html#hostname=" . $domain . "'><div class='certred'><img style='width:20px;' src='" . $dir . "img/insecure-s.png'> WARNING! SSL Expired: " . $validTo . "</div></a>";
    } else {
        $notice = "<a target='_blank'href='https://www.sslshopper.com/ssl-checker.html#hostname=" . $domain . "'><div class='certgreen'><b>VERIFIED & SECURED</b><br>BY: " . $certinfo['issuer']['CN'] . "<br><img style='width:20px;' src='" . $dir . "img/secure-s.png'> SSL Valid: " . $validFrom . " - " . $validTo . "</div></a>";
    }


    if (preg_match("/".$domain."/", $SANs)) { //checks if domain is found in cert SANs
        echo $notice;
    } elseif (preg_match("/\*\.".$ns."/", $SANs)) { //checks if domain is found in wildcard as well
            echo $notice;
    } else {
    echo "<div class='certyellow'><img style='width:20px;' src='" . $dir . "img/unsecure-s.png'>" . $domain . "  is not secured by an SSL currently. Visit <a target='_blank' href='https://www.startssl.com/'>StartSSL.com</a> for one for FREE.</div>";
    }
}
add_action( 'wp_enqueue_scripts', 'sslvb_my_enqueued_assets' );

function sslvb_my_enqueued_assets() {
	wp_enqueue_style( 'sslvb_my-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
}
add_action('admin_menu', 'sslvb_my_plugin_menu');

//function sslvb_my_plugin_menu() {
//	add_menu_page('My Plugin Settings', 'SSL Verification Badge', 'administrator', 'sslvb_my-plugin-settings', 'sslvb_my_plugin_settings_page', 'dashicons-awards');
//}

function sslvb_plugin_settings_page() {
  //
}
