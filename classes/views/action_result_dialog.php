<?php
/**
 * The dialog to show the result of an action.
 */
?>
<div id="action-result-dialog" class="hidden" style="min-width: 400px; max-width:800px">
    <p></p>
</div>
<script>
    (function ($) {
        $(window).load(function() {
            $('#action-result-dialog').dialog({
                title: 'Action Result',
                dialogClass: 'wp-dialog',
                autoOpen: false,
                draggable: false,
                width: 'auto',
                modal: true,
                resizable: false,
                closeOnEscape: true,
                position: {
                    my: "center",
                    at: "center",
                    of: window
                },
                open: function () {
                    $('.ui-widget-overlay').bind('click', function(){
                        $('#action-result-dialog').dialog('close');
                    })
                },
                create: function () {
                    $('.ui-dialog-titlebar-close').addClass('ui-button');
                },
            });
        });
    })(jQuery);
</script>
