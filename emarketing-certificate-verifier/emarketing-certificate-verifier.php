<?php
/**
 * Plugin Name:       eMarketing Certificate Verifier
 * Plugin URI:        https://emarketing.cy
 * Description:       Creates and verifies Trusted Vision certificates and generates embeddable badges.
 * Version:           1.8.0
 * Author:            eMarketing Cyprus part of SaltPixel Ltd
 * Author URI:        https://emarketing.cy
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecv
 */

if (!defined('WPINC')) die;

define('ECV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECV_PLUGIN_URL', plugin_dir_url(__FILE__));
global $wpdb;
define('ECV_TABLE_NAME', $wpdb->prefix . 'ecv_certificates');

// --- All functions from the previous version up to ecv_manage_certificates_page are unchanged. ---
// --- Paste them here. ---
function ecv_activate() { global $wpdb; $charset_collate = $wpdb->get_charset_collate(); $table_name = ECV_TABLE_NAME; $sql = "CREATE TABLE $table_name ( id mediumint(9) NOT NULL AUTO_INCREMENT, certificate_id varchar(20) NOT NULL, company_name varchar(255) NOT NULL, website_url varchar(255) NOT NULL, level tinyint(1) NOT NULL, verification_date date NOT NULL, status varchar(20) DEFAULT 'active' NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY  (id), UNIQUE KEY certificate_id (certificate_id), UNIQUE KEY website_url (website_url) ) $charset_collate;"; require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); dbDelta($sql); add_rewrite_rule('^vision-badge/loader.js$', 'index.php?vision_badge_loader=1', 'top'); flush_rewrite_rules(); }
register_activation_hook(__FILE__, 'ecv_activate');
function ecv_deactivate(){ flush_rewrite_rules(); }
register_deactivation_hook(__FILE__, 'ecv_deactivate');
function ecv_add_query_vars($vars) { $vars[] = 'vision_badge_loader'; return $vars; }
add_filter('query_vars', 'ecv_add_query_vars');
function ecv_serve_badge_loader_js() { if (get_query_var('vision_badge_loader')) { $js_path = ECV_PLUGIN_PATH . 'assets/js/badge-loader.js'; if (file_exists($js_path)) { header('Content-Type: application/javascript; charset=utf-8'); header('Cache-Control: public, max-age=86400'); readfile($js_path); exit; } else { status_header(404); exit; } } }
add_action('template_redirect', 'ecv_serve_badge_loader_js');
function ecv_admin_menu(){ add_menu_page('Certificate Verifier','Certificates','manage_options','ecv-main-menu','ecv_manage_certificates_page','dashicons-shield-alt',25); add_submenu_page('ecv-main-menu','Manage Certificates','Manage','manage_options','ecv-main-menu'); add_submenu_page('ecv-main-menu','Add New Certificate','Add New','manage_options','ecv-add-new','ecv_add_new_certificate_page'); add_submenu_page('ecv-main-menu','Embed Badge','Embed Badge','manage_options','ecv-embed-badge','ecv_embed_badge_page'); add_submenu_page('ecv-main-menu','Settings','Settings','manage_options','ecv-settings','ecv_settings_page'); }
add_action('admin_menu', 'ecv_admin_menu');
function ecv_embed_badge_page() { global $wpdb; $table_name = ECV_TABLE_NAME; $certificates = $wpdb->get_results("SELECT id, certificate_id, company_name FROM $table_name ORDER BY company_name ASC"); $selected_cert_id = isset($_GET['cert_id']) ? intval($_GET['cert_id']) : 0; $selected_theme = isset($_GET['theme']) ? sanitize_key($_GET['theme']) : 'is-dark'; ?> <div class="wrap"> <h1>Embed Trusted Vision Badge</h1> <p>This embed code is small and secure. It loads the badge styles and data dynamically from a clean URL on your website.</p> <form method="GET"> <input type="hidden" name="page" value="ecv-embed-badge"> <table class="form-table"> <tr> <th scope="row"><label for="cert_id">Select Certificate</label></th> <td> <select name="cert_id" id="cert_id" required> <option value="">-- Select a Certificate --</option> <?php foreach ($certificates as $cert) : ?> <option value="<?php echo esc_attr($cert->id); ?>" <?php selected($selected_cert_id, $cert->id); ?>> <?php echo esc_html($cert->company_name); ?> (<?php echo esc_html($cert->certificate_id); ?>) </option> <?php endforeach; ?> </select> </td> </tr> <tr> <th scope="row">Select Theme</th> <td> <fieldset> <label><input type="radio" name="theme" value="is-dark" <?php checked($selected_theme, 'is-dark'); ?>> Dark Theme</label><br> <label><input type="radio" name="theme" value="is-light" <?php checked($selected_theme, 'is-light'); ?>> Light Theme</label><br> <label><input type="radio" name="theme" value="is-all-white" <?php checked($selected_theme, 'is-all-white'); ?>> All White Theme (for dark backgrounds)</label> </fieldset> </td> </tr> </table> <?php submit_button('Generate Code'); ?> </form> <?php if ($selected_cert_id > 0) : $certificate = $wpdb->get_row($wpdb->prepare("SELECT certificate_id FROM $table_name WHERE id = %d", $selected_cert_id)); if ($certificate) : $plugin_data = get_plugin_data( __FILE__ ); $plugin_version = $plugin_data['Version']; $loader_url = home_url('/vision-badge/loader.js?ver=' . $plugin_version); $embed_code = <<<HTML
<div class="trusted-vision-badge-embed" data-cert-id="{$certificate->certificate_id}" data-theme="{$selected_theme}"></div>
<script src="{$loader_url}" async defer></script>
HTML;
 ?> <h2>Your New Embed Code</h2> <p>Copy the code below and paste it into your customer's website HTML, ideally before the closing `&lt;/body&gt;` tag.</p> <textarea id="ecv-embed-code" readonly style="width: 100%; height: 120px; font-family: monospace; white-space: pre;"><?php echo esc_textarea($embed_code); ?></textarea> <button class="button button-primary" onclick="copyEmbedCode()">Copy to Clipboard</button> <script>function copyEmbedCode(){var t=document.getElementById('ecv-embed-code');t.select(),document.execCommand('copy'),alert('Code copied to clipboard!')}</script> <?php endif; endif; ?> </div> <?php }
function ecv_normalize_url($url){if(!preg_match("~^(?:f|ht)tps?://~i",$url)){$url="http://".$url;} $parts=parse_url(strtolower($url)); if(!$parts||!isset($parts['host'])){return '';} $host=preg_replace('/^www\./','',$parts['host']); $path=isset($parts['path'])?rtrim($parts['path'],'/'):''; return $host.$path;}
function ecv_add_new_certificate_page(){global $wpdb; $table_name=ECV_TABLE_NAME; $error_message=''; $form_data=[]; if(isset($_POST['ecv_submit'])&&check_admin_referer('ecv_add_new_cert')){$form_data=['company_name'=>sanitize_text_field($_POST['company_name']),'website_url'=>esc_url_raw($_POST['website_url']),'level'=>intval($_POST['level']),'verification_date'=>sanitize_text_field($_POST['verification_date'])]; if(!empty($form_data['company_name'])&&!empty($form_data['website_url'])&&$form_data['level']>=1&&$form_data['level']<=5&&!empty($form_data['verification_date'])){$normalized_url=ecv_normalize_url($form_data['website_url']); if(empty($normalized_url)){$error_message='The provided Website URL is not valid. Please enter a valid URL (e.g., example.com).';}else{$certificate_id=ecv_generate_certificate_id($form_data['level']); $result=$wpdb->insert($table_name,['certificate_id'=>$certificate_id,'company_name'=>$form_data['company_name'],'website_url'=>$normalized_url,'level'=>$form_data['level'],'verification_date'=>$form_data['verification_date'],]); if($result===false){$error_message='Error: A certificate for this website already exists. Please check the "Manage Certificates" page.';}else{wp_redirect(admin_url('admin.php?page=ecv-main-menu&message=success')); exit;}}}else{$error_message='Please fill in all fields correctly.';}} if(!empty($error_message)){echo '<div class="notice notice-error"><p>'.esc_html($error_message).'</p></div>';} ?> <div class="wrap"> <h1>Add New Certificate</h1> <form method="post" action=""> <?php wp_nonce_field('ecv_add_new_cert'); ?> <table class="form-table"> <tr valign="top"> <th scope="row"><label for="company_name">Company Name</label></th> <td><input type="text" id="company_name" name="company_name" class="regular-text" value="<?php echo esc_attr($form_data['company_name']??''); ?>" required /></td> </tr> <tr valign="top"> <th scope="row"><label for="website_url">Website URL</label></th> <td> <input type="url" id="website_url" name="website_url" class="regular-text" placeholder="https://example.com" value="<?php echo esc_attr($form_data['website_url']??''); ?>" required /> <p class="description">A certificate for this exact website cannot already exist.</p> </td> </tr> <tr valign="top"> <th scope="row"><label for="level">Verification Level</label></th> <td> <select id="level" name="level" required> <?php for($i=1; $i<=5; $i++): ?> <option value="<?php echo $i; ?>" <?php selected($form_data['level']??5,$i); ?>>Level <?php echo $i; ?></option> <?php endfor; ?> </select> </td> </tr> <tr valign="top"> <th scope="row"><label for="verification_date">Verification Date</label></th> <td><input type="date" id="verification_date" name="verification_date" value="<?php echo esc_attr($form_data['verification_date']??date('Y-m-d')); ?>" required /></td> </tr> </table> <?php submit_button('Generate and Save Certificate','primary','ecv_submit'); ?> </form> </div> <?php }
function ecv_generate_certificate_id($level){global $wpdb; $table_name=ECV_TABLE_NAME; $is_unique=false; $cert_id=''; while(!$is_unique){$part1=strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,3)); $part2=strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,4)); $cert_id="TV{$level}-{$part1}-{$part2}"; $existing=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE certificate_id = %s",$cert_id)); if($existing==0){$is_unique=true;}} return $cert_id;}
/**
 * UPDATED: The shortcode now gathers the feature list to display on the page.
 */
