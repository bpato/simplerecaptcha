
{foreach $simplerecaptcha_js as $module_js}
    <!-- {$module_js}: recaptcha invisible -->
    <script type="text/javascript">
        (function($) {
            $('#recaptcha.{$module_js}').closest('form').submit(function(event){
                if (! grecaptcha.getResponse()) {
                    window._{$module_js}Submitter = event.originalEvent.submitter;
                    event.preventDefault();
                    grecaptcha.reset();
                    grecaptcha.execute();
                } else {
                    delete window._{$module_js}Submitter;
                }
            });

            window._{$module_js}OnSubmit = function(token) {
                event.preventDefault();
                if ( grecaptcha.getResponse() ) {
                    $(window._{$module_js}Submitter).trigger('click');
                }
            }
        })(jQuery);
    </script><!-- {$module_js}: recaptcha invisible -->
{/foreach}