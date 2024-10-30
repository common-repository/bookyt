

var dateArray = [];
var myTimeout;

jQuery(document).ready(function( $ ) {

    //Kalender initialisieren
    if(document.getElementById("bookytkalender")){

        //Kalender einsetzen
        var dateToday = new Date();

        jQuery.datepicker.regional['de'] = {
            closeText: 'schließen',
            prevText: '&#x3c;zurück',
            nextText: 'Vor&#x3e;',
            currentText: 'heute',
            monthNames: ['Januar','Februar','Marz','April','Mai','Juni',
                'Juli','August','September','Oktober','November','Dezember'],
            monthNamesShort: ['Jan','Feb','Mar','Apr','Mai','Jun',
                'Jul','Aug','Sep','Okt','Nov','Dez'],
            dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
            dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            weekHeader: 'Wo',
            dateFormat: 'dd.mm.yy',
            firstDay: 1,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ''};
        jQuery.datepicker.setDefaults(jQuery.datepicker.regional['de']);


        jQuery('#bookytkalender').datepicker({
            numberOfMonths: 1,
            regional:'de',
            changeMonth: true,
            changeYear: true,
            dateFormat : 'dd.mm.yy',
            minDate: dateToday,
            maxDate: 365*2,
            onSelect:function(day){
                var url = jQuery(".wp-bookyt-calendar").first().attr("frontend");
                var para = bookytGetCalendarValues();

                var goTo = url+"/?station_id="+para.station_id+"&fahrzeugsubgruppe_id="+para.gruppe+"&start="+day+"&stop="+para.dauer;
                window.location.href=goTo;
            },

            beforeShowDay:function(date){
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                return [ dateArray.indexOf(string) !== -1 ]
            },
            beforeshow: function(inp, inst) {
                //call the function to append div to datepicker

            },
            onChangeMonthYear: function(year, month, datepicker) {
                jQuery('#bookytkalender').attr("year",year);
                jQuery('#bookytkalender').attr("month",month);
                bookytAjaxRequestShowAvailable();
            }
        });

        //Kategorien setzen
        bookytSetCategorie(jQuery(".wp-bookyt-calendar .station").first());
    }
});


function bookytGetCalendarValues(){
    var obj = {};
    obj.station_id = jQuery(".wp-bookyt-calendar .station").first().val();
    obj.gruppe = jQuery(".wp-bookyt-calendar .gruppe").first().val();
    obj.dauer = jQuery(".wp-bookyt-calendar .mietdauer").first().val();
    obj.day = jQuery('#bookytkalender').datepicker( "getDate" );
    obj.year = jQuery('#bookytkalender').attr("year");
    obj.month = jQuery('#bookytkalender').attr("month");
    return obj;
}


function bookytSetCategorie(obj){
    dateArray = [];
    jQuery('#bookytkalender').datepicker( "refresh" );

    if(jQuery(obj).find("option:selected").length==0){
        jQuery(obj).find("option").first().prop("selected",true);
    }
    var json = jQuery(obj).find("option:selected").attr("categories");
    var categories = JSON.parse(decodeURIComponent(json));
    var katObj = jQuery(obj).closest(".wp-bookyt-calendar").find(".gruppe");
    var val = jQuery(katObj).val();

    jQuery(katObj).find("option").remove();
    jQuery.each(categories,function(){
        jQuery(katObj).append("<option value='"+this["gruppe_id"]+"'>"+this["caption"]+"</option>");
    })

    if(jQuery(katObj).find("option[value='"+val+"']").length>0){
        jQuery(katObj).val(val);
    }
    else{
        jQuery(katObj).find("option").first().prop("selected");
    }

    bookytAjaxRequestShowAvailable();
}

function bookytAjaxRequestShowAvailable(){

    dateArray = [];
    jQuery('#bookytkalender').datepicker( "refresh" );

    var data = bookytGetCalendarValues();
    data["frontend"] = jQuery(".wp-bookyt-calendar").attr("frontend");
    data["action"] = 'bookyt_capacity_check';

    try {
        clearTimeout(myTimeout);
    }
    catch (e) {}

    myTimeout = setTimeout(function(){
        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(ajaxurl, data, function(response) {

            jQuery.each(response,function(day,avail){
                if(avail){
                    dateArray.push(day);
                }
            });

            jQuery('#bookytkalender').datepicker( "refresh" );
        });

        }, 400);


}