function ecv_certificate_shortcode() {
    wp_enqueue_style('ecv-frontend-style', ECV_PLUGIN_URL . 'assets/css/frontend-style.css');

    if (!isset($_GET['cert'])) {
        return '<div class="ecv-container" style="padding: 2rem; text-align: center;"><p>Please provide a certificate ID to begin verification.</p></div>';
    }

    global $wpdb;
    $table_name = ECV_TABLE_NAME;
    $cert_id = sanitize_text_field($_GET['cert']);
    $certificate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE certificate_id = %s", $cert_id));
    
    $features_for_page = []; // Initialize an empty array for features

    // If the certificate is valid, get its feature list
    if ($certificate && $certificate->status === 'active') {
        $all_features = ecv_get_all_features();
        $level_features_config = get_option('ecv_level_features', []);
        $current_level_keys = isset($level_features_config[$certificate->level]) ? $level_features_config[$certificate->level] : [];
        
        foreach ($current_level_keys as $key) {
            if (isset($all_features[$key])) {
                $features_for_page[] = $all_features[$key];
            }
        }
    }

    ob_start();
    if ($certificate && $certificate->status === 'active') {
        // The $certificate and $features_for_page variables are now available in the template
        include(ECV_PLUGIN_PATH . 'templates/certificate-valid-template.php');
    } else {
        // Handle invalid, not found, or expired certificates
        include(ECV_PLUGIN_PATH . 'templates/certificate-invalid-template.php');
    }
    return ob_get_clean();
}
add_shortcode('certificate_verifier','ecv_certificate_shortcode');
function ecv_register_rest_route(){register_rest_route('ecv/v1','/badge/(?P<id>[a-zA-Z0-9-]+)/?',['methods'=>'GET','callback'=>'ecv_get_badge_data','permission_callback'=>'__return_true',]);}
add_action('rest_api_init','ecv_register_rest_route');
function ecv_get_all_features() { $features_string = get_option('ecv_master_features_list'); if (empty($features_string)) { $features_string = implode("\n", [ 'Verified business identity', 'Address & contact confirmed', '5★ reviews audited', 'Annual re-verification', 'Social media audit', 'Reputation check', ]); add_option('ecv_master_features_list', $features_string); } $features_lines = explode("\n", $features_string); $features_array = []; foreach ($features_lines as $line) { $line = trim($line); if (!empty($line)) { $key = sanitize_title($line); $features_array[$key] = $line; } } return $features_array; }

