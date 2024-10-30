<?php


function bookyt_isJson($string) {

    if(!empty($string) AND is_array($string)){
        return false;
    }

    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function bookyt_cleanHTML($string){
    $string = preg_replace('#<a.*?>.*?</a>#i', '', $string);
    $string = strip_tags($string);
    $string = str_replace("\n"," ",$string);
    $string = str_replace("&nbsp;"," ",$string);
    $string = preg_replace('/[\t\n\r\0\x0B]/', '', $string);
    $string = preg_replace('/([\s])\1+/', ' ', $string);
    $string = trim($string);
    $string = esc_html($string);
    $string = str_replace("&nbsp;"," ",$string);
    return $string;
}

