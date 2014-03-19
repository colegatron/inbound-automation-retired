jQuery(document).ready(function($) {

    // Colorpicker fix
    jQuery('.jpicker').one('mouseenter', function () {
        jQuery(this).jPicker({
            window: // used to define the position of the popup window only useful in binded mode
            {
                title: null, // any title for the jPicker window itself - displays "Drag Markers To Pick A Color" if left null
                position: {
                    x: 'screenCenter', // acceptable values "left", "center", "right", "screenCenter", or relative px value
                    y: 'center', // acceptable values "top", "bottom", "center", or relative px value
                },
                expandable: false, // default to large static picker - set to true to make an expandable picker (small icon with popup) - set
                // automatically when binded to input element
                liveUpdate: true, // set false if you want the user to click "OK" before the binded input box updates values (always "true"
                // for expandable picker)
                alphaSupport: false, // set to true to enable alpha picking
                alphaPrecision: 0, // set decimal precision for alpha percentage display - hex codes do not map directly to percentage
                // integers - range 0-2
                updateInputColor: true // set to false to prevent binded input colors from changing
            }
        },
        function(color, context)
        {
          var all = color.val('all');
         // alert('Color chosen - hex: ' + (all && '#' + all.hex || 'none') + ' - alpha: ' + (all && all.a + '%' || 'none'));
           //jQuery(this).attr('rel', all.hex);
           jQuery(this).parent().find(".inbound-email-templates-success-message").remove();
           jQuery(this).parent().find(".new-save-inbound-email-templates").show();
           jQuery(this).parent().find(".new-save-inbound-email-templates-frontend").show();

           //jQuery(this).attr('value', all.hex);
        });
    });

    if (jQuery(".inbound-email-templates-template-selector-container").css("display") == "none"){
        jQuery(".currently_selected").hide(); }
    else {
        jQuery(".currently_selected").show();
    }

   

    /* Move Slug Box
    var slugs = jQuery("#edit-slug-box");
    jQuery('#main-title-area').after(slugs.show());
    */
    // Background Options
    jQuery('.current_lander .background-style').live('change', function () {
        var input = jQuery(".current_lander .background-style option:selected").val();
        if (input == 'color') {
            jQuery('.current_lander tr.background-color').show();
            jQuery('.current_lander tr.background-image').hide();
            jQuery('.background_tip').hide();
        }
        else if (input == 'default') {
            jQuery('.current_lander tr.background-color').hide();
            jQuery('.current_lander tr.background-image').hide();
            jQuery('.background_tip').hide();
        }
        else if (input == 'custom') {
            var obj = jQuery(".current_lander tr.background-style td .inbound_email_templates_tooltip");
            obj.removeClass("inbound_email_templates_tooltip").addClass("background_tip").html("Use the custom css block at the bottom of this page to set up custom CSS rules");
            jQuery('.background_tip').show();
        }
        else {
            jQuery('.current_lander tr.background-color').hide();
            jQuery('.current_lander tr.background-image').show();
            jQuery('.background_tip').hide();
        }

    });

    // Check BG options on page load
    jQuery(document).ready(function () {
        var input2 = jQuery(".current_lander .background-style option:selected").val();
        if (input2 == 'color') {
            jQuery('.current_lander tr.background-color').show();
            jQuery('.current_lander tr.background-image').hide();
        } else if (input2 == 'custom') {
            var obj = jQuery(".current_lander tr.background-style td .inbound_email_templates_tooltip");
            obj.removeClass("inbound_email_templates_tooltip").addClass("background_tip").html("Use the custom css block at the bottom of this page to set up custom CSS rules");
            jQuery('.background_tip').show();
        } else if (input2 == 'default') {
            jQuery('.current_lander tr.background-color').hide();
            jQuery('.current_lander tr.background-image').hide();
        } else {
            jQuery('.current_lander tr.background-color').hide();
            jQuery('.current_lander tr.background-image').show();
        }
    });

    //Stylize lead's wp-list-table
    var cnt = $("#leads-table-container").contents();
    $("#inbound_email_templates_conversion_log_metabox").replaceWith(cnt);

    //remove inputs from wp-list-table
    jQuery('#leads-table-container-inside input').each(function(){
        jQuery(this).remove();
    });

     var post_status = jQuery("#original_post_status").val();

    if (post_status === "draft") {
        // jQuery( ".nav-tab-wrapper.a_b_tabs .inbound-email-templates-ab-tab, #tabs-add-variation").hide();
        jQuery(".new-save-inbound-email-templates-frontend").on("click", function(event) {
            event.preventDefault();
            alert("Must publish this page before you can use the visual editor!");
        });
        var subbox = jQuery("#submitdiv");
        jQuery("#inbound_email_templates_ab_display_stats_metabox").before(subbox);
        jQuery("body").on('click', '#content-html', function () {
           // alert('Ut oh! Hit publish to use text editor OR refresh the page.');
        });
    } else {
        jQuery("#publish").val("Update All");

    }

function getURLParameter(name) {
    return decodeURI(
        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
    );
}

// Clone ID fixes
var clone_id = getURLParameter('clone');
if (clone_id != null) {
    jQuery("#inbound_email_templates_width-" + clone_id).attr('name', 'inbound_email_templates_width');
    jQuery("#inbound_email_templates_height-" + clone_id).attr('name', 'inbound_email_templates_height');
}

    // Ajax Saving for metadata
    jQuery('#inbound_email_templates_metabox_select_template input, #inbound_email_templates_metabox_select_template select, #inbound_email_templates_metabox_select_template textarea, #inbound-email-templates-notes-area input, .inbound-wysiwyg-option iframe').on("change keyup", function (e) {
        // iframe content change needs its own change function $("#iFrame").contents().find("#someDiv")
        // media uploader needs its own change function
        var new_meta_key = getURLParameter('new_meta_key');
        var clone_id = getURLParameter('clone');


        var this_id = jQuery(this).attr("id");
        if (new_meta_key != null){
            var this_id = this_id.replace(clone_id, new_meta_key)
        } else {

        }
        var parent_el = jQuery(this).parent();
        var field_type = jQuery(this).parent().attr('data-field-type');
        if (typeof (field_type) === "undefined") {
           var field_type = 'editor';
        }
        jQuery(parent_el).find(".inbound-email-templates-success-message").remove();
        jQuery(parent_el).find(".new-save-inbound-email-templates").remove();
        var ajax_save_button = jQuery('<span class="button-primary new-save-inbound-email-templates" data-field-type="'+field_type+'" id="' + this_id +'" style="margin-left:10px">Update</span>');
        //console.log(parent_el);
        jQuery(ajax_save_button).appendTo(parent_el);
    });




        jQuery('#main-title-area input').on("change keyup", function (e) {
        // iframe content change needs its own change function $("#iFrame").contents().find("#someDiv")
        // media uploader needs its own change function
        var this_id = jQuery(this).attr("id");
        var current_view = jQuery("#inbound-email-templates-current-view").text();
        if (current_view !== "0") {
            this_id = this_id + '-' + current_view;
        }
        var parent_el = jQuery(this).parent();
        jQuery(parent_el).find(".inbound-email-templates-success-message").remove();
        jQuery(parent_el).find(".new-save-inbound-email-templates").remove();
        var ajax_save_button = jQuery('<span class="button-primary new-save-inbound-email-templates" id="' + this_id + '" style="margin-left:10px">Update</span>');
        //console.log(parent_el);
        jQuery(ajax_save_button).appendTo(parent_el);
    });




    var nonce_val = inbound_email_templates_post_edit_ui.wp_call_to_action_meta_nonce; // NEED CORRECT NONCE
    jQuery("body").on('click', '.new-save-inbound-email-templates', function () {

        jQuery('body').css('cursor', 'wait');
        jQuery(this).parent().find(".inbound-email-templates-success-message").hide();
        var input_type = jQuery(this).attr('data-field-type');

        console.log(input_type);
        var this_meta_id = jQuery(this).attr("id");
        console.log(this_meta_id);
        // prep data
        if (input_type == "text" || input_type == "number" ||  input_type == "colorpicker") {
            var meta_to_save = jQuery(this).parent().find("input").val();
        } else if (input_type == "textarea") {
            var meta_to_save = jQuery(this).parent().find("textarea").val();
        } else if (input_type == "select") {
            var meta_to_save = jQuery(this).parent().find("select").val();
        } else if (input_type == "dropdown") {
            var meta_to_save = jQuery(this).parent().find("select").val();
        } else if (input_type == "radio") {
            var meta_to_save = jQuery(this).parent().find("input:checked").val();
        } else if (input_type == "checkbox") {
            var meta_to_save = jQuery(this).parent().find('input[type="checkbox"]:checked').val();
        } else if (input_type == "editor") {
            var meta_to_save = jQuery(this).parent().find('textarea').val();
        } else if (input_type == "iframe") {
            var meta_to_save = jQuery(".iframe-options-"+this_meta_id+" iframe").contents().find('body').html();
        } else {
            var meta_to_save = "";
        }
        console.log(meta_to_save);
        // if data exists save it

        var post_id = jQuery("#post_ID").val();

        function do_reload_preview() {
        var cache_bust =  generate_random_cache_bust(35);
        var reload_url = parent.window.location.href;
        reload_url = reload_url.replace('cta-template-customize=on','');
        //alert(reload_url);
        var current_variation_id = jQuery("#inbound-email-templates-current-view").text();

        // var reload = jQuery(parent.document).find("#lp-live-preview").attr("src");
        var new_reload = reload_url + "&live-preview-area=" + cache_bust + "&inbound-email-templates-variation-id=" + current_variation_id;
        //alert(new_reload);
        jQuery(parent.document).find("#inbound-email-templates-live-preview").attr("src", new_reload);

      var iframe_w = jQuery('.cta-width').val();
      var iframe_h = jQuery('.cta-height').val();
      if (typeof (iframe_w) != "undefined" && iframe_w != null && iframe_w != "") {
        var iframe_h = jQuery('.cta-height').val() || "100%";
         jQuery(parent.document).find("#inbound-email-templates-live-preview").css('width', iframe_w).css('height', iframe_h);
      }


        // console.log(new_reload);
    }
        var frontend_status = jQuery("#frontend-on").val();
        setTimeout(function() {
         do_reload_preview();
                }, 1000);

        jQuery.ajax({
            type: 'POST',
            url: inbound_email_templates_post_edit_ui.ajaxurl,
            context: this,
            data: {
                action: 'wp_wp_call_to_action_meta_save',
                meta_id: this_meta_id,
                new_meta_val: meta_to_save,
                page_id: post_id,
                nonce: nonce_val
            },

            success: function (data) {
                var self = this;
                jQuery('body').css('cursor', 'default');
                //alert(data);
                // jQuery('.inbound-email-templates-form').unbind('submit').submit();
                //var worked = '<span class="success-message-map">Success! ' + this_meta_id + ' set to ' + meta_to_save + '</span>';
                var worked = '<span class="inbound-email-templates-success-message">Updated!</span>';
                var s_message = jQuery(self).parent();
                jQuery(worked).appendTo(s_message);
                jQuery(self).hide();
                jQuery("#switch-inbound-email-templates").text("0");
                // RUN RELOAD
                if (typeof (frontend_status) != "undefined" && frontend_status !== null) {
                console.log('reload frame');
                do_reload_preview();
                } else {
                console.log('No reload frame');
                }
                //alert("Changes Saved!");
            },

            error: function (MLHttpRequest, textStatus, errorThrown) {
                alert("Ajax not enabled");
            }
        });

        return false;

    });
});
