jQuery(document).ready(function($) {

	var selected_template = data.selected_template;
	var templates_json = data.templates.replace(/&quot;/g, '"');
	var templates_array = jQuery.parseJSON(templates_json);

	//reveal extension metaboxes and selected template's metaboxes
	jQuery.each(templates_array, function(name, array) {
		name = name.replace(' ','-');
		name = name.toLowerCase();
		if (name==selected_template)
		{
			//alert(name);
			jQuery("#inbound_email_templates_"+name+"_custom_meta_box").css('display','block').addClass('current_lander');
		}
		else
		{
			jQuery("#inbound_email_templates_"+name+"_custom_meta_box").css('display','none');
		}
	});
	
	// Add current title of template to selector
    var selected_template = jQuery('#inbound_email_templates_select_template').val();
    var selected_template_id = "#" + selected_template;
    var clean_template_name = selected_template.replace(/-/g, ' ');
   
	function capitaliseFirstLetter(string) {
		return string.charAt(0).toUpperCase() + string.slice(1);
    }
	
    var currentlabel = jQuery(".currently_selected");
    jQuery(selected_template_id).parent().addClass("default_template_highlight").prepend(currentlabel);
    jQuery("#inbound_email_templates_metabox_select_template h3").first().prepend('<strong>' + capitaliseFirstLetter(clean_template_name) + '</strong> - ');

    jQuery('#inbound-email-templates-change-template-button').live('click', function () {
        jQuery(".wrap").fadeOut(500,function(){

            jQuery(".inbound-email-templates-selector-container").fadeIn(500, function(){
                jQuery(".currently_selected").show();
                jQuery('#inbound-email-templates-cancel-selection').show();
            });
            jQuery("#template-filter li a").first().click();
        });
    });

	//add listener to detect change in selected template and hide and reveal appropriate metaboxes
	jQuery('.inbound_email_templates_select_template').live('change', function(){
		var input = jQuery(this).attr('id');
		alert(input);
		jQuery.each(templates_array, function(name, array) {
			name = name.replace(' ','-');
			if (input==name)
			{
				jQuery("#inbound_email_templates_"+name+"_custom_meta_box").show().addClass('current_lander');
			}
			else
			{
				jQuery("#inbound_email_templates_"+name+"_custom_meta_box").hide().removeClass('current_lander');
			}
		});
	});

    jQuery('#inbound-email-templates-cancel-selection').click(function(){
        jQuery(".inbound-email-templates-template-selector-container").fadeOut(500,function(){
            jQuery(".wrap").fadeIn(500, function(){
            });
        });

    });
	
	// filter items when filter link is clicked
    jQuery('#template-filter a').click(function(){
        var selector = jQuery(this).attr('data-filter');
        $(".template-item-boxes").fadeOut(500);
           setTimeout(function() {
            $(selector).fadeIn(500);
           }, 500);
        return false;
    });

	// filter Styling
    jQuery('#template-filter a').first().addClass('button-primary');
    jQuery('#template-filter a').click(function(){
        jQuery("#template-filter a.button-primary").removeClass("button-primary");
        jQuery(this).addClass('button-primary');
    });

	
    // Fix inactivate theme display
    jQuery("#template-box a").live('click', function () {
		setTimeout(function() {
			jQuery('#TB_window iframe').contents().find("#customize-controls").hide();
			jQuery('#TB_window iframe').contents().find(".wp-full-overlay.expanded").css("margin-left", "0px");
		}, 600);
    });
	
	 // Load meta box in correct position on page load
    var current_template = jQuery("input#inbound_email_templates_select_template ").val();
    var current_template_meta = "#inbound_email_templates_" + current_template + "_custom_meta_box";
    jQuery(current_template_meta).removeClass("postbox").appendTo("#template-display-options").addClass("Old-Template");
    var current_template_h3 = "#inbound_email_templates_" + current_template + "_custom_meta_box h3";

    jQuery(current_template_meta +' .handlediv').hide();
    jQuery(current_template_meta +' .hndle').css('cursor','default');
	
    jQuery('.inbound_email_templates_select_template').click(function(){

        var template = jQuery(this).attr('id');
        var label = jQuery(this).attr('label');
        var selected_template_id = "#" + template;
        var currentlabel = jQuery(".currently_selected").show();
		var current_template = jQuery("input#inbound_email_templates_select_template ").val();
        var current_template_meta = "#inbound_email_templates_" + current_template + "_custom_meta_box";
        var current_template_h3 = "#inbound_email_templates_" + current_template + "_custom_meta_box h3";
        var current_template_div = "#inbound_email_templates_" + current_template + "_custom_meta_box .handlediv";
		var open_variation = jQuery("#open_variation").val();

		if (open_variation>0) {
			var variation_tag = "-"+open_variation;
		} else {
			var variation_tag = "";
		}
		jQuery("#template-box.default_template_highlight").removeClass("default_template_highlight");

        jQuery(selected_template_id).parent().addClass("default_template_highlight").prepend(currentlabel);
        jQuery(".inbound-email-templates-template-selector-container").fadeOut(500,function(){

			jQuery('#template-display-options').fadeOut(500, function(){
			});

			var ajax_data = {
				action: 'inbound_email_templates_get_template_meta',
				selected_template: template,
				post_id: inbound_email_templates_post_edit_ui.post_id,
			};

			jQuery.ajax({
					type: "POST",
					url: inbound_email_templates_post_edit_ui.ajaxurl,
					data: ajax_data,
					dataType: 'html',
					timeout: 7000,
					success: function (response) {

					jQuery('#inbound_email_templates_metabox_select_template .input').remove();
					jQuery('#inbound_email_templates_metabox_select_template .form-table').remove();
						jQuery('#template-display-options').fadeIn(500);
						//alert(response);
						var html = '<input id="inbound_email_templates_select_template" type="hidden" value="'+template+'" name="inbound-email-templates-selected-template'+variation_tag+'">'
								 + '<h3 class="hndle" style="cursor: default;">'
								 + '<span>'
								 + '<small>'+ template +' Options:</small>'
								 +	'</span>'
								 +	'</h3>'
								 + response;

						jQuery('#inbound_email_templates_metabox_select_template #template-display-options').html(html);

					},
					error: function(request, status, err) {
						alert(status);
					}
				});
				jQuery(".wrap").fadeIn(500, function(){
            });
        });

        jQuery(current_template_meta).appendTo("#template-display-options");
        jQuery('#inbound_email_templates_metabox_select_template h3').first().html('Current Active Template: '+label);
        jQuery('#inbound_email_templates_select_template').val(template);
        jQuery(".Old-Template").hide();

        jQuery(current_template_div).css("display","none");
        jQuery(current_template_h3).css("background","#f8f8f8");
        jQuery(current_template_meta).show().appendTo("#template-display-options").removeClass("postbox").addClass("Old-Template");
        //alert(template);
        //alert(label);
    });

});