/**
 * UPDATED: The Manage Certificates page now clears the cache when an item is changed.
 */
function ecv_manage_certificates_page() {
    global $wpdb; $table_name = ECV_TABLE_NAME; $message = ''; $message_type = '';
    if (isset($_GET['action']) && isset($_GET['cert_id'])) {
        $action = sanitize_key($_GET['action']);
        $cert_id = intval($_GET['cert_id']);
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], $action . '_' . $cert_id)) {
            // Get the certificate ID string before deleting/updating
            $certificate_id_str = $wpdb->get_var($wpdb->prepare("SELECT certificate_id FROM $table_name WHERE id = %d", $cert_id));
            if ($certificate_id_str) {
                // Delete the specific transient for this certificate
                delete_transient('ecv_badge_data_' . $certificate_id_str);
            }

            if ($action === 'delete') {
                $wpdb->delete($table_name, ['id' => $cert_id], ['%d']);
                $message = 'Certificate deleted and cache cleared.'; $message_type = 'success';
            } elseif ($action === 'toggle_status') {
                $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $cert_id));
                $new_status = ($current_status === 'active') ? 'expired' : 'active';
                $wpdb->update($table_name, ['status' => $new_status], ['id' => $cert_id]);
                $message = 'Certificate status updated and cache cleared.'; $message_type = 'success';
            }
        } else {
            $message = 'Security check failed. Action aborted.'; $message_type = 'error';
        }
    }
    // ... the rest of the function is the same ...
    $certificates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC"); echo '<div class="wrap">'; echo '<h1>Manage Certificates <a href="?page=ecv-add-new" class="page-title-action">Add New</a></h1>'; if (!empty($message)) { echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>'; } echo '<table class="wp-list-table widefat fixed striped">'; echo '<thead><tr><th>Certificate ID</th><th>Company Name</th><th>Website</th><th>Level</th><th>Status</th><th>Actions</th></tr></thead>'; echo '<tbody>'; if ($certificates) { foreach ($certificates as $cert) { $delete_nonce = wp_create_nonce('delete_' . $cert->id); $status_nonce = wp_create_nonce('toggle_status_' . $cert->id); $delete_link = "?page=ecv-main-menu&action=delete&cert_id={$cert->id}&_wpnonce={$delete_nonce}"; $status_link = "?page=ecv-main-menu&action=toggle_status&cert_id={$cert->id}&_wpnonce={$status_nonce}"; $status_text = ($cert->status === 'active') ? 'Expire' : 'Activate'; $status_class = ($cert->status === 'active') ? 'active' : 'expired'; echo '<tr>'; echo '<td><strong>' . esc_html($cert->certificate_id) . '</strong></td>'; echo '<td>' . esc_html($cert->company_name) . '</td>'; echo '<td><a href="' . esc_url('http://' . $cert->website_url) . '" target="_blank">' . esc_html($cert->website_url) . '</a></td>'; echo '<td>Level ' . esc_html($cert->level) . '</td>'; echo '<td><span style="font-weight:bold; color:' . ($status_class === 'active' ? '#27ae60' : '#c0392b') . ';">' . esc_html(ucfirst($cert->status)) . '</span></td>'; echo '<td>'; echo '<a href="' . esc_url($status_link) . '">' . esc_html($status_text) . '</a> | '; echo '<a href="' . esc_url($delete_link) . '" style="color:#a00;" onclick="return confirm(\'Are you sure you want to permanently delete this certificate?\');">Delete</a>'; echo '</td>'; echo '</tr>'; } } else { echo '<tr><td colspan="6">No certificates found.</td></tr>'; } echo '</tbody></table></div>';
}

