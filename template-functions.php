<?php

if ( !function_exists('print_lang_value') ) {
    function print_lang_value($value, $lang_code="en", $echo=true){
        if ( is_array($value) ){
            foreach($value as $current_value){
                $print_values[] = get_lang_value($current_value, $lang_code);
            }

            if ( $echo ) {
                echo implode(', ', $print_values);
            } else {
                return implode(', ', $print_values);
            }
        }else{
            if ( $echo ) {
                echo get_lang_value($value, $lang_code);
            } else {
                return get_lang_value($value, $lang_code);
            }
        }
    }
}

if ( !function_exists('get_lang_value') ) {
    function get_lang_value($string, $lang_code, $default_lang_code='en'){
        $lang_value = array();
        $occs = preg_split('/\|/', $string);

        foreach ($occs as $occ){
            $re_sep = (strpos($occ, '~') !== false ? '/\~/' : '/\^/');
            $lv = preg_split($re_sep, $occ);
            $lang = substr($lv[0],0,2);
            $value = $lv[1];
            $lang_value[$lang] = $value;
        }

        if ( isset($lang_value[$lang_code]) ){
            $translated = $lang_value[$lang_code];
        }else{
            $translated = $lang_value[$default_lang_code];
        }

        return trim($translated);
    }
}


if ( !function_exists('print_formated_date') ) {
    function print_formated_date($string){
        echo substr($string,6,2)  . '/' . substr($string,4,2) . '/' . substr($string,0,4);
    }
}

if ( !function_exists('isUTF8') ) {
    function isUTF8($string){
        return (utf8_encode(utf8_decode($string)) == $string);
    }
}

if ( !function_exists('translate_label') ) {
    function translate_label($texts, $label, $group=NULL) {
        // labels on texts.ini must be array key without spaces
        $label_norm = preg_replace('/[&,\'\s]+/', '_', $label);
        if($group == NULL) {
            if(isset($texts[$label_norm]) and $texts[$label_norm] != "") {
                return $texts[$label_norm];
            }
        } else {
            if(isset($texts[$group][$label_norm]) and $texts[$group][$label_norm] != "") {
                return $texts[$group][$label_norm];
            }
        }
        // case translation not found return original label ucfirst
        return ucfirst($label);
    }
}

if ( !function_exists('real_site_url') ) {
    function real_site_url($path = ''){

        $site_url = get_site_url();

        // check for multi-language-framework plugin
        if ( function_exists('mlf_parseURL') ) {
            global $mlf_config;

            $current_language = substr( strtolower(get_bloginfo('language')),0,2 );

            if ( $mlf_config['default_language'] != $current_language ){
                $site_url .= '/' . $current_language;
            }
        }
        // check for polylang plugin
        elseif ( defined('POLYLANG_VERSION') ) {
            $defLang = pll_default_language();
            $curLang = pll_current_language();

            if ($defLang != $curLang) {
                $site_url .= '/' . $curLang;
            }
        }

        if ($path != ''){
            $site_url .= '/' . $path;
        }
        $site_url .= '/';


        return $site_url;
    }
}

if ( !function_exists('get_publication_language') ) {
    function get_publication_language($labels, $lang){
        $publication_language = '';

        if ( $labels ) {
            foreach ($labels as $label) {
                if (strpos($label, $lang) === 0) {
                    $arr = explode('^', $label);
                    $publication_language = $arr[1];
                }
            }
        }

        return $publication_language;
    }
}

