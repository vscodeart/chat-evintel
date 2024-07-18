<?php
use Mailgun\Mailgun;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

/* This files includes all helper and utilities function used by various other major functions in the system */

// this function will be used to output jason responses with correct headers

function json_response($data=null, $status=200){
    header_remove();
    header("Content-Type: application/json");
    header('Status: ' . $status);
    $json = json_encode($data);
    if ($json === false) {
        $json = json_encode( utf8ize( $data ) );
        if ($json === false) {
            $json = json_encode(["jsonError" => json_last_error_msg()]);
            if ($json === false) {
                $json = '{"jsonError":"unknown"}';
            }   
            http_response_code(500);
        }
    }
    echo $json;
}

// Image upload function used in profile and settings sections
function image($files, $name=false, $path="", $height=false, $width=false){
    $image = new Image($files);
    // Config
    if ($name) {
        $image->setName($name);
    }
    $image->setSize(0, SETTINGS['post_max_size']);
    $image->setDimension(10000,10000);
    $image->setMime(array('jpeg', 'gif', 'png', 'jpg'));
    $image->setLocation('media/' . $path);
    //Upload
    if($image){
        $upload = $image->upload();
        $image_name = $upload->getName().'.'.$upload->getMime();
        if($upload){
            // Crop
            if ($height || $width) {
                if ($height == false) {
                    $height = $image->getHeight();
                }
                if ($width == false) {
                    $width = $image->getWidth();
                }

                $image = new ImageResize($upload->getFullPath());
                $image->crop($width, $height);
                $image->save($upload->getFullPath());

            }

            if (isset(SETTINGS['cloud_storage']) && SETTINGS['cloud_storage'] == true) {
                $result = app('s3')->putObject([
                    'Bucket' => SETTINGS['cloud_storage_bucket'],
                    'Key' => $path."/".$image_name,
                    'SourceFile' => "media/".$path."/".$image_name
                ]);
                unlink("media/".$path."/".$image_name);
            }
            return array(true, $image_name);
        }else{
            app('msg')->error($image->getError());
            return array(false, $image->getError());
        }
    }else{
        return array(false, "No Image Found!");
    }
    
}

// Image upload function used in chat dropzone
function video_thumb_upload($thumb_file_name, $thumb_file){
    
    $video_thumb_path = BASE_PATH. 'media'.DS.'chats'.DS.'videos'.DS.$thumb_file_name;
    base64_to_upload($thumb_file, $video_thumb_path);
    $image = new ImageResize($video_thumb_path);
    $image->crop(255, 155);
    $image->save($video_thumb_path);

    if (isset(SETTINGS['cloud_storage']) && SETTINGS['cloud_storage'] == true) {
        $result = app('s3')->putObject([
            'Bucket' => SETTINGS['cloud_storage_bucket'],
            'Key' => 'chats/videos/'.$thumb_file_name,
            'SourceFile' => $video_thumb_path
        ]);
        unlink($video_thumb_path);
    }
    
}