/**
 * UPDATED: The Settings Page now includes a cache clearing button.
 */
function ecv_settings_page() {
    if (!current_user_can('manage_options')) return;

    // Handle Clear Cache action
    if (isset($_POST['ecv_clear_cache']) && check_admin_referer('ecv_clear_cache_nonce')) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_ecv_badge_data_%' OR option_name LIKE '\_transient\_timeout\_ecv_badge_data_%'");
        echo '<div class="notice notice-success is-dismissible"><p>All badge caches have been cleared successfully!</p></div>';
    }

    // Handle Save Settings form submission
    if (isset($_POST['ecv_save_settings']) && check_admin_referer('ecv_settings_nonce')) {
        // ... same saving logic as before ...
        $page_id = isset($_POST['ecv_verification_page_id']) ? intval($_POST['ecv_verification_page_id']) : 0; update_option('ecv_verification_page_id', $page_id); if (isset($_POST['ecv_master_features_list'])) { update_option('ecv_master_features_list', sanitize_textarea_field($_POST['ecv_master_features_list'])); } $level_features = isset($_POST['ecv_level_features']) ? $_POST['ecv_level_features'] : []; $sanitized_features = []; $all_features_keys = array_keys(ecv_get_all_features()); foreach ($level_features as $level => $features) { $level = intval($level); if ($level >= 1 && $level <= 5 && is_array($features)) { $sanitized_features[$level] = array_intersect($features, $all_features_keys); } } update_option('ecv_level_features', $sanitized_features); echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    // ... same form display logic as before ...
    $selected_page_id = get_option('ecv_verification_page_id'); $master_features_string = get_option('ecv_master_features_list', ''); $saved_level_features = get_option('ecv_level_features', []); $all_features = ecv_get_all_features();
    ?>
    <div class="wrap">
        <h1>Certificate Verifier Settings</h1>
        
        <form method="post" action="" style="margin-bottom: 2rem; border: 1px solid #c3c4c7; padding: 1rem; background: #fff;">
            <h2>Cache Management</h2>
            <p>Your badge data is cached for 1 hour to ensure fast loading on external sites. If you make changes to settings, you can force an immediate update by clearing the cache.</p>
            <?php wp_nonce_field('ecv_clear_cache_nonce'); ?>
            <?php submit_button('Clear All Badge Caches', 'delete', 'ecv_clear_cache', false); ?>
        </form>

        <form method="post" action="">
            <?php wp_nonce_field('ecv_settings_nonce'); ?>
            <h2>General Settings</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="ecv_verification_page_id">Verification Page</label></th>
                    <td>
                        <?php wp_dropdown_pages(['name' => 'ecv_verification_page_id', 'selected' => $selected_page_id, 'show_option_none' => '— Select a Page —', 'option_none_value' => '0']); ?>
                        <p class="description">Select the page containing the <code>[certificate_verifier]</code> shortcode.</p>
                    </td>
                </tr>
            </table>
            <hr>
            <h2>Feature Configuration</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="ecv_master_features_list">Available Features</label></th>
                    <td>
                        <textarea name="ecv_master_features_list" id="ecv_master_features_list" rows="8" class="large-text"><?php echo esc_textarea($master_features_string); ?></textarea>
                        <p class="description">Add or remove features from this master list, one feature per line.</p>
                    </td>
                </tr>
            </table>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <h3>Level <?php echo $i; ?> Features</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Included Features</th>
                        <td><fieldset>
                            <?php foreach ($all_features as $key => $label): ?>
                                <?php $current_level_features = isset($saved_level_features[$i]) ? $saved_level_features[$i] : []; $is_checked = in_array($key, $current_level_features); ?>
                                <label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="ecv_level_features[<?php echo $i; ?>][]" value="<?php echo esc_attr($key); ?>" <?php checked($is_checked); ?>> <?php echo esc_html($label); ?></label>
                            <?php endforeach; ?>
                        </fieldset></td>
                    </tr>
                </table>
            <?php endfor; ?>
            <?php submit_button('Save Settings', 'primary', 'ecv_save_settings'); ?>
        </form>
    </div>
    <?php
}


