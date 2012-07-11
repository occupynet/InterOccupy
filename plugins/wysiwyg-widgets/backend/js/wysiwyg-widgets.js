jQuery(document).ready(function(){

    jQuery('div.widget:has(textarea.wp-editor-area) input.widget-control-save').live('click', function(){
        
        var textarea = jQuery('textarea.wp-editor-area', jQuery(this).parents('form'));
        var textarea_id = textarea.attr('id');

        if (typeof(tinyMCE.get(textarea_id)) == "object") {
            
            var editor = tinyMCE.get(textarea_id);
            var content = editor.getContent();

            textarea.val(content);
            editor.remove();
        }

        jQuery(this).unbind('ajaxSuccess').ajaxSuccess(function(e, x, s) {
            jQuery('a.switch-tmce', jQuery(this).parents('form')).click();
        });
        
        return true;
    });

});