if ( !function_exists('get_relatos_attachment') ) {
    function get_relatos_attachment($resource, $upload_type=''){
        global $relatos_service_url;

        $relatos_files = array();
        $submission_id = $resource->main_submission->id;
        $submissions = wp_list_pluck( $resource->submission, 'id' );
        $attachments = $resource->main_submission->attachments;

        foreach ($attachments as $file) {
            if ( $upload_type == $file->upload_type->slug ) {
                if ( 'image' == $upload_type ) {
                    $img_src = $relatos_service_url . '/uploads/' . str_pad($submission_id, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;
                    $img_type_check = @exif_imagetype($img_src);

                    if (strpos($http_response_header[0], "200")) {
                        $relatos_files[] = $img_src;
                    } else {
                        foreach ($submissions as $submission) {
                            $img_src = $relatos_service_url . '/uploads/' . str_pad($submission, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;
                            $img_type_check = @exif_imagetype($img_src);

                            if (strpos($http_response_header[0], "200")) {
                                $relatos_files[] = $img_src;
                                break;
                            }
                        }
                    }
                } else {
                    $uri = $relatos_service_url . '/uploads/' . str_pad($submission_id, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;

                    if (is_uri($uri)) {
                        $relatos_files[] = $uri;
                    } else {
                        foreach ($submissions as $submission) {
                            $uri = $relatos_service_url . '/uploads/' . str_pad($submission, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;

                            if (is_uri($uri)) {
                                $relatos_files[] = $uri;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $relatos_files;
    }
}

if ( !function_exists('get_relatos_images') ) {
    function get_relatos_images($resource){
        global $relatos_service_url;

        $relatos_images = array();
        $submission_id = $resource->main_submission->id;
        $submissions = wp_list_pluck( $resource->submission, 'id' );
        $attachments = $resource->main_submission->attachments;

        foreach ($attachments as $file) {
            $upload_type = $file->upload_type->slug;

            if ( 'image' == $upload_type ) {
                $img_src = $relatos_service_url . '/uploads/' . str_pad($submission_id, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;
                $img_type_check = @exif_imagetype($img_src);

                if (strpos($http_response_header[0], "200")) {
                    $relatos_images[] = $img_src;
                } else {
                    foreach ($submissions as $submission) {
                        $img_src = $relatos_service_url . '/uploads/' . str_pad($submission, 5, '0', STR_PAD_LEFT) . '/' . $file->filename;
                        $img_type_check = @exif_imagetype($img_src);

                        if (strpos($http_response_header[0], "200")) {
                            $relatos_images[] = $img_src;
                            break;
                        }
                    }
                }
            }
        }

        return $relatos_images;
    }
}

if ( !function_exists('get_responsible_image') ) {
    function get_responsible_image($resource, $filename){
        global $relatos_service_url;

        $relatos_images = array();
        $submission_id = $resource->main_submission->id;
        $submissions = wp_list_pluck( $resource->submission, 'id' );
        $attachments = $resource->main_submission->attachments;

        $img_src = $relatos_service_url . '/uploads/images/' . str_pad($submission_id, 5, '0', STR_PAD_LEFT) . '/' . $filename;
        $img_type_check = @exif_imagetype($img_src);

        if (strpos($http_response_header[0], "200")) {
            $relatos_images[] = $img_src;
        } else {
            foreach ($submissions as $submission) {
                $img_src = $relatos_service_url . '/uploads/images/' . str_pad($submission, 5, '0', STR_PAD_LEFT) . '/' . $filename;
                $img_type_check = @exif_imagetype($img_src);

                if (strpos($http_response_header[0], "200")) {
                    $relatos_images[] = $img_src;
                    break;
                }
            }
        }

        return $relatos_images;
    }
}


if ( !function_exists('get_relatos_targets') ) {
    function get_relatos_targets($targets, $lang){
        $relatos_targets = array();

        $texts = array_map(function($val) {
            return explode('|', $val);
        }, $targets);

        foreach ($texts as $text) {
            $keys = array_map(function($val) {
                return explode('^', $val){0};
            }, $text);

            $values = array_map(function($val) {
                return explode('^', $val)[1];
            }, $text);

            $t = array_combine($keys, $values);

            if ( array_key_exists($lang, $t) ) {
                $relatos_targets[] = $t[$lang];
            } else {
                $relatos_targets[] = $t['en'];
            }
        }

        return $relatos_targets;
    }
}

if ( !function_exists('is_uri') ) {
    function is_uri($uri) {
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            $status = true;
        } else {
            $status = false;
        }
        curl_close($ch);
        return $status;
    }
}

if ( !function_exists('slugify') ) {
    function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}

?>
