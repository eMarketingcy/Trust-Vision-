<?php
/**
 * Plugin Name:       eMarketing Certificate Verifier
 * Plugin URI:        https://emarketing.cy
 * Description:       Creates and verifies Trusted Vision certificates and generates embeddable badges.
 * Version:           1.5.1
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecv
 */

if (!defined('WPINC')) die;

define('ECV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECV_PLUGIN_URL', plugin_dir_url(__FILE__));
global $wpdb;
define('ECV_TABLE_NAME', $wpdb->prefix . 'ecv_certificates');

// The first several functions are unchanged.
function ecv_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = ECV_TABLE_NAME;
    $sql = "CREATE TABLE $table_name ( id mediumint(9) NOT NULL AUTO_INCREMENT, certificate_id varchar(20) NOT NULL, company_name varchar(255) NOT NULL, website_url varchar(255) NOT NULL, level tinyint(1) NOT NULL, verification_date date NOT NULL, status varchar(20) DEFAULT 'active' NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY  (id), UNIQUE KEY certificate_id (certificate_id), UNIQUE KEY website_url (website_url) ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_rewrite_rule('^vision-badge/loader.js$', 'index.php?vision_badge_loader=1', 'top');
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ecv_activate');
function ecv_deactivate(){ flush_rewrite_rules(); }
register_deactivation_hook(__FILE__, 'ecv_deactivate');
function ecv_add_query_vars($vars) { $vars[] = 'vision_badge_loader'; return $vars; }
add_filter('query_vars', 'ecv_add_query_vars');
function ecv_serve_badge_loader_js() { if (get_query_var('vision_badge_loader')) { $js_path = ECV_PLUGIN_PATH . 'assets/js/badge-loader.js'; if (file_exists($js_path)) { header('Content-Type: application/javascript; charset=utf-8'); header('Cache-Control: public, max-age=86400'); readfile($js_path); exit; } else { status_header(404); exit; } } }
add_action('template_redirect', 'ecv_serve_badge_loader_js');
function ecv_admin_menu(){add_menu_page('Certificate Verifier','Certificates','manage_options','ecv-main-menu','ecv_manage_certificates_page','dashicons-shield-alt',25); add_submenu_page('ecv-main-menu','Manage Certificates','Manage','manage_options','ecv-main-menu'); add_submenu_page('ecv-main-menu','Add New Certificate','Add New','manage_options','ecv-add-new','ecv_add_new_certificate_page'); add_submenu_page('ecv-main-menu','Embed Badge','Embed Badge','manage_options','ecv-embed-badge','ecv_embed_badge_page');}
add_action('admin_menu', 'ecv_admin_menu');
function ecv_manage_certificates_page() { global $wpdb; $table_name = ECV_TABLE_NAME; $message = ''; $message_type = ''; if (isset($_GET['action']) && isset($_GET['cert_id'])) { $action = sanitize_key($_GET['action']); $cert_id = intval($_GET['cert_id']); if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], $action . '_' . $cert_id)) { if ($action === 'delete') { $wpdb->delete($table_name, ['id' => $cert_id], ['%d']); $message = 'Certificate deleted successfully.'; $message_type = 'success'; } elseif ($action === 'toggle_status') { $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $cert_id)); $new_status = ($current_status === 'active') ? 'expired' : 'active'; $wpdb->update($table_name, ['status' => $new_status], ['id' => $cert_id]); $message = 'Certificate status updated.'; $message_type = 'success'; } } else { $message = 'Security check failed. Action aborted.'; $message_type = 'error'; } } $certificates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC"); echo '<div class="wrap">'; echo '<h1>Manage Certificates <a href="?page=ecv-add-new" class="page-title-action">Add New</a></h1>'; if (!empty($message)) { echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>'; } echo '<table class="wp-list-table widefat fixed striped">'; echo '<thead><tr><th>Certificate ID</th><th>Company Name</th><th>Website</th><th>Level</th><th>Status</th><th>Actions</th></tr></thead>'; echo '<tbody>'; if ($certificates) { foreach ($certificates as $cert) { $delete_nonce = wp_create_nonce('delete_' . $cert->id); $status_nonce = wp_create_nonce('toggle_status_' . $cert->id); $delete_link = "?page=ecv-main-menu&action=delete&cert_id={$cert->id}&_wpnonce={$delete_nonce}"; $status_link = "?page=ecv-main-menu&action=toggle_status&cert_id={$cert->id}&_wpnonce={$status_nonce}"; $status_text = ($cert->status === 'active') ? 'Expire' : 'Activate'; $status_class = ($cert->status === 'active') ? 'active' : 'expired'; echo '<tr>'; echo '<td><strong>' . esc_html($cert->certificate_id) . '</strong></td>'; echo '<td>' . esc_html($cert->company_name) . '</td>'; echo '<td><a href="' . esc_url('http://' . $cert->website_url) . '" target="_blank">' . esc_html($cert->website_url) . '</a></td>'; echo '<td>Level ' . esc_html($cert->level) . '</td>'; echo '<td><span style="font-weight:bold; color:' . ($status_class === 'active' ? '#27ae60' : '#c0392b') . ';">' . esc_html(ucfirst($cert->status)) . '</span></td>'; echo '<td>'; echo '<a href="' . esc_url($status_link) . '">' . esc_html($status_text) . '</a> | '; echo '<a href="' . esc_url($delete_link) . '" style="color:#a00;" onclick="return confirm(\'Are you sure you want to permanently delete this certificate?\');">Delete</a>'; echo '</td>'; echo '</tr>'; } } else { echo '<tr><td colspan="6">No certificates found.</td></tr>'; } echo '</tbody></table></div>';}
function ecv_embed_badge_page() { global $wpdb; $table_name = ECV_TABLE_NAME; $certificates = $wpdb->get_results("SELECT id, certificate_id, company_name FROM $table_name ORDER BY company_name ASC"); $selected_cert_id = isset($_GET['cert_id']) ? intval($_GET['cert_id']) : 0; $selected_theme = isset($_GET['theme']) ? sanitize_key($_GET['theme']) : 'is-dark'; ?> <div class="wrap"> <h1>Embed Trusted Vision Badge</h1> <p>This embed code is small and secure. It loads the badge styles and data dynamically from a clean URL on your website.</p> <form method="GET"> <input type="hidden" name="page" value="ecv-embed-badge"> <table class="form-table"> <tr> <th scope="row"><label for="cert_id">Select Certificate</label></th> <td> <select name="cert_id" id="cert_id" required> <option value="">-- Select a Certificate --</option> <?php foreach ($certificates as $cert) : ?> <option value="<?php echo esc_attr($cert->id); ?>" <?php selected($selected_cert_id, $cert->id); ?>> <?php echo esc_html($cert->company_name); ?> (<?php echo esc_html($cert->certificate_id); ?>) </option> <?php endforeach; ?> </select> </td> </tr> <tr> <th scope="row">Select Theme</th> <td> <fieldset> <label><input type="radio" name="theme" value="is-dark" <?php checked($selected_theme, 'is-dark'); ?>> Dark Footer Theme</label><br> <label><input type="radio" name="theme" value="is-light" <?php checked($selected_theme, 'is-light'); ?>> Light Footer Theme</label> </fieldset> </td> </tr> </table> <?php submit_button('Generate Code'); ?> </form> <?php if ($selected_cert_id > 0) : $certificate = $wpdb->get_row($wpdb->prepare("SELECT certificate_id FROM $table_name WHERE id = %d", $selected_cert_id)); if ($certificate) : $plugin_data = get_plugin_data( __FILE__ ); $plugin_version = $plugin_data['Version']; $loader_url = home_url('/vision-badge/loader.js?ver=' . $plugin_version); $embed_code = <<<HTML
<div class="trusted-vision-badge-embed" data-cert-id="{$certificate->certificate_id}" data-theme="{$selected_theme}"></div>
<script src="{$loader_url}" async defer></script>
HTML;
 ?> <h2>Your New Embed Code</h2> <p>Copy the code below and paste it into your customer's website HTML, ideally before the closing `&lt;/body&gt;` tag.</p> <textarea id="ecv-embed-code" readonly style="width: 100%; height: 120px; font-family: monospace; white-space: pre;"><?php echo esc_textarea($embed_code); ?></textarea> <button class="button button-primary" onclick="copyEmbedCode()">Copy to Clipboard</button> <script>function copyEmbedCode(){var t=document.getElementById('ecv-embed-code');t.select(),document.execCommand('copy'),alert('Code copied to clipboard!')}</script> <?php endif; endif; ?> </div> <?php }
function ecv_normalize_url($url){if(!preg_match("~^(?:f|ht)tps?://~i",$url)){$url="http://".$url;} $parts=parse_url(strtolower($url)); if(!$parts||!isset($parts['host'])){return '';} $host=preg_replace('/^www\./','',$parts['host']); $path=isset($parts['path'])?rtrim($parts['path'],'/'):''; return $host.$path;}
function ecv_add_new_certificate_page(){global $wpdb; $table_name=ECV_TABLE_NAME; $error_message=''; $form_data=[]; if(isset($_POST['ecv_submit'])&&check_admin_referer('ecv_add_new_cert')){$form_data=['company_name'=>sanitize_text_field($_POST['company_name']),'website_url'=>esc_url_raw($_POST['website_url']),'level'=>intval($_POST['level']),'verification_date'=>sanitize_text_field($_POST['verification_date'])]; if(!empty($form_data['company_name'])&&!empty($form_data['website_url'])&&$form_data['level']>=1&&$form_data['level']<=5&&!empty($form_data['verification_date'])){$normalized_url=ecv_normalize_url($form_data['website_url']); if(empty($normalized_url)){$error_message='The provided Website URL is not valid. Please enter a valid URL (e.g., example.com).';}else{$certificate_id=ecv_generate_certificate_id($form_data['level']); $result=$wpdb->insert($table_name,['certificate_id'=>$certificate_id,'company_name'=>$form_data['company_name'],'website_url'=>$normalized_url,'level'=>$form_data['level'],'verification_date'=>$form_data['verification_date'],]); if($result===false){$error_message='Error: A certificate for this website already exists. Please check the "Manage Certificates" page.';}else{wp_redirect(admin_url('admin.php?page=ecv-main-menu&message=success')); exit;}}}else{$error_message='Please fill in all fields correctly.';}} if(!empty($error_message)){echo '<div class="notice notice-error"><p>'.esc_html($error_message).'</p></div>';} ?> <div class="wrap"> <h1>Add New Certificate</h1> <form method="post" action=""> <?php wp_nonce_field('ecv_add_new_cert'); ?> <table class="form-table"> <tr valign="top"> <th scope="row"><label for="company_name">Company Name</label></th> <td><input type="text" id="company_name" name="company_name" class="regular-text" value="<?php echo esc_attr($form_data['company_name']??''); ?>" required /></td> </tr> <tr valign="top"> <th scope="row"><label for="website_url">Website URL</label></th> <td> <input type="url" id="website_url" name="website_url" class="regular-text" placeholder="https://example.com" value="<?php echo esc_attr($form_data['website_url']??''); ?>" required /> <p class="description">A certificate for this exact website cannot already exist.</p> </td> </tr> <tr valign="top"> <th scope="row"><label for="level">Verification Level</label></th> <td> <select id="level" name="level" required> <?php for($i=1; $i<=5; $i++): ?> <option value="<?php echo $i; ?>" <?php selected($form_data['level']??5,$i); ?>>Level <?php echo $i; ?></option> <?php endfor; ?> </select> </td> </tr> <tr valign="top"> <th scope="row"><label for="verification_date">Verification Date</label></th> <td><input type="date" id="verification_date" name="verification_date" value="<?php echo esc_attr($form_data['verification_date']??date('Y-m-d')); ?>" required /></td> </tr> </table> <?php submit_button('Generate and Save Certificate','primary','ecv_submit'); ?> </form> </div> <?php }
function ecv_generate_certificate_id($level){global $wpdb; $table_name=ECV_TABLE_NAME; $is_unique=false; $cert_id=''; while(!$is_unique){$part1=strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,3)); $part2=strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,4)); $cert_id="TV{$level}-{$part1}-{$part2}"; $existing=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE certificate_id = %s",$cert_id)); if($existing==0){$is_unique=true;}} return $cert_id;}
function ecv_certificate_shortcode(){wp_enqueue_style('ecv-frontend-style',ECV_PLUGIN_URL.'assets/css/frontend-style.css'); if(!isset($_GET['cert'])){return '<div class="ecv-container" style="padding: 2rem; text-align: center;"><p>Please provide a certificate ID to begin verification.</p></div>';} global $wpdb; $table_name=ECV_TABLE_NAME; $cert_id=sanitize_text_field($_GET['cert']); $certificate=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE certificate_id = %s",$cert_id)); ob_start(); if($certificate){include(ECV_PLUGIN_PATH.'templates/certificate-valid-template.php');}else{include(ECV_PLUGIN_PATH.'templates/certificate-invalid-template.php');} return ob_get_clean();}
add_shortcode('certificate_verifier','ecv_certificate_shortcode');


/**
 * UPDATED: Register REST API endpoint to be more flexible.
 */
function ecv_register_rest_route() {
    // The '/?' at the end makes the trailing slash optional, preventing 301 redirects.
    register_rest_route('ecv/v1', '/badge/(?P<id>[a-zA-Z0-9-]+)/?', [
        'methods'  => 'GET',
        'callback' => 'ecv_get_badge_data',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'ecv_register_rest_route');


/**
 * UPDATED: Callback for REST API now only returns ACTIVE certificates.
 */
function ecv_get_badge_data($request) {
    global $wpdb;
    $cert_id = sanitize_text_field($request['id']);
    
    // This query now checks that the certificate status is 'active'.
    $certificate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . ECV_TABLE_NAME . " WHERE certificate_id = %s AND status = 'active'", 
        $cert_id
    ));

    // If no active certificate is found, return an error.
    if (empty($certificate)) {
        return new WP_Error('not_found', 'Active certificate not found', ['status' => 404]);
    }

    $verify_page_query = new WP_Query(['post_type'=>'page', 'post_status'=>'publish', 'posts_per_page'=>1, 's'=>'[certificate_verifier]']);
    $verify_url = $verify_page_query->have_posts() ? get_permalink($verify_page_query->posts[0]->ID) : home_url('/');
    
    $features_map = [ 1=>['Verified business identity'], 2=>['Verified business identity','Address &amp; contact confirmed'], 3=>['Verified business identity','Address &amp; contact confirmed','Social media audit'], 4=>['Verified business identity','Address &amp; contact confirmed','Social media audit','Reputation check'], 5=>['Verified business identity','Address &amp; contact confirmed','5★ reviews audited','Annual re-verification'], ];

    $data = [ 'cert_id' => $certificate->certificate_id, 'level' => $certificate->level, 'issuer' => 'eMarketing.cy', 'date_iso' => $certificate->verification_date, 'date_formatted' => date("M j, Y", strtotime($certificate->verification_date)), 'features' => $features_map[$certificate->level] ?? [], 'verify_link' => add_query_arg('cert', $certificate->certificate_id, $verify_url), ];
    
    $response = new WP_REST_Response($data);
    $response->header('Access-Control-Allow-Origin', '*');
    
    return $response;
}