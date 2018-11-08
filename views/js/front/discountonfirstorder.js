$(document).ready(function () {
    $("#dialogDiscountFirstOrder").dialog({
        autoOpen: true,
        draggable: false,
        resizable: false,
        width: 350,
        height: "auto",
        modal: true,
//        show: {
//            effect: "bounce",
//            duration: 1500
//        },
        hide: {
            effect: "fade",
            duration: 1000
        },
        position: {my: "center", at: "center", of: window}
        ,
        open: function( event, ui ) {
            $('button.ui-button').removeClass('ui-state-focus ui-state-hover ui-state-active');
        },
        create: function (event, ui) {
            $("body").css({overflow: 'hidden'})
        }
        ,
        beforeClose: function (event, ui) {
            $("body").css({overflow: 'inherit'})
        }
    }
    );
});