jQuery(document).ready(function($) {
	
	var cookies = (typeof (jQuery.cookie) != "undefined" ? true : false); // Check for JQuery Cookie
    function cookie_notice() {
        alert('Oh no! jQuery Cookie not loaded. Your Server Might be Blocking this. Some functionality may be impaired');
    }

	
	jQuery("body").on('click', '#content-tmce, .wp-switch-editor.switch-tmce', function () {
		if(cookies) {
		  $.cookie("email-templates-edit-view-choice", "editor", { path: '/', expires: 7 });
		} else {
		  cookie_notice();
		}
	});

	
	jQuery("body").on('click', '#content-html, .wp-switch-editor.switch-html', function () {
		if(cookies) {
		$.cookie("email-templates-edit-view-choice", "html", { path: '/', expires: 7 });
		} else {
		cookie_notice();
		}
	});

	if(cookies) {
	   var which_editor = $.cookie("email-templates-edit-view-choice");
	} else {
		var which_editor = 'editor';
		cookie_notice();
	}
	if(which_editor === null){
	   setTimeout(function() {
		//jQuery("#content-tmce").click();
		//jQuery(".wp-switch-editor.switch-tmce").click();
		}, 1000);

	}
	
	if(which_editor === 'editor'){
	  setTimeout(function() {
		//jQuery("#content-tmce").click();
		//jQuery(".wp-switch-editor.switch-tmce").click();
		jQuery('.inbound-wysiwyg-option textarea').each(function(){
			var chtml= "#" + jQuery(this).attr('id') + '-html';
			var ctmce= "#" + jQuery(this).attr('id') + '-tmce';
			var html_box = jQuery(chtml);
			var tinymce_box = jQuery(ctmce);
			switchEditors.switchto(tinymce_box[0]); // switch to tinymce
		});
		}, 1000);
	}

	
	/* WYSIWYG */
	window.tb_remove = function()
	{
		$("#TB_imageOff").unbind("click");
		$("#TB_closeWindowButton").unbind("click");
		$("#TB_window").fadeOut("fast",function(){$('#TB_window,#TB_overlay,#TB_HideSelect').trigger("unload").unbind().remove();});
		$("#TB_load").remove();
		if (typeof document.body.style.maxHeight == "undefined") {//if IE 6
			$("body","html").css({height: "auto", width: "auto"});
			$("html").css("overflow","");
		}
		document.onkeydown = "";
		document.onkeyup = "";
		jQuery.cookie('media_init', 0);
		return false;
	}
	 
	window.send_to_editor = function(h) {		
		if (jQuery.cookie('media_init')==1)
		{
			var imgurl = jQuery('img',h).attr('src');
			if (!imgurl)
			{
				var array = html.match("src=\"(.*?)\"");
				imgurl = array[1];
			}
			//alert(jQuery.cookie('media_name'));
			jQuery('#' + jQuery.cookie('media_name')).val(imgurl);
			jQuery.cookie('media_init', 0);
			tb_remove();
		}
		else
		{
			var ed, mce = typeof(tinymce) != 'undefined', qt = typeof(QTags) != 'undefined';

			if ( !wpActiveEditor ) {
				if ( mce && tinymce.activeEditor ) {
					ed = tinymce.activeEditor;
					wpActiveEditor = ed.id;
				} else if ( !qt ) {
					return false;
				}
			} else if ( mce ) {
				if ( tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen' || tinymce.activeEditor.id == 'wp_mce_fullscreen') )
					ed = tinymce.activeEditor;
				else
					ed = tinymce.get(wpActiveEditor);
			}

			if ( ed && !ed.isHidden() ) {
				// restore caret position on IE
				if ( tinymce.isIE && ed.windowManager.insertimagebookmark )
					ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);

				if ( h.indexOf('[caption') === 0 ) {
					if ( ed.wpSetImgCaption )
						h = ed.wpSetImgCaption(h);
				} else if ( h.indexOf('[gallery') === 0 ) {
					if ( ed.plugins.wpgallery )
						h = ed.plugins.wpgallery._do_gallery(h);
				} else if ( h.indexOf('[embed') === 0 ) {
					if ( ed.plugins.wordpress )
						h = ed.plugins.wordpress._setEmbed(h);
				}

				ed.execCommand('mceInsertContent', false, h);
			} else if ( qt ) {
				QTags.insertContent(h);
			} else {
				document.getElementById(wpActiveEditor).value += h;
			}

			jQuery.cookie('media_init', 0);
			
			try{tb_remove();}catch(e){};
		}
	}
});