/**
 * UPDATED: The REST API now uses the new caching system.
 */
function ecv_get_badge_data($request) {
    $cert_id = sanitize_text_field($request['id']);
    $transient_key = 'ecv_badge_data_' . $cert_id;

    // 1. Try to get the data from the cache first
    $cached_data = get_transient($transient_key);
    if ($cached_data !== false) {
        $response = new WP_REST_Response($cached_data);
        $response->header('Access-Control-Allow-Origin', '*');
        return $response;
    }

    // 2. If not in cache, generate the data
    global $wpdb;
    $certificate = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ECV_TABLE_NAME . " WHERE certificate_id = %s AND status = 'active'", $cert_id));
    if (empty($certificate)) {
        return new WP_Error('not_found', 'Active certificate not found', ['status' => 404]);
    }

    $verify_page_id = get_option('ecv_verification_page_id');
    $verify_url = $verify_page_id ? get_permalink($verify_page_id) : home_url('/');
    
    $all_features = ecv_get_all_features();
    $level_features_config = get_option('ecv_level_features', []);
    $current_level_keys = isset($level_features_config[$certificate->level]) ? $level_features_config[$certificate->level] : [];
    
    $features_for_badge = [];
    foreach ($current_level_keys as $key) {
        if (isset($all_features[$key])) {
            $features_for_badge[] = $all_features[$key];
        }
    }
    
    $data = [
        'cert_id'         => $certificate->certificate_id,
        'level'           => $certificate->level,
        'issuer'          => 'eMarketing.cy',
        'date_iso'        => $certificate->verification_date,
        'date_formatted'  => date("M j, Y", strtotime($certificate->verification_date)),
        'features'        => $features_for_badge,
        'verify_link'     => add_query_arg('cert', $certificate->certificate_id, $verify_url),
    ];
    
    // 3. Save the newly generated data into the cache for 1 hour
    set_transient($transient_key, $data, HOUR_IN_SECONDS);

    $response = new WP_REST_Response($data);
    $response->header('Access-Control-Allow-Origin', '*');
    
    return $response;
}