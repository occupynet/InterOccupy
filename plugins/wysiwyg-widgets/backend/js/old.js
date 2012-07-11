/**
 * This code is heavily inspired by Marco and Francesco from Black Studio and their Black Studio TinyMCE Widget plugin
 * Have a look at it here: http://wordpress.org/extend/plugins/black-studio-tinymce-widget/
 * http://www.blackstudio.it/
 */

var edCanvas;

jQuery(document).ready(function(){
    
    /**
     * Get active texteditor for WP Media Uploading
     */
    jQuery('.wwe_media_buttons a').live('click', function(){
        edCanvas = jQuery('textarea.wwe_editor', jQuery(this).parents('form')).get();
    });
    
    /**
     * Activate WYSIWYG Editor upon opening widget.
     */
    jQuery('div.widget:has(textarea.wwe_editor) a.widget-action').live('click', function(){
        var txt_area = jQuery('textarea.wwe_editor', jQuery(this).parents('div.widget'));
        WYSIWYG_Widgets.instantiate_editor(txt_area.attr('id'));
        return false;
    });
    
    /**
     * Get HTML value of WYSIWYG Editor for saving widget
     */
    jQuery('div.widget:has(textarea.wwe_editor) input.widget-control-save').live('click', function(){
        var txt_area = jQuery('textarea.wwe_editor', jQuery(this).parents('form'));
        
        if (typeof(tinyMCE.get(txt_area.attr('id'))) == "object") {
            WYSIWYG_Widgets.deactivate_editor(txt_area.attr('id'));
        }
                 
        jQuery(this).unbind('ajaxSuccess').ajaxSuccess(function(e, x, s) {
            console.log("Ajax succes");
            var txt_area = jQuery('textarea.wwe_editor', jQuery(this).parents('form'));
            WYSIWYG_Widgets.instantiate_editor(txt_area.attr('id'));
        });
        
        return true;
    });
    
    /**
     * Switch to visual mode
     */
    jQuery('.wwe_toggle_buttons a[id$=visual]').live('click', function(){
        jQuery(this).addClass('active');
        jQuery('.wwe_toggle_buttons a[id$=html]', jQuery(this).parents('div.widget')).removeClass('active');
        jQuery('input.wwe_type', jQuery(this).parents('div.widget')).val('visual');
        WYSIWYG_Widgets.activate_editor(jQuery('textarea.wwe_editor', jQuery(this).parents('form')).attr('id'));
        return false;
    });
    
    /**
     * Switch to HTML mode
     */
    jQuery('.wwe_toggle_buttons a[id$=html]').live('click', function(){
        jQuery(this).addClass('active');
        jQuery('.wwe_toggle_buttons a[id$=visual]', jQuery(this).parents('form')).removeClass('active');
        jQuery('input.wwe_type', jQuery(this).parents('div.widget')).val('html');
        WYSIWYG_Widgets.deactivate_editor(jQuery('textarea.wwe_editor', jQuery(this).parents('form')).attr('id'));
        return false;
    });
});

window.WYSIWYG_Widgets = {
    
    activate_editor : function (id) {
        jQuery('#'+id).addClass("mceEditor");
        if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
            WYSIWYG_Widgets.deactivate_editor(id);
            tinyMCE.init({ mode : "exact", theme : "advanced", theme_advanced_toolbar_location : "top" });
            tinyMCE.execCommand("mceAddControl", false, id);
        }
    },
    
    deactivate_editor : function(id) {
        if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
            if (typeof(tinyMCE.get(id)) == "object") {
                var content = tinyMCE.get(id).getContent();
                tinyMCE.execCommand("mceRemoveControl", false, id);
                jQuery('textarea#'+id).val(content);
            }
        }
    },
    
    instantiate_editor : function(id) {
        jQuery('div.widget:has(#' + id + ') input.wwe_type[value=visual]').each(function() {
            
            if (jQuery('div.widget:has(#' + id + ') :animated').size() == 0 && typeof(tinyMCE.get(id)) != "object" && jQuery('#' + id).is(':visible')) {
                jQuery('.wwe_toggle_buttons a[id$=visual]', jQuery(this).parents('form')).click();
            }
            
            else if (typeof(tinyMCE.get(id)) != "object") {
                setTimeout(function(){ WYSIWYG_Widgets.instantiate_editor(id);}, 250);
                return;
            }
        });
    }  
    
    
}