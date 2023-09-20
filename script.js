if(typeof jQuery === 'undefined'){
    var script = document.createElement('script');
    script.src = 'https://code.jquery.com/jquery-3.6.1.min.js';
    document.getElementsByTagName('head')[0].appendChild(script);
}

(function($){
    $(document).ready(function(){
        // enable only for decimal number inputs
        $('#create-order [type="text"]').keydown(function(e){            
            if(e.shiftKey == true) { e.preventDefault(); }
            if((e.keyCode >= 48 && e.keyCode <= 57) || e.keyCode == 190 || e.keyCode == 8){
                // let it be pressed
                $('.co-decimal-reminder').fadeOut(350);
            }else{
                e.preventDefault();
                $('.co-decimal-reminder').fadeIn(350);
            }
            
            if($(this).val().indexOf('.') !== -1 && e.keyCode == 190){
                e.preventDefault();
                $('.co-decimal-reminder').fadeIn(350);
            }
        });

        $('#create-order [type="submit"]').click(function(e){
            if($('#create-order [type="text"]').val() == ''){
                e.preventDefault();
                $('#create-order [type="text"]').focus();
                $('.co-no-weight').fadeIn(350);
            }
        });
    });
})(jQuery);