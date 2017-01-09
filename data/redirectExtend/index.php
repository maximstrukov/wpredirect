<?php
/*
  Plugin Name: Redirects extended
  Plugin URI: http://www.trafficjunction.co.uk/
  Description: CouponPress extend
  Version: 2.7
  Author: Dmitry Surzhikov
  Author URI: http://dmitry-devstyle.pp.ua
 */


// create custom plugin settings menu
add_action('admin_menu', 'redirects_create_menu');

function redirects_create_menu() {

    //create new top-level menu
    add_options_page('redirects Plugin Settings', 'Redirects Settings', 'administrator', __FILE__, 'redirects_settings_page');

    //call register settings function
    add_action('admin_init', 'register_mysettings');
}

function register_mysettings() {
    //register our settings
    add_meta_box('myplugin_sectionid', __('Parametrized URL', 'myplugin_textdomain'), 'myplugin_inner_custom_box', 'post', 'advanced', 'high');

    register_setting('redirects-settings-group', 'redirect_rules');
    register_setting('redirects-settings-group', 'redirectsMagicParam');
    register_setting('redirects-settings-group', 'redirectsMagicParam2');
    register_setting('redirects-settings-group', 'redirectAdminUrl');
    register_setting('redirects-settings-group', 'rootUrl');
}

function myplugin_inner_custom_box() {
    global $post;

    //print_r($post);
    $permalink = get_permalink();
    $mp = get_option('redirectsMagicParam');
    $mp2 = get_option('redirectsMagicParam2');    

    echo '<input type="text" size="80" value="' . addmagicParam($permalink, $mp) . '"/>';
}

function redirects_settings_page() {
    ?>

<script type="text/javascript">
jQuery(document).ready(function(){

    jQuery(".button-primary").click(function(e){
        
        var redirectAdminUrl = jQuery('input[name=redirectAdminUrl]').val();
        if(!CheckValidUrl(redirectAdminUrl)) {
            jQuery('#url_error').html('Wrong url format.');
            e.preventDefault();
            return false;
        }
        
        var rootUrl = jQuery('input[name=rootUrl]').val();
        if(rootUrl != '') {
            
            if(!CheckValidUrl(rootUrl)) {
                jQuery('#rootUrl_error').html('Wrong url format.');
                e.preventDefault();
                return false;
            }
        }
    });        
});
    
function CheckValidUrl(strUrl) {
    var RegexUrl = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
    return RegexUrl.test(strUrl);
}
</script>

    <div class="wrap">
        <h2>Redirects extended options</h2>

        <form method="post" action="options.php">
            <?php settings_fields('redirects-settings-group'); ?>
            <?php // do_settings('redirects-settings-group'); ?>
            <table class="form-table" style="width:530px" id="rowsList">
                <tr valign="top">
                    <th scope="row">Magic Parameter</th>
                    <td><input type="text" name="redirectsMagicParam" value="<?php echo get_option('redirectsMagicParam'); ?>" /></td>
                    <td></td>
                </tr>
                <tr valign="row">
                    <th scope="row">Magic Parameter(for TU2)</th>
                    <td><input type="text" name="redirectsMagicParam2" value="<?php echo get_option('redirectsMagicParam2'); ?>" /></td>
                    <td></td>
                </tr>                
                <tr>
                    <th scope="row">Redirectadmin URL</th>
                    <td><input type="text" name="redirectAdminUrl" value="<?php echo get_option('redirectAdminUrl'); ?>" /></td>
                    <td><span id="url_error" style="color:red;"></span></td>
                </tr>                
                <tr>
                    <th scope="row">Root Url</th>
                    <td><input type="text" name="rootUrl" value="<?php echo get_option('rootUrl'); ?>" /></td>
                    <td><span id="rootUrl_error" style="color:red;"></span></td>
                </tr>                
                <tr>
                    <td  colspan="3" style="text-align: center"><h3>Website URL parsing rule</h3></td>
                </tr>

                <?php
                $domailRules = get_option('redirect_rules');

                if (is_array($domailRules)):
                    foreach ($domailRules as $domain => $rule):
                        ?>
                        <tr>
                            <td>
                                <?php echo $domain; ?>
                            </td>
                            <td>
                                <textarea  name="redirect_rules[<?php echo $domain; ?>]" placeholder="new domain rule"  cols="36"><?php echo $rule; ?></textarea>
                            </td>
                            <td>
                                <button class="rmvBrn">x</button>
                            </td>
                        </tr>

                        <?php
                    endforeach;
                endif;
                ?>

            </table>
            <table class="form-table" style="width:530px">
                <tr>
                    <td>
                        <input type="text" id="newDomain" placeholder="new domain" size="16"/>
                    </td>
                    <td>
                        <textarea  id="newDomainRule" placeholder="new domain rule"  cols="26"></textarea>
                    </td>
                    <td>
                        <button id="addRule">Add</button>
                    </td>
                </tr>
            </table>    

            <script type="text/javascript">
                                                        
                jQuery(document).ready(function($){
                    $("#addRule").click(function(e){
                        e.preventDefault();
                        var domain = $("#newDomain").val();
                        var rule = $("#newDomainRule").val();
                                                                
                        var tr = $("<tr/>");
                        tr.append($("<td/>").append(domain));
                        tr.append($("<td/>").html(
                        $('<input type="text" size="35"/>').attr("name", "redirect_rules["+ domain +"]").val(rule)
                    ));
                        tr.append($("<td/>").append($("<button/>").addClass("rmvBtn").html("x")));
                                                                
                        $("#rowsList").append(tr);                          
                        $("#newDomain").val('');
                        $("#newDomainRule").val('');
                    });
                                                      
                    $(".rmvBrn").live('click',function(){
                        $(this).parents("tr").remove();
                                                        
                    })  
                });
                                                
            </script>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>

        </form>
    </div>
    <?php
}

