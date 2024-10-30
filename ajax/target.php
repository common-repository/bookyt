<?php

function bookyt_capacity_check() {
    global $wpdb; // this is how you get access to the database

    $frontend = $_POST["frontend"];
    unset($_POST["day"]);
    unset($_POST["frontend"]);

    $data=rawurlencode(json_encode($_POST));

    $url = $frontend."/api.php?call=calendar_capacity&json=".$data;
    //Daten laden
    $key = md5($url);
    $cache_duration=15*60;
    if (false === ( $json_data = get_transient( $key ) ) OR empty($json_data)) {
        //Daten neu holen
        $response = wp_remote_get( $url);
        $json_data     = wp_remote_retrieve_body( $response );
        set_transient( $key, $json_data, $cache_duration);
        //$Ausgabe.="Neu holen";
    }

    if(!bookyt_isJson($json_data))
    {
        return "invalid request";
    }
    $json = json_decode($json_data,true);

    header("Content-type:application/json; charset=utf-8");
    echo json_encode($json);

    wp_die(); // this is required to terminate immediately and return a proper response
}