// Image upload function used in chat dropzone
function chat_image_upload($file){
    
    if (isset(SETTINGS['cloud_storage']) && SETTINGS['cloud_storage'] == true) {

        $image_new = new ImageResize($file['tmp_name']);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $image_new->resizeToWidth(600);
        $image_name = uniqid()."_".$image_new->getDestWidth()."x".$image_new->getDestHeight().'.'.$extension;
        $temp_file = tempnam(sys_get_temp_dir(), '');
        $image_new->save($temp_file);
        $result = app('s3')->putObject([
            'Bucket' => SETTINGS['cloud_storage_bucket'],
            'Key'    => 'chats/images/large/'.$image_name,
            'SourceFile' => $temp_file
        ]);
        try {
            gc_collect_cycles();
            unlink($temp_file);
        } catch (\Throwable $th) {
            //throw $th;
        }
        
        
        $medium_image_crop = new ImageResize($file['tmp_name']);
        $medium_image_crop->crop(300, 300);
        $temp_file = tempnam(sys_get_temp_dir(), '');
        $medium_image_crop->save($temp_file);
        $result = app('s3')->putObject([
            'Bucket' => SETTINGS['cloud_storage_bucket'],
            'Key'    => 'chats/images/medium/'.$image_name,
            'SourceFile' => $temp_file
        ]);
        try {
            gc_collect_cycles();
            unlink($temp_file);
        } catch (\Throwable $th) {
            //throw $th;
        }

        $thumb_image_crop = new ImageResize($file['tmp_name']);
        $thumb_image_crop->crop(220, 220);
        $temp_file = tempnam(sys_get_temp_dir(), '');
        $thumb_image_crop->save($temp_file);
        $result = app('s3')->putObject([
            'Bucket' => SETTINGS['cloud_storage_bucket'],
            'Key'    => 'chats/images/thumb/'.$image_name,
            'SourceFile' => $temp_file
        ]);
        try {
            gc_collect_cycles();
            unlink($temp_file);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $image_name;

    }else{

        $image = new Image($file);
        $image->setSize(0, SETTINGS['post_max_size']);
        $image->setDimension(10000,10000);
        $image->setMime(array('jpeg', 'gif', 'png', 'jpg'));
        $image->setLocation('media/chats/images/large');
        $image->setName(uniqid()."_".$image->getWidth()."x".$image->getHeight());
        //Upload
        if($image){
            
            $upload = $image->upload();
            if($upload){
                $ori_image_path = $upload->getFullPath();
                
                // Crop
                if($upload->getMime() != 'gif'){
                    $image_new = new ImageResize($upload->getFullPath());
                    $image_new->resizeToWidth(600);
                    $upload->setName(uniqid()."_".$image_new->getDestWidth()."x".$image_new->getDestHeight());
                    $image_new->save($upload->getFullPath());
                }

                // save medium image
                $medium_image = "media/chats/images/medium/".$upload->getName() .".". $upload->getMime();
                if(copy($upload->getFullPath(), $medium_image)){
                    $medium_image_crop = new ImageResize($medium_image);
                    $medium_image_crop->crop(300, 300);
                    $medium_image_crop->save($medium_image);
                }

                // save thumb image
                $thumb_image = "media/chats/images/thumb/".$upload->getName() .".". $upload->getMime();
                if(copy($upload->getFullPath(), $thumb_image)){
                    $thumb_image_crop = new ImageResize($thumb_image);
                    $thumb_image_crop->crop(220, 220);
                    $thumb_image_crop->save($thumb_image);
                }

                if($upload->getMime() != 'gif'){
                    unlink($ori_image_path);
                }
                return $upload->getName().'.'.$upload->getMime();
            }else{
                //$image->getError()
                return false;
            }
        }

    }

    
}

// Image upload function used in reaction section
function reaction_icon($files, $name=false, $path="", $height=false, $width=false){
    $image = new Image($files);
    // Config
    if ($name) {
        $image->setName($name);
    }
    
    $image->setSize(0, SETTINGS['post_max_size']);
    $image->setDimension(10000,10000);
    $image->setMime(array('jpeg', 'gif', 'png', 'jpg'));
    $image->setLocation('media/' . $path);
    $image->setExtension('gif');
    //Upload
    if($image){
        $upload = $image->upload();
        $image_name = $upload->getName().'.gif';
        if($upload){
            // Crop
            if ($height || $width) {
                if ($height == false) {
                    $height = $image->getHeight();
                }
                if ($width == false) {
                    $width = $image->getWidth();
                }

                $image = new ImageResize($upload->getFullPath());
                $image->crop($width, $height);
                $image->save($upload->getFullPath());

            }

            // save static image
            $static_image = "media/reactions/static_".$upload->getName() .".png";
            if(copy($upload->getFullPath(), $static_image)){
                $static_image_crop = new ImageResize($static_image);
                $static_image_crop->crop(20, 20);
                $static_image_crop->save($static_image, IMAGETYPE_PNG);
            }

            if (isset(SETTINGS['cloud_storage']) && SETTINGS['cloud_storage'] == true) {
                $result = app('s3')->putObject([
                    'Bucket' => SETTINGS['cloud_storage_bucket'],
                    'Key' => $path."/".$image_name,
                    'SourceFile' => "media/".$path."/".$image_name
                ]);
                unlink("media/".$path."/".$image_name);

                $static_image_name = $upload->getName().".png";
                $result = app('s3')->putObject([
                    'Bucket' => SETTINGS['cloud_storage_bucket'],
                    'Key'    => $path."/".$static_image_name,
                    'SourceFile' => "media/".$path."/".$static_image_name
                ]);
                unlink("media/".$path."/".$static_image_name);
            }
            return array(true, $image_name);
        }else{
            app('msg')->error($image->getError());
            return array(false, $image->getError());
        }
    }else{
        return array(false, "No Image Found!");
    }
    
}

// Send mail function to send reset password links and other emails
function send_mail($to, $subject, $body){
    if (isset(SETTINGS['send_emails_via']) && SETTINGS['send_emails_via'] == 'mailgun') {
        if(!empty(SETTINGS['mailgun_api_key'])){
            $from_email = array_key_exists("email_from_address", SETTINGS) ? SETTINGS['email_from_address'] : "chatnet@".$_SERVER['HTTP_HOST'];
            $from_name = array_key_exists("email_from_name", SETTINGS) ? SETTINGS['email_from_name'] : "ChatNet";
            // Instantiate Mailgun the SDK with your API credentials
            try {
                $mg = Mailgun::create(SETTINGS['mailgun_api_key'], SETTINGS['mailgun_base_url']);
                $mg->messages()->send(SETTINGS['mailgun_domain'], [
                    'from'    => $from_name.' <'.$from_email.'>',
                    'to'      => $to,
                    'subject' => $subject,
                    'html'    => $body
                ]);
                return true;
            } catch (\Exception $e) {
                app('msg')->error($e->getMessage());
                return false;
            }

        }else{
            app('msg')->error(__("Could not send the email, Mailgun API Key is Empty"));
            return false;
        }
    }else{
        try {
            //Recipients
            app('mail')->addAddress($to);
            // Content
            app('mail')->isHTML(true);
            app('mail')->Subject = $subject;
            app('mail')->Body = $body;
            app('mail')->send();
            return true;
        } catch (Exception $e) {
            app('msg')->error(app('mail')->ErrorInfo);
            return false;
        }
    }
    
}


// Crean input $_POST data and validate according to given rules
function clean_and_validate($key, $value){
    $value_and_rules = clean_get_validation_rules($key, $value);
    $value = $value_and_rules[0];
    $rules = $value_and_rules[1];

    $validator = new Valitron\Validator([$key => $value]);
    if($rules){
        foreach ($rules as $rule) {
            if(is_array($rule)){
                foreach ($rule as $key_rule => $rule_params) {
                    $validator->rule($key_rule, $key, $rule_params);
                }
            }else{
                $validator->rule($rule, $key);
            }
        }
    }

    if($validator->validate()){
        return array($value, array(true, ""));
    }else{
        return array($value, array(false, $validator->errors()));
    }
}

// get defined validation rules for given feilds
function clean_get_validation_rules($field, $value){
    if(in_array($field, array('footer_js', 'header_js', 'ad_chat_left_bar', 'ad_chat_right_bar', 'firebase_server_key'))){
        $value = @trim($value);
    }elseif (in_array($field, array('password'))) {
        $value = trim($value);
    }else{
        $value = clean($value);
        $value = app('purify')->xss_clean($value);
    }

    switch ($field) {
        case "site_name":
            return array($value, array('required', ['lengthMax' => '200']));
            break;
        case "contact_us_email":
        case "email":
        case "email_from_address":
            return array($value, array('required', 'email'));
            break;
        case "email_from_name":
            return array($value, array('required'));
            break;
        case "chat_receive_seconds":
        case "user_list_check_seconds":
        case "chat_status_check_seconds":
        case "online_status_check_seconds":
        case "typing_status_check_seconds":
            return array($value, array('required', 'integer', ['min' => '1']));
            break;
        case "home_bg_gradient_1":
        case "home_bg_gradient_2":
        case "home_text_color":
        case "home_header_bg_color":
        case "home_header_text_color":
        case "chat_userlist_bg_gradient_1":
        case "chat_userlist_bg_gradient_2":
        case "chat_userlist_text_color":
        case "chat_container_bg_gradient_1":
        case "chat_container_bg_gradient_2":
        case "chat_container_text_color":
        case "chat_container_received_bubble_color":
        case "chat_container_received_text_color":
        case "chat_container_username_text_color":
        case "chat_container_sent_bubble_color":
        case "chat_container_sent_text_color":
        case "chat_info_bg_gradient_1":
        case "chat_info_bg_gradient_2":
        case "chat_info_section_header_color":
        case "chat_info_text_color":
            return array($value, array(['regex' => '/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/']));
            break;
        case "max_message_length":
        case "tenor_gif_limit":
            return array($value, array(['min' => '1']));
            break;
        case "name":
            return array($value, array('required'));
            break;
        case "slug":
            return array($value, array('required', 'slug', ['lengthMax' => '50']));
            break;
        case "last_name":
        case "first_name":
            return array($value, array('required', ['lengthMax' => '20']));
            break;
        case "user_name":
            return array($value, array(['lengthMin' => '3'], ['lengthMax' => '50']));
            break;
        case "password":
            return array($value, array(['lengthMin' => '4'], ['lengthMax' => '20']));
            break;
        case "pin":
            return array($value, array(['lengthMin' => '3'], ['lengthMax' => '10']));
            break;
        case "homepage_chat_room_limit":
            return array($value, array(['min' => '1']));
            break;
        case "country":
            return array($value, array(['lengthMax' => '2']));
            break;
        case "title":
            return array($value, array('required', ['lengthMax' => '200']));
            break;     
        default:
            return array($value, false);
    }
}

// basic clean function to clean input data
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function purchase_verify($purchase_code, $email, $url){
	return array('status'=>'true','response'=>'response');
    $post = [
        'code' => $purchase_code,
        'email' => $email,
        'url'   => $url,
        'item_id'   => '3318260180',
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.oncodes.com/v1/envato/purchase_verify");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch);
    $return_data = @json_decode($output, true);
    return $return_data;
    curl_close($ch);
}


function get_timezone_list($selected_timezone=False){
    $opt = '';
    $regions = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');
    $tzs = timezone_identifiers_list();
    $optgroup = '';
    sort($tzs);
    $timestamp = time();
    if (!$selected_timezone) {
        $selected_timezone = SETTINGS['timezone'];
        if ((app('auth')->user() && app('auth')->user()['timezone'])) {
            $selected_timezone = app('auth')->user()['timezone'];
        }
    }

    foreach ($tzs as $tz) {
        $z = explode('/', $tz, 2);
        date_default_timezone_set($tz); //for each timezone offset
        $diff_from_GMT = 'GMT ' . date('P', $timestamp);
        if (count($z) != 2 || !in_array($z[0], $regions)){
            continue;
        }
        if ($optgroup != $z[0]) {
            if ($optgroup !== ''){
                $opt .= '</optgroup>';
            }
            $optgroup = $z[0];
            $opt .= '<optgroup label="' . htmlentities($z[0]) . '">';
        }

        $selected = "";
        if($selected_timezone == htmlentities($tz)){
            $selected = "selected";
        }
        $opt .= '<option value="' . htmlentities($tz) . '" '. $selected .' >'  . htmlentities(str_replace('_', ' ', $tz)). " - " .$diff_from_GMT . '</option>';
    }
    if ($optgroup !== ''){
        $opt .= '</optgroup>';
    }
    // change back system timezone
    date_default_timezone_set(SETTINGS['timezone']);
    return $opt;

}


function index_helper(){
    $data = array();

    if(array_key_exists("homepage_chat_room_limit", SETTINGS)){
        if (SETTINGS['homepage_chat_room_limit']) {
            $default_limit = SETTINGS['homepage_chat_room_limit'];
        }else{
            $default_limit = 6;
        }
    }else{
        $default_limit = 6;
    }
    app('db')->join("chat_rooms cr", "cr.id=cg.chat_room", "LEFT");
    app('db')->join("group_users gu", "gu.chat_group=cg.id", "LEFT");
    app('db')->where ('cg.slug', 'general');
    app('db')->where ('cr.status', 1);
    app('db')->where ('cr.is_visible', 1);
    app('db')->groupBy ('gu.chat_group, cr.id');


    if (isset(SETTINGS['chat_room_order'])) {
        if (SETTINGS['chat_room_order'] == 'newest_first') {
            app('db')->orderBy ('cr.id', 'DESC');
        }else if(SETTINGS['chat_room_order'] == 'oldest_first'){
            app('db')->orderBy ('cr.id', 'ASC');
        }else if(SETTINGS['chat_room_order'] == 'most_users_first'){
            app('db')->orderBy ('users_count', 'DESC');
        }else if(SETTINGS['chat_room_order'] == 'least_users_first'){
            app('db')->orderBy ('users_count', 'ASC');
        }
    }else{
        app('db')->orderBy ('users_count', 'DESC');
    }

    $chat_rooms = app('db')->get('chat_groups cg', array(0,$default_limit), 'cr.id, cr.name, cr.description, cr.cover_image, cr.is_protected, cr.password, cr.is_visible, cr.slug, cr.allowed_users, cr.status, cr.created_by, COUNT(gu.id) as users_count');


    $data['chat_rooms'] = $chat_rooms;
    $data['timezone_list'] = get_timezone_list();
    include('countries.php');
    $data['country_list'] = $countries;

    if (app('auth')->user()){
        app('db')->where ('user_id', app('auth')->user()['id']);
        $data['user_push_devices'] = app('db')->get('push_devices');
    }

    $data['lang_list'] = app('db')->get('languages');
    $data['title'] = SETTINGS['site_name'] . (isset(SETTINGS['site_tagline']) ? ' - '.SETTINGS['site_tagline'] : '');

    if (app('auth')->user() && in_array(app('auth')->user()['user_type'], array(1,2,4))) {
        if (app('auth')->user()['user_type'] == 2) {
            app('db')->where ('created_by', app('auth')->user()['id']);
        }
        $my_rooms_list = app('db')->get('chat_rooms');
        $my_rooms = array();
        foreach ($my_rooms_list as $my_room) {
            if($my_room['allowed_users'] && !empty($my_room['allowed_users'])){
                if(in_array(app('auth')->user()['user_type'], json_decode($my_room['allowed_users']))){
                    app('db')->join("chat_groups cg", "cg.id=gu.chat_group", "LEFT");
                    app('db')->where ('cg.chat_room', $my_room['id']);
                    app('db')->where ('cg.slug', 'general');
                    app('db')->get('group_users gu', null, 'gu.*');
                    $my_room['users_count'] = app('db')->count;
                    array_push($my_rooms, $my_room);
                }
            }
        }
        $data['my_rooms'] = $my_rooms;
    }

    if(isset(SETTINGS['custom_menus']) && SETTINGS['custom_menus'] == 1){
        
        app('db')->orderBy ('menu_order', 'ASC');
        app('db')->where ('status', 1);
        $data['menus'] = app('db')->get ('menus');
    }

    return $data;
}


function translate_term($term, $section=1, $lang=false ){
    if (get_setting('enable_multiple_languages')) {
        if(!$lang){
            $lang = app()->lang;
        }
        if (defined('LANG_TERMS')) { //defined('LANG_TERMS')
            if (isset(LANG_TERMS[$section][strtolower($term)])) {
                return LANG_TERMS[$section][strtolower($term)];
            }else{
                return $term;
            }
        }else{ 
            app('db')->where('term', $term);
            app('db')->where('section', $section);
            if ($lang_term = app('db')->getOne('lang_terms')) {
                app('db')->where('term_id', $lang_term['id']);
                app('db')->where('lang_code', $lang['code']);
                if ($lang_term = app('db')->getOne('translations')) {
                    return $lang_term['translation'];
                }else{
                    return $term;
                }
            }else{
                return $term;
            }
        }
    }else{
        return $term;
    }
}

function get_url_data_v2($url){
    $data = array(
        'title' => null,
        'description' => null,
        'image' => null,
        'code' => null
    );
    try {
        if ($url) {
            $info = Embed\Embed::create($url);
            $data['title'] = $info->title; 
            $data['description'] = $info->description;
            $data['image'] = $info->image;
            $data['code'] = urlencode($info->code);
        } 
    } catch (\Throwable $th) {
        //throw $th;
    }

    return $data;
}


function get_url_data($url) {
    $data = array(
        'title' => null,
        'description' => null,
        'image' => null
    );
    if ($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $page = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        $advanced = 1;
        if (stripos($contentType, 'text/html') !== false) {
            $doc = new DOMDocument();
            @$doc->loadHTML(mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8'));
            $nodes = $doc->getElementsByTagName('title');
            $data['title'] = $nodes->item(0)->nodeValue;
            if (empty($data['title'])) {
                $data['title'] = null;
            } else {
                $data['title'] = app('purify')->xss_clean(clean($data['title']));
            }
            $metas = $doc->getElementsByTagName('meta');
            for ($i = 0; $i < $metas->length; $i++) {
                //$meta = $metas->item($i);
                $meta = $metas[$i];
                if ($meta->getAttribute('property') == 'og:description') {
                    $data['description'] =  app('purify')->xss_clean(clean($meta->getAttribute('content')));
                }else if($meta->getAttribute('name') == 'description'){
                    $data['description'] = app('purify')->xss_clean(clean($meta->getAttribute('content')));
                }else if($meta->getAttribute('name') == 'Description'){
                    $data['description'] = app('purify')->xss_clean(clean($meta->getAttribute('content')));
                }
                if ($meta->getAttribute('property') == 'og:image') {
                    $data['image'] = $meta->getAttribute('content');
                }else if($meta->getAttribute('property') == 'twitter:image'){
                    $data['image'] = $meta->getAttribute('content');
                }
            }
        }

    }
    return $data;
}


function listFolderFiles($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value); 
        if (!is_dir($path)) {
            $results[] = array('path' => $path);
        } else if ($value != "." && $value != "..") {
            listFolderFiles($path, $results);
            $results[] = array('path' => $path);
        }
    }

    return $results;
}

function get_string_between($p_string, $p_from, $p_to, $p_multiple=true){
    $result = null;
    //checking for valid main string
    if (strlen($p_string) > 0) {
        //checking for multiple strings
        if ($p_multiple) {
            // getting list of results by end delimiter
            $result_list = explode($p_to, $p_string);
            //looping through result list array
            foreach ( $result_list AS $rlkey => $rlrow) {
                // getting result start position
                $result_start_pos   = strpos($rlrow, $p_from);
                // calculating result length
                $result_len         =  strlen($rlrow) - $result_start_pos;

                // return only valid rows
                if ($result_start_pos > 0) {
                    // cleanying result string + removing $p_from text from result
                    $result[] =   substr($rlrow, $result_start_pos + strlen($p_from), $result_len);
                }// end if
            } // end foreach

        // if single string
        } else {
            // result start point + removing $p_from text from result
            $result_start_pos   = strpos($p_string, $p_from) + strlen($p_from);
            // lenght of result string
            $result_length      = strpos($p_string, $p_to, $result_start_pos);
            // cleaning result string
            $result             = substr($p_string, $result_start_pos+1, $result_length );
        } // end if else
    // if empty main string
    } else {
        $result = false;
    } // end if else

    return $result;

}


// rebuild translation phrases
function collect_update_terms(){
    $dirs = array(
        BASE_PATH.'templates',
        BASE_PATH.'static',
        BASE_PATH.'utils',
        BASE_PATH.'app',
        BASE_PATH.'classes'
    );
    $exts = array(
        'html',
        'php'
    );
    $js_files = array(
        'chatnet.js',
        'scripts.js',
        'admin.js',
        'index.js'
    );
    $added_terms = array();
    foreach ($dirs as $dir) {

        $items = listFolderFiles($dir);
        foreach ($items as $item) {
            if (!is_dir($item['path'])) {
                $ext = pathinfo($item['path'], PATHINFO_EXTENSION);
                $file_name = pathinfo($item['path'], PATHINFO_BASENAME);
                if (in_array($ext, $exts) or in_array($file_name, $js_files)) {
                    $item_file_content = file_get_contents($item['path']);
                    $translate_phrases = get_string_between($item_file_content, '_(', ')');
                    
                    if ($translate_phrases) {
                        foreach ($translate_phrases as $translate_phrase) {
                           // $phrase_array = explode(',', $translate_phrase);
                           //$re = '/(?<=,)(?=(?:(?:[^`\'"]*[`\'"]){2})*[^`\'"]*$)/m';
                           $re = '/(?<=,)(?!\R)(?=(?:[^\'"]*([\'"])(?:\\.|(?!\1).)*\1)*[^\'"]*$)/m';
                           $phrase_array = preg_split($re,$translate_phrase);
                           
                            if (isset($phrase_array[0])) {
                                if (isset($phrase_array[1])) {
                                    $section = trim(rtrim(trim($phrase_array[1],',')));
                                }else{
                                    $section = 1;
                                }
                                $translate_phrase = $phrase_array[0];
                                $translate_phrase = rtrim($translate_phrase, ',');
                                $translate_phrase = substr($translate_phrase, 1);
                                $translate_phrase = substr($translate_phrase, 0, -1);
                                if (strlen(trim($translate_phrase))>1) {
                                    $sql = "SELECT * FROM cn_lang_terms WHERE term = ? AND (section = '".$section."' OR section IS NULL) LIMIT 1";
                                    $this_term_in_db = app('db')->rawQueryOne($sql, array($translate_phrase));
                                    if($this_term_in_db){
                                        if($this_term_in_db['section'] == null){
                                            $data = Array ("section" => $section);
                                            app('db')->where('id', $this_term_in_db['id']);
                                            $id = app('db')->update ('lang_terms', $data);
                                        }
                                    }else{
                                        $data = Array ("term" => $translate_phrase, "section" => $section);
                                        $id = app('db')->insert ('lang_terms', $data);
                                        if ($id) {
                                            $added_terms[] = $translate_phrase;
                                        }
                                    }  
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $added_terms;
}

function profanity_filter($text){
    if(app('profanity')->hasProfanity($text)){
        $words = preg_split("~\s+~", $text);
        foreach ($words as $key => $word) {
            $filtered = app('profanity')->obfuscateIfProfane($word);
            $words[$key] = $filtered;
        }
        return implode(' ', $words);
    }else{
        return $text;
    }
}


function __($term){
    return translate_term($term);
}

function send_push($token, $title, $message, $image, $url){
    if (!$url) {
        $url = URL;
    }
    if (SETTINGS['push_provider'] == 'firebase') {
        if (isset(SETTINGS['firebase_server_key']) && !empty(SETTINGS['firebase_server_key'])) {
            
            $firebase_server_key = html_entity_decode(SETTINGS['firebase_server_key']);
            $data = array();
            $data['title'] = $title;
            $data['body'] = $message;
            $data['icon'] = $image;
            $data['click_action'] = $url;

            $factory = (new Factory)->withServiceAccount($firebase_server_key);
            $messaging = $factory->createMessaging();
            $config = WebPushConfig::fromArray(['notification' => $data]);
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => $data
            ])->withWebPushConfig($config);
            $messaging->send($message);
            
        }
    }else if(SETTINGS['push_provider'] == 'pushy'){
       $post = array();
       $post['to'] = array($token);
        $post['data'] = array(
           'title' => $title,
           'message'  => $message,
           'image' => $image,
           'url' => $url
        );
        $headers = array(
           'Content-Type: application/json'
        );
       $post['notification'] =  array(
           'badge' => 1,
           'body'  => $message
        );

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/push?api_key=' . SETTINGS['pushy_api_key']);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
       $result = curl_exec($ch);
       curl_close($ch);
       //$response = @json_decode($result);
    }

}


function push_unsubscribe($token){
    if (SETTINGS['push_provider'] == 'pushy') {
        $post = array();
        $headers = array(
           'Content-Type: application/json'
        );
        $post['token'] = $token;
        $post['topics'] = ["*"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/topics/unsubscribe?api_key=' . SETTINGS['pushy_api_key']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
        $result = curl_exec($ch);
        curl_close($ch);
        //$response = @json_decode($result);
    }
}

function hybridauth($sconfig){
    $hybridauth = new Hybridauth\Hybridauth($sconfig);
    app()->hybridauth = new Hybridauth\Hybridauth($sconfig);
    $adapters = $hybridauth->getConnectedAdapters();
    return $hybridauth;
}

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}


function download_image($url, $temp_file_name){
    $fp = fopen ($temp_file_name, 'w+');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36');
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    // Crop
    $image = new ImageResize($temp_file_name);
    $image->crop(220, 220);
    $image->save($temp_file_name);
    if ($image) {
        return $temp_file_name;
    }else{
        return false;
    }

}

function get_social_logins(){
    $social_logins = array(
        ['name' => 'Amazon', 'spec' => 'OAuth2'],
        ['name' => 'Apple', 'spec' => 'OAuth2'],
        ['name' => 'Authentiq', 'spec' => 'OAuth2'],
        ['name' => 'BitBucket', 'spec' => 'OAuth2'],
        ['name' => 'Blizzard', 'spec' => 'OAuth2'],
        ['name' => 'BlizzardAPAC', 'spec' => 'OAuth2'],
        ['name' => 'BlizzardEU', 'spec' => 'OAuth2'],
        ['name' => 'DeviantArt', 'spec' => 'OAuth2'],
        ['name' => 'Discord', 'spec' => 'OAuth2'],
        ['name' => 'Disqus', 'spec' => 'OAuth2'],
        ['name' => 'Dribbble', 'spec' => 'OAuth2'],
        ['name' => 'Dropbox', 'spec' => 'OAuth2'],
        ['name' => 'Facebook', 'spec' => 'OAuth2'],
        ['name' => 'Foursquare', 'spec' => 'OAuth2'],
        ['name' => 'GitHub', 'spec' => 'OAuth2'],
        ['name' => 'GitLab', 'spec' => 'OAuth2'],
        ['name' => 'Google', 'spec' => 'OAuth2'],
        ['name' => 'Instagram', 'spec' => 'OAuth2'],
        ['name' => 'LinkedIn', 'spec' => 'OAuth2'],
        ['name' => 'Mailru', 'spec' => 'OAuth2'],
        ['name' => 'Medium', 'spec' => 'OAuth2'],
        ['name' => 'MicrosoftGraph', 'spec' => 'OAuth2'],
        ['name' => 'Odnoklassniki', 'spec' => 'OAuth2'],
        ['name' => 'ORCID', 'spec' => 'OAuth2'],
        ['name' => 'Patreon', 'spec' => 'OAuth2'],
        ['name' => 'Pinterest', 'spec' => 'OAuth2'],
        ['name' => 'QQ', 'spec' => 'OAuth2'],
        ['name' => 'Reddit', 'spec' => 'OAuth2'],
        ['name' => 'Slack', 'spec' => 'OAuth2'],
        ['name' => 'Spotify', 'spec' => 'OAuth2'],
        ['name' => 'StackExchange', 'spec' => 'OAuth2'],
        ['name' => 'Steam', 'spec' => 'Hybrid'],
        ['name' => 'SteemConnect', 'spec' => 'OAuth2'],
        ['name' => 'Strava', 'spec' => 'OAuth2'],
        ['name' => 'Telegram', 'spec' => 'Hybrid'],
        ['name' => 'TwitchTV', 'spec' => 'OAuth2'],
        ['name' => 'Vkontakte', 'spec' => 'OAuth2'],
        ['name' => 'WeChat', 'spec' => 'OAuth2'],
        ['name' => 'WeChatChina', 'spec' => 'OAuth2'],
        ['name' => 'WindowsLive', 'spec' => 'OAuth2'],
        ['name' => 'WordPress', 'spec' => 'OAuth2'],
        ['name' => 'Yahoo', 'spec' => 'OAuth2'],
        ['name' => 'Yandex', 'spec' => 'OAuth2']
    );
    return $social_logins;
}

function base64_to_upload($base64_string, $output_file) {
    $file = fopen($output_file, "wb");
    $data = explode(',', $base64_string);
    fwrite($file, base64_decode($data[1]));
    fclose($file);
    return $output_file;
}


function get_social_config(){
    app('db')->where('status', 1);
    app('db')->where('id_key',  NULL, 'IS NOT');
    app('db')->where('secret_key',  NULL, 'IS NOT');
    $social_logins = app('db')->get('social_logins');
    $providers = [];
    foreach ($social_logins as $social_login) {
        if (!empty($social_login['id_key']) && !empty($social_login['secret_key'])) {
            $each_provider_keys = [];
            if($social_login['name'] == "Apple"){
                $each_provider_keys['id'] = $social_login['id_key'];
                $each_provider_keys['team_id'] = $social_login['secret_key'];
                $each_provider_keys['key_id'] = '';
                //$each_provider_keys['key_content'] = <<<EOD
                //EOD;
            }else{
                $each_provider_keys['id'] = $social_login['id_key'];
                $each_provider_keys['secret'] = $social_login['secret_key'];
            }
            
            $each_provider = [];
            $each_provider['enabled'] = true;
            $each_provider['keys'] = $each_provider_keys;
            $providers[$social_login['name']] = $each_provider;
        }
    }
    $sconfig = [];
    $sconfig['callback'] = route('sociallogin-callback');
    $sconfig['providers'] = $providers;
    return $sconfig;
}


function get_login_page(){
    $data = array();
    $data['title'] = __("Sign In").' - ' . SETTINGS['site_name'];
    if (isset(SETTINGS['guest_mode'])  && SETTINGS['guest_mode'] == 1) {
        $data['guest_data'] = app('auth')->nextGuestUser();
        include(BASE_PATH.'utils'.DS.'countries.php');
        $data['country_list'] = $countries;
        $data['timezone_list'] = get_timezone_list();
    }
    if (isset(SETTINGS['enable_social_login']) && SETTINGS['enable_social_login'] == 1) {
        $data['hybridauth_providers'] = hybridauth(get_social_config())->getProviders();
    }
    if (isset($_GET['next']) && (filter_var($_GET['next'], FILTER_VALIDATE_URL) != false)) {
        $data['next_url'] = $_GET['next'];
        if(strpos($_GET['next'], '/dashboard') !== false){
            $data['admin_login'] = true;		
        }
    }
    echo app('twig')->render('login.html', $data);

}


function get_registration_page($post_data=false){
    $data = array();
    if($post_data){
        $data = array_merge($data, $post_data);
    }
    $data['title'] = __("Sign Up").' - ' . SETTINGS['site_name'];
    if (isset(SETTINGS['autodetect_country']) && SETTINGS['autodetect_country']) {
        $geoip = getGeoIP('getClientIP()');
        if($geoip && isset($geoip['countryCode'])){
            $data['auto_country_code'] = $geoip['countryCode'];
        }else{
            $data['auto_country_code'] = "US";
        }
    }
    include(BASE_PATH.'utils'.DS.'countries.php');
    $data['country_list'] = $countries;
    $data['timezone_list'] = get_timezone_list();
    echo app('twig')->render('register.html', $data);
}

function recaptcha_v2_verify($response){
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => SETTINGS['recaptcha_secretkey'],
        'response' => $response
    );
    $options = array(
        'http' => array (
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success=json_decode($verify);

    if ($captcha_success->success==false) {
        return false;
    } else if ($captcha_success->success==true) {
        return true;
    }
}

function recaptcha_v3_verify($response){
	    // Build POST request:
	    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
	    $recaptcha_secret = SETTINGS['recaptcha_secretkey'];
	    // Make and decode POST request:
	    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $response);
	    $recaptcha = json_decode($recaptcha);
	    // Take action based on the score returned:
	    if (property_exists ( $recaptcha , 'score' ) && $recaptcha->score >= SETTINGS['recaptcha_v3_pass_score']) {
	        // reCaptcha Verified
			return true;
	    } else {
			return false;
	    }
}

function getClientIP(){
    $ip = '';
    if (isset($_SERVER)){
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            if(strpos($ip,",")){
                $exp_ip = explode(",",$ip);
                $ip = $exp_ip[0];
            }
        }else if(isset($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }
    }else{
        if(getenv("HTTP_X_FORWARDED_FOR")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
            if(strpos($ip,",")){
                $exp_ip=explode(",",$ip);
                $ip = $exp_ip[0];
            }
        }else if(getenv("HTTP_CLIENT_IP")){
            $ip = getenv("HTTP_CLIENT_IP");
        }else {
            $ip = getenv("REMOTE_ADDR");
        }
    }
    return $ip;
}

function getGeoIP($ip=false){
    if($ip){
        $endpoint = SETTINGS['geoip_api_endpoint'].$ip;
    }else{
        $endpoint = SETTINGS['geoip_api_endpoint'];
    }
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "content-type: application/json"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if (!$err) {
      return json_decode($response, true);
    }

}


function isAllowedIp($ip, array $whitelist){
    $ip = (string)$ip;
    if (in_array($ip, $whitelist, true)) {
        // the given ip is found directly on the whitelist --allowed
        return true;
    }
    // go through all whitelisted ips
    foreach ($whitelist as $whitelistedIp) {
        $whitelistedIp = (string)$whitelistedIp;
        // find the wild card * in whitelisted ip (f.e. find position in "127.0.*" or "127*")
        $wildcardPosition = strpos($whitelistedIp, "*");
        if ($wildcardPosition === false) {
            // no wild card in whitelisted ip --continue searching
            continue;
        }
        // cut ip at the position where we got the wild card on the whitelisted ip
        // and add the wold card to get the same pattern
        if (substr($ip, 0, $wildcardPosition) . "*" === $whitelistedIp) {
            // f.e. we got
            //  ip "127.0.0.1"
            //  whitelisted ip "127.0.*"
            // then we compared "127.0.*" with "127.0.*"
            // return success
            return true;
        }
    }
    // return false on default
    return false;
}

function get_setting($name, $get_from_db=false){
    if (!$get_from_db) {
        if(isset(SETTINGS[$name])){
            return SETTINGS[$name];
        }else{
            return false;
        }
    }else{
        // TODO:
    }

}

function user_agent_browser($user_agent){
    if(strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) {
        return 'Opera';
    }elseif (strpos($user_agent, 'Edge')) {
        return 'Edge';
    }elseif (strpos($user_agent, 'Chrome')) {
        return 'Chrome';
    }elseif (strpos($user_agent, 'Safari')) {
        return 'Safari';
    }elseif (strpos($user_agent, 'Firefox')) {
        return 'Firefox';
    }elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
        return 'Internet Explorer';
    }else{
        return '';
    }
}

function user_agent_platform($user_agent){
    if(preg_match('/linux/i', $user_agent)) {
        return 'linux';
    }elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        return 'mac';
    }elseif (preg_match('/windows|win32/i', $user_agent)) {
        return 'windows';
    }else{
        return '';
    }
}

function user_agent_string($user_agent){
    $browser = user_agent_browser($user_agent);
    $platform = user_agent_platform($user_agent);
    if($browser && $platform){
        $return_string = $browser.' '.__("on").' '.$platform;
    }elseif($browser){
        $return_string = $browser;
    }elseif($platform){
        $return_string = $platform;
    }else{
        $return_string = __("Not Found");
    }
    return $return_string;
}

function get_chat_small_preview($message, $message_type){
    if($message_type == 1){
        $msg_preview = $message;
    }else if($message_type == 2){
        $msg_preview = __("Image");
    }else if($message_type == 3){
        $msg_preview = __("GIF");
    }else if($message_type == 4){
        $msg_preview = __("Sticker");
    }else if($message_type == 5){
        $msg_preview = __("Link");
    }else if($message_type == 6){
        $msg_preview = __("File");
    }else if($message_type == 7){
        $msg_preview = __("Audio");
    }else if($message_type == 8){
        $msg_preview = __("Reply Message");
    }else if($message_type == 9){
        $msg_preview = __("Forwarded Message");
    }
    return $msg_preview;
}

function send_notification($to_user, $active_room, $message, $message_type){
    if (isset(SETTINGS['push_notifications']) && SETTINGS['push_notifications']==true) {
        $from_user = app('auth')->user();
        if ($from_user['avatar']) {
            $from_user['avatar_url'] = MEDIA_URL."/avatars/".$from_user['avatar'];
        } else {
            $from_user['avatar_url'] = URL."static/img/default_push.jpg";
        }
        if ($to_user) {
            app('db')->where('user_id', $to_user);
            app('db')->where('perm_private', 1);
            $user_devices = app('db')->get('push_devices');

            if ($user_devices) {
                app('db')->where('id', $active_room);
                $active_room = app('db')->getOne('chat_rooms');

                if (isset(SETTINGS['display_name_format']) && SETTINGS['display_name_format'] == 'username') {
                    $push_name = $from_user['user_name'];
                }else{
                    $push_name = $from_user['first_name'] . " " . $from_user['last_name'];
                }
                try {
                    $chat_small_preview = get_chat_small_preview($message, $message_type);
                    $chat_small_preview_enc = html_entity_decode(html_entity_decode($chat_small_preview), ENT_QUOTES | ENT_IGNORE, "UTF-8");
                } catch (\Throwable $th) {
                    $chat_small_preview_enc = "";
                }
                
                foreach ($user_devices as $user_device) {
                    if ($user_device['token']) {
                        send_push(
                            $user_device['token'],
                            __("New Message From ") . $push_name,
                            $chat_small_preview_enc ,
                            $from_user['avatar_url'],
                            route('chat-room', array('chatroomslug' => $active_room['slug'].'/'.$from_user['user_name']))
                        );
                    }
                }
            }
        }
    }
}

function cn_setcookie($name, $value, $time, $path){
    if (PHP_VERSION_ID < 70300) {
        setcookie($name, $value, $time, $path . '; samesite=None', '', true, 'true');
        return;
    }
    setcookie($name, $value, [
        'expires' =>  $time,
        'path' => $path,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}


function utf8ize( $mixed ) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}


function checkLinkValidity($link){
    if (isset(SETTINGS['domain_filter']) && SETTINGS['domain_filter']==true) {
        $domains_list = str_replace(' ', '', SETTINGS['domains_list']);
        $domains_list = explode(',', $domains_list);
        $parse = parse_url($link);
        if (SETTINGS['domain_filter_type'] == 'blacklist') {
            if (isset($parse['host']) && in_array($parse['host'], $domains_list)) {
                return false;
            }else{
                return true;
            }
        }else if(SETTINGS['domain_filter_type'] == 'whitelist'){
            if (isset($parse['host']) && in_array($parse['host'], $domains_list)) {
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
        
    }else{
        return true;
    }    
}

function get_default_term($term){
    $lang_term = translate_term($term);
    if($term == $lang_term){
        switch ($term) {
            case "report_reason_1":
                return 'Spam';
                break;
            case "report_reason_2":
                return 'Hate Speech';
                break;
            case "report_reason_3":
                return 'Sexually Explicit Content';
                break;
            case "report_reason_4":
                return 'Harassment or Bullying';
                break;   
            case "report_reason_5":
                return 'Suicide or Self-Injury';
                break;     
            case "report_reason_6":
                return 'Pretending to Be Someone';
                break;    
            case "report_reason_7":
                return 'Sharing Inappropriate Things';
                break;     
            case "report_reason_8":
                return 'Organizing or Promoting Violence';
                break;     
            case "report_reason_9":
                return 'Unauthorized Sales';
                break;     
            case "report_reason_10":
                return 'Other';
                break;    
            default:
                return $term;
        }
    }else{
        return $lang_term;
    }

}

function getUserAvatarURL($user_data){
    if($user_data){
        if ($user_data['avatar']) {
            if (!filter_var($user_data['avatar'], FILTER_VALIDATE_URL) === false) {
                $avatar_url = $user_data['avatar'];
            } else {
                $avatar_url = MEDIA_URL."/avatars/".$user_data['avatar'];
            }
        } else {
            if ($user_data['user_type'] == 3) {
                $avatar_url = URL."static/img/user.png";
            }else{
                if(preg_match('/^[A-Za-z]/', $user_data['first_name'])){
                    $avatar_url = URL."static/img/letters/".strtoupper($user_data['first_name'][0]).'.svg';
                }else{
                    $avatar_url = URL."static/img/user.png";
                }
            }
        }
    }else{
        $avatar_url = URL."static/img/user.png";
    }
    return $avatar_url;
}


function addToMailgunList($email, $name, $data){
    if(!empty(SETTINGS['mailgun_list']) && !empty(SETTINGS['mailgun_api_key'])){
        $mgClient = Mailgun::create(SETTINGS['mailgun_api_key']);
        $mgClient->mailingList()->member()->create(
            SETTINGS['mailgun_list'],
            $email,
            $name,
            $data
        );
    }
}



function uploadFile($files){

    $uploaded_file_and_image = array();
    $mime_types = array(
        'pdf' => array('application/pdf'),
    );

    if ($files['error'] == 0) {

        $file_path = $files['tmp_name'];

        $file_type_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_type_info, $file_path);

        $ext_list = array();
        foreach ($mime_types as $key => $value) {
            if(in_array($file_type, $value)){
                array_push($ext_list, $key);
            }
        }
        // var_dump($ext_list);
        $enable_files = str_replace('.', '', array_map('trim', explode(",",SETTINGS['enable_file_list'])));

        if(!empty(array_intersect($ext_list, $enable_files))){

            $extension = pathinfo($files['name'], PATHINFO_EXTENSION);
            $file_name = trim(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'),1,32));
            $original_file_name = pathinfo($files['name'], PATHINFO_FILENAME);
            $original_file_name = preg_replace( '/[\W]/', '', $original_file_name);
            if (empty($original_file_name)) {
                $original_file_name = 'file';
            }
            $tmp_name = $files['tmp_name'];
            $size = $files['size'];
            $full_file_name = $original_file_name.'.'.$file_name.'.'.$extension;

            $return_array = array();
            $return_array['name'] = $full_file_name;
            $return_array['extenstion'] = $extension;
            $return_array['size'] = app('chat')->humanFileSize($size);
            move_uploaded_file($tmp_name, "media/chats/files/".$full_file_name);
            return $full_file_name;
        }
return false;

    }else{
        // file size error
    }
}


?>