add_action('template_redirect', 'redirects_field_redirect');

function addmagicParam($url, $mp) {

    if (strpos($url, '?') !== false)
        return $url . '&' . $mp;

    return $url . '?' . $mp;
}

// get root url considering root url from setup field 
function getRootUrl() {
    
    $rootUrl = $_SERVER['HTTP_HOST'];
    
    $rootUrlData = get_option('rootUrl');
    if(!empty($rootUrlData))
        $rootUrl = strtolower(rtrim(str_ireplace(array('http://', 'https://', 'www.'), '', $rootUrlData), '/'));
    
    return $rootUrl; 
}

function redirects_field_redirect() {
    
    //globalize vars
    global $wp_query, $post;
    
    // api field 
    runRedirectApi();
    
    // magic parameters 
    $mp = get_option('redirectsMagicParam');
    $mp2 = get_option('redirectsMagicParam2');
    
    // post urls
    $exception_url2 = get_post_meta($wp_query->post->ID, 'exception_url2', true);
    $redirectUrl = get_post_meta($wp_query->post->ID, 'link', true);

    $referer = wp_get_referer();
    $permalink = get_permalink();
    
        // RedirectAdmin params 
        $redirectAdminUrl = get_option('redirectAdminUrl');
        $user_ip = get_user_ip(); 
        $wp_post_id = $wp_query->post->ID;     
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // catching emailParam
    $emailParam = get_post_meta($wp_query->post->ID, 'email_param', true);
    
    foreach($_GET as $key => $val)
        if($key == $emailParam) {
            
            $cData = array();
            
            $rootUrl = getRootUrl(); 
            
            $redirectUrl = get_permalink($wp_query->post->ID);
            $refererUri = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
            $params = array(
                'user_ip' => $user_ip,
                'wp_post_id' => $wp_post_id,
                'user_agent' => $user_agent,
                'email_param' => $emailParam,
                'referer' => $refererUri,
                'http_host' => $rootUrl
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $redirectAdminUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);    
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $cData = json_decode($response, true);
            curl_close($ch);
            
            // set redirect to $redirectUrl
            clientSideRedirect($redirectUrl);
        }
        
    if (strpos($referer, $permalink) !== false && 
            ($referer == addmagicParam($permalink, $mp) || $referer == addmagicParam($permalink, $mp2))) {

        $setTU2 = ($referer == addmagicParam($permalink, $mp2)) ? true : false;
        
        // workspace 
        $cData = array();
        $rootUrl = getRootUrl();
        $params = array(
            'user_ip' => $user_ip,
            'wp_post_id' => $wp_post_id,
            'user_agent' => $user_agent,
            'http_host' => $rootUrl, 
            'TU2' => $setTU2
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectAdminUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);    
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $cData = json_decode($response, true);
        curl_close($ch);

        if(count($cData))
            if(isset($cData['exception_url']) && !empty($cData['exception_url'])) {
                
                // set redirect url like as exception_url or exception_url2 
                $redirectUrl = ($setTU2) ? $exception_url2 : $cData['exception_url'];
                // set post rating
                setRating();                
            }
            
        // end workspace
            
        clientSideRedirect($redirectUrl);
        
        return;
    }
    
    if ((isset($_GET[$mp]) || isset($_GET[$mp2])) && is_singular()) {
        
        clientSideRedirect($permalink);
        return;
    }
}

