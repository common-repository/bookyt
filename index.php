<?php
/*
Plugin Name: Bookyt Vermietsoftware
Plugin URI: https://bookyt.de/support/wordpress-plugin
Description: Einbinden der Bookyt Vermietsoftware Onlinebuchung
Version: 1.99
Author: Philipp Stäbler (PHCOM GmbH)
Author URI: https://phcom.de/
Text Domain: bookyt
License: GPLv2
Released under the GNU General Public License (GPL)
https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
*/

include( plugin_dir_path( __FILE__ ) . 'libs/functions.php');
include( plugin_dir_path( __FILE__ ) . 'ajax/target.php');

function bookyt ($atts )
{
    $Ausgabe="";
    $a = shortcode_atts( array(
        'account' => '',
        'token' => '',
        'station_id' => '',
        'call' => ''
    ), $atts );

    //Standard-Call
    if(empty($a['call']))
    {
        $a['call']="category";
    }

    if(empty($a['account']) OR empty($a['token']))
    {
        return "Please define account and token for the request";
    }

    $url = "https://".$a['account'].".bookyt.de/api.php?token=".$a['token']."&call=".$a['call'];

    //Daten laden
    $key = md5($url);
    $cache_duration=15*60;
    if (false === ( $json_data = get_transient( $key ) ) ) {
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

    $counter=0;
    if(!empty($json) AND is_array($json))
    {
        if($a['call']=="calendar")
        {
            $Ausgabe.="<div class='wp-bookyt-calendar' frontend='{$json["frontend_url"]}'>";

            //Suchfilter
            $Ausgabe.="<div class='wp-block-columns'>";

            //Mietdauer
            $Ausgabe.="<div class='wp-block-column'>";
            $Ausgabe.="<select name='mietdauer' class='mietdauer' onchange='bookytAjaxRequestShowAvailable()'>";
            foreach($json["intervalle"] as $intervall)
            {
                $Ausgabe.="<option value='{$intervall["intervall"]}'>".$intervall["caption"]."</option>";
            }
            $Ausgabe.="</select>";
            $Ausgabe.="</div>";

            //Station
            $Ausgabe.="<div class='wp-block-column'>";
            $Ausgabe.="<select name='station_id' class='station' onchange='bookytSetCategorie(this);'>";
            foreach($json["stationen"] as $station)
            {
                $categories = json_encode($station["categories"]);

                $attr="";
                if($a['station_id']==$station["station_id"]){
                    $attr=" selected ";
                }

                $Ausgabe.="<option value='{$station["station_id"]}' {$attr} categories='".rawurlencode($categories)."'>".$station["caption"]."</option>";
            }
            $Ausgabe.="</select>";
            $Ausgabe.="</div>";

            //Gruppe
            $Ausgabe.="<div class='wp-block-column'>";
            $Ausgabe.="<select name='gruppe_id' class='gruppe' onchange='bookytAjaxRequestShowAvailable()'></select>";
            $Ausgabe.="</div>";

            //Schließen der 1. Zeile
            $Ausgabe.="</div>";

            //2. Zeile - Kalender
            $Ausgabe.="<div class='wp-block-columns'>";
            $Ausgabe.="<div id='bookytkalender'></div>";
            $Ausgabe.="</div>";

            //Schließen der 2 Zeile
            $Ausgabe.="</div>";

            //Schließen Gesamtkonstrukt
            $Ausgabe.="</div>";

            bookyt_enqueue_libs();
        }
        else{
            foreach($json as $row)
            {
                if($counter==0)
                {
                    $Ausgabe.="<div class='wp-block-columns'>";
                }
                if($counter%3==0)
                {
                    $Ausgabe.="</div><div class='wp-block-columns'>";
                }

                $Ausgabe.="<div class='wp-block-column'>";

                if($a['call']=="category")
                {
                    $Ausgabe.="<h3>{$row["bezeichnung"]}</h3>";

                    if(!empty($row["files"][0]))
                    {
                        $Ausgabe.="<img src='".esc_url($row["files"][0])."' class='referenzlogo' alt='".esc_attr($row["bezeichnung"])."' style='max-width:120px'/>";
                    }

                    $Ausgabe.="<br>".bookyt_cleanHTML($row["fzg_gruppe_sub_bemerkung"]);

                    if(!empty($row["internet_link"]))
                    {
                        $Ausgabe.="<br><br><a href='".esc_attr($row["internet_link"])."'>Mehr Infos</a>";
                    }

                    $Ausgabe.='
                   <br>
                <div class="wp-block-buttons">
                <div class="wp-block-button"><a class="wp-block-button__link" href="'.esc_url($row["frontend_link"]).'">Jetzt buchen</a></div>
                </div>';
                }
                elseif($a["call"]=="news")
                {
                    $Ausgabe.="<b>".strftime("%d.%m.%Y",strtotime(esc_attr($row["datum"])));
                    if(!empty($row["betreff"]))
                    {
                        $Ausgabe.=" &bullet; ".bookyt_cleanHTML($row["betreff"])."";
                    }
                    $Ausgabe.="</b>";
                    $Ausgabe.="<br>".bookyt_cleanHTML($row["mitteilung"]);
                }
                elseif($a["call"]=="locations")
                {
                    $Ausgabe.="<h3>".bookyt_cleanHTML($row["caption"])."</h3>";

                    if(!empty($row["remarks_internet"]))
                    {
                        $Ausgabe.="<br>".esc_attr(strip_tags($row["remarks_internet"]));
                    }

                    if(!empty($row["street"]))
                    {
                        $Ausgabe.="<br><b>Anschrift</b>";
                        $Ausgabe.="<br>".bookyt_cleanHTML($row["street"]);
                        $Ausgabe.="<br>".bookyt_cleanHTML($row["zip"])." ".bookyt_cleanHTML($row["place"]);
                    }

                    if(!empty($row["opening_string"]))
                    {
                        $Ausgabe.="<br><b>Öffnungszeiten</b><br>".bookyt_cleanHTML($row["opening_string"]);
                    }

                }


                $Ausgabe.="<hr class='hide-on-desktop'></div>";

                $counter++;
            }
            $Ausgabe.="</div>";
        }
    }

    return $Ausgabe;

}


function bookyt_enqueue_libs() {
    // Load the datepicker script (pre-registered in WordPress).
    wp_enqueue_script('jquery');
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script( 'jquery-ui-datepicker' );

    // You need styling for the datepicker. For simplicity I've linked to the jQuery UI CSS on a CDN.
    wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.min.css' );
    wp_enqueue_style( 'jquery-ui' );
    wp_enqueue_script( 'ajax-script', plugins_url( '/js/functions.js', __FILE__ ));

    wp_register_style( 'bookytstyle',plugins_url( '/css/bookytstyle.css', __FILE__ ));
    wp_enqueue_style( 'bookytstyle' );
}

add_action( 'wp_ajax_bookyt_capacity_check', 'bookyt_capacity_check' );
add_action( 'wp_ajax_nopriv_bookyt_capacity_check', 'bookyt_capacity_check' );

add_action('wp_head', 'myplugin_ajaxurl');

function myplugin_ajaxurl() {

    echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url('admin-ajax.php') . '";
         </script>';
}


add_shortcode( 'bookyt', 'bookyt' );