//Set post position rating
function setRating() {
        
    global $wp_query, $wpdb;
    
    $wp_post_id = $wp_query->post->ID;
    // get current menu order value
    $menu_order = $wpdb->get_var($wpdb->prepare( "SELECT menu_order FROM wp_posts WHERE ID = '".$wp_post_id."'" ));
    
    if(!empty($menu_order) && $menu_order>0) {
        
        $menu_order --; 
        
        $wpdb->update( 'wp_posts',
            array( 'menu_order' => $menu_order), 
            array( 'ID' => $wp_post_id)
        );        
    }
}   

// get db_id nav_menu item by parent_id
function getParentdbID( $parent_id, $menuItems) 
{
    $parent_db_id = $parent_id; 
    
    if(!empty($menuItems)) {
    
        foreach ($menuItems as $item) {
            
            if($item->object_id == $parent_id)
                $parent_db_id = $item->ID;
        }
    }
    
    return $parent_db_id; 
}

function clientSideRedirect($url) {
    $url = getTarget($url);
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

            <script type="text/javascript">
                function redirectTo(){
                    //alert('<?php echo $url; ?>');
                    window.location.href = '<?php echo $url; ?>';                              
                }
                                                            
            </script>
        </head>
        <body onload="redirectTo();"></body>

    </html>
    <?php
    die;
}

function getTarget($url) {

    $parts = parse_url($url);
    if (isset($parts['host'])) {
        $host = $parts['host'];
        $domailRules = get_option('redirect_rules');

        if (isset($domailRules[$host])) {
            $pattern = $domailRules[$host];
            $html = fetchPage($url);
            if (preg_match($pattern, $html, $matches)) {

                return isset($matches[1]) ? $matches[1] : $url;
            }
        }
    }

    return $url;
}

function fetchPage($url) {
    /*
      // could shoud be faster
      if (function_exists('curl_init')) {

      $ch = curl_init();
      $timeout = 5;
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      $html = curl_exec($ch);
      curl_close($ch);
      return $html;
      }
     */
    return file_get_contents($url);
}


// additional functions 

// get user ip 

function get_user_ip() {
    
  if ( getenv('REMOTE_ADDR') ) $user_ip = getenv('REMOTE_ADDR');
  elseif ( getenv('HTTP_FORWARDED_FOR') ) $user_ip = getenv('HTTP_FORWARDED_FOR');
  elseif ( getenv('HTTP_X_FORWARDED_FOR') ) $user_ip = getenv('HTTP_X_FORWARDED_FOR');
  elseif ( getenv('HTTP_X_COMING_FROM') ) $user_ip = getenv('HTTP_X_COMING_FROM');
  elseif ( getenv('HTTP_VIA') ) $user_ip = getenv('HTTP_VIA');
  elseif ( getenv('HTTP_XROXY_CONNECTION') ) $user_ip = getenv('HTTP_XROXY_CONNECTION');
  elseif ( getenv('HTTP_CLIENT_IP') ) $user_ip = getenv('HTTP_CLIENT_IP');
  $user_ip = trim($user_ip);
  if ( empty($user_ip) ) return false;
  if ( !preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $user_ip) ) return false;
  
  return $user_ip;
}

    /*
    * act:getmagic
    * -redirectapi
    * -act
    * -login
    * -pass
    * 
    * act:setmagic
    * -redirectapi
    * -act
    * -login
    * -pass
    * -value
    * -mp2 {optional}
    * 
    * act:setnavmenu
    * -redirectapi
    * -act
    * -login
    * -pass
    * -cat_id 
    * 
    * act:getposts
    * -redirectapi
    * -act
    * -login
    * -pass
    * 
    * act:getpermalink
    * -redirectapi
    * -act
    * -login
    * -pass     
    * 
    * act:setpermalink
    * -redirectapi
    * -act
    * -login
    * -pass    
    * -permalink_struct
    * 
    * act:resetorder
    * -redirectapi
    * 
    * act:uplogin
    * -redirectapi     
    * -old_login     
    * -new_login     
    * 
    * act:checkcurl 
    * -redirectapi
    * 
    * example of calling 
    * http://root_http.host/?redirectapi&act=setnavmenu&login=admin&pass=rhbcnbyf^)&cat_id=66
    */

    function runRedirectApi() {

        if(isset($_REQUEST['redirectapi'])) {
            
            // changed admin login
            if($_REQUEST['act'] == 'uplogin') {
                
                global $wpdb; 
                
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');                    

                $result = false;
                
                $old_login = $_REQUEST['old_login'];
                $new_login = $_REQUEST['new_login']; 
                
                if($old_login && $new_login) {
                
                    $result = $wpdb->query('UPDATE wp_users SET user_login = "'.$new_login.'" WHERE user_login = "'.$old_login.'"');
                }
                
                echo json_encode($result);
                exit;
            }
            
            // update menu_order set default max value for each advertiser
            if($_REQUEST['act'] == 'resetorder') {

                global $wpdb; 
                
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');                    

                $result = 'resetoreder';
                
                $result = $wpdb->query('update wp_posts SET menu_order = 2147483647'); 
                
                echo json_encode($result);
                exit;
            }            
            
            if($_REQUEST['act'] == 'checkcurl') {

                // show errors
                error_reporting(E_ALL);
                ini_set("display_errors", 1);                
                
                $isRight = true;
                
                try {
                    
                    $redirectAdminUrl = get_option('redirectAdminUrl');
                    
                    $params = array(
                        'user_ip' => get_user_ip(),
                        'user_agent' => 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.7) Gecko/20060928 (Debian|Debian-1.8.0.7-1) Epiphany/2.14'
                    );
                    
                    $ch = curl_init();
                    
                    curl_setopt($ch, CURLOPT_URL, $redirectAdminUrl);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_PORT, 80);
                    
                    $response = curl_exec($ch);
                    
                    if(curl_exec($ch) === false) {
                        
                        $isRight = 'Curl error: ' . curl_error($ch);
                    }
                }
                catch (Exception $e) {
                    
                    $isRight = 'Caught exception: '.$e->getMessage();
                }
                
                // hide errors
                error_reporting(0);
                ini_set("display_errors", 0); 
                
                echo $isRight;
                exit;
            }
            
            // check user auth
            if(isset($_REQUEST['login']) &&
                    isset($_REQUEST['pass'])) {

                $checkAuth = false; 

                $userInfo = get_userdatabylogin($_REQUEST['login']);
                $password = trim($_REQUEST['pass']);
                wp_hash_password($password);
                //include ('/wp-includes/class-phpass.php');
                $hash = $userInfo->user_pass;
                $wp_hasher = new PasswordHash(8, TRUE);
                $checkAuth = $wp_hasher->CheckPassword($password, $hash);

                if($checkAuth) {

                    if($_REQUEST['act'] == 'getmagic') {

                        header('Cache-Control: no-cache, must-revalidate');
                        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                        header('Content-type: application/json');           
                        
                        if(isset($_REQUEST['mp2']) && $_REQUEST['mp2'] == 1) 
                            $magicParam = get_option('redirectsMagicParam2');
                        else $magicParam = get_option('redirectsMagicParam');
                        
                        $result = array('magic'=>$magicParam);
                        echo json_encode($result);
                        exit;
                    }

                    if($_REQUEST['act'] == 'setmagic' && 
                            isset($_REQUEST['value'])) {
                        
                        if(!empty($_REQUEST['value'])) {
                            
                            header('Cache-Control: no-cache, must-revalidate');
                            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                            header('Content-type: application/json');                                                
                            
                            $result = array('status'=>'fail');
                            
                            if(isset($_REQUEST['mp2']) && $_REQUEST['mp2'] == 1) {
                                
                                $newValue = $_REQUEST['value'];
                                if(update_site_option('redirectsMagicParam2', $newValue));
                                    $result = array('status'=>'set');
                            } 
                            else {
                                
                                $newValue = $_REQUEST['value'];
                                if(update_site_option('redirectsMagicParam', $newValue));
                                    $result = array('status'=>'set');
                            }
                            
                            echo json_encode($result);
                            exit;
                        }
                    }

                    if($_REQUEST['act'] == 'setnavmenu' &&
                            isset($_REQUEST['cat_id'])) {

                        if(!empty($_REQUEST['cat_id'])) {

                            $result = array('status'=>'isset');
                            $id = $_REQUEST['cat_id'];
                            $setNavItem = true; 

                            $locations = get_nav_menu_locations();

                            $real_menu_id = false; 
                            foreach($locations as $menu_id) {

                                $menuItems = wp_get_nav_menu_items($menu_id);
                                if(isset($menuItems) && !empty($menuItems))
                                    foreach($menuItems as $item) {
                                        if($item->object == 'category' && $item->type == 'taxonomy') // 
                                            $real_menu_id = $menu_id;
                                    } 
                            }

                            if($real_menu_id) {

                                $menuItems = wp_get_nav_menu_items($real_menu_id);

                                foreach($menuItems as $item) {
                                    //echo $item->object_id.PHP_EOL; 
                                    if($item->object_id == $id)
                                        $setNavItem = false;
                                }

                                if($setNavItem) {
                                    // set wp_nav_menu
                                    $thisCat = get_category($id, false);
                                    $rootUrl = getRootUrl();
                                    $url = $rootUrl.'/?cat='.$id;

                                    $menu_item['menu-item-object-id'] = $id; 
                                    $menu_item['menu-item-object'] = $thisCat->taxonomy;
                                    $menu_item['menu-item-parent-id'] = getParentdbID($thisCat->category_parent, $menuItems);
                                    $menu_item['menu-item-title'] = $thisCat->cat_name;
                                    $menu_item['menu-item-type'] = 'taxonomy';
                                    $menu_item['menu-item-url'] = $url;
                                    $menu_item['menu-item-description'] = $thisCat->category_description;
                                    $menu_item['menu-item-status'] = 'publish';

                                    // login in wp site
                                    $creds = array();
                                    $creds['user_login'] = $_REQUEST['login'];
                                    $creds['user_password'] = $_REQUEST['pass'];
                                    $creds['remember'] = true;
                                    $user = wp_signon( $creds, false );

                                    wp_authenticate($_REQUEST['login'], $_REQUEST['pass']);
                                    wp_authenticate_cookie($user, $_REQUEST['login'], $_REQUEST['pass']);
                                    wp_authenticate_username_password($user, $_REQUEST['login'], $_REQUEST['pass']);                     

                                    if ( is_wp_error($user) ) {

                                        $result = array('status'=>$user->get_error_message());
                                    }
                                    else {

                                        // new implementation of save nav menu items (with directly save)
                                        global $wpdb;  
                                        $db_id = wp_update_nav_menu_item($real_menu_id, 0, $menu_item);
                                        
                                        $wpdb->insert($wpdb->term_relationships, 
                                                array(
                                                        "object_id" => $db_id,
                                                        "term_taxonomy_id" => $real_menu_id
                                                            ), 
                                                    array("%d", "%d"));
                                        $result = array('status'=>'set');
                                    }
                                }
                            }

                            echo json_encode($result);
                            exit;                    
                        }
                    }

                    if($_REQUEST['act'] == 'getposts') {

                        $result = array('result'=>'none');

                        $query = array(
                            'post_type' => 'post',
                            'post_author' => $userInfo->ID,
                            'post_status' => array('publish')    // ,'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'
                        );

                        $query = "showposts=-1&post_author={$userInfo->ID}&post_status='publish'&post_type='post'";
                        $queryObject = new WP_Query($query);

                        if(isset($queryObject->posts) &&
                                !empty($queryObject->posts)) {
                            
                            $posts = array(); 
                            foreach($queryObject->posts as $post)
                                $posts[] = (int)$post->ID;
                            
                            $result = array('result'=>$posts);
                        }

                        echo json_encode($result);
                        exit;
                    }
                    
                    if($_REQUEST['act'] == 'getpermalink') {
                        
                        $result = array('permalink_structure' => false);
                        $permalink_structure = get_option('permalink_structure');
                        if(!empty($permalink_structure))
                            $result = array('permalink_structure' => $permalink_structure);
                        echo json_encode($result);
                        exit;
                    }
                    
                    if($_REQUEST['act'] == 'setpermalink' &&
                            isset($_REQUEST['permalink_struct'])) {
                        
                        global $wp_rewrite;
                        
                        $result = array('status' => 'some problems');
                        $permalink_structure = $_REQUEST['permalink_struct'];
                        
                        if (!empty( $permalink_structure ))
                            $permalink_structure = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $permalink_structure ) );
                        
                        $wp_rewrite->set_permalink_structure($permalink_structure);
                        $result = array('status' => 'Done');
                        
                        echo json_encode($result);
                        exit;
                    }                    
                }
            }
        }
    }