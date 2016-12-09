/*
 * Filter box on fields edit page.
 */
jQuery(document).ready(function(){
    mncfFieldsFormFiltersSummary();
	//jQuery('#mnfooter').css({'position':'relative'});
});

function mncfFieldsFormFiltersSummary()
{
    if ( 'undefined' == typeof mncf_settings ) {
        return;
    }
    if (jQuery('#mncf-fields-form-filters-association-form').find("input:checked").val() == 'all') {
        var string = mncf_settings.mncf_filters_association_and;
    } else {
        var string = mncf_settings.mncf_filters_association_or;
    }
    var pt = new Array();
    jQuery('#mncf-form-fields-post_types').find("input:checked").each(function(){
        pt.push(jQuery(this).next().html());
    });
    var tx = new Array();
    jQuery('#mncf-form-fields-taxonomies').find("input:checked").each(function(){
        tx.push(jQuery(this).next().html());
    });
    var vt = new Array();
    jQuery('#mncf-form-fields-templates').find("input:checked").each(function(){
        vt.push(jQuery(this).next().html());
    });
    if (pt.length < 1) {
        pt.push(mncf_settings.mncf_filters_association_all_pages);
    }
    if (tx.length < 1) {
        tx.push(mncf_settings.mncf_filters_association_all_taxonomies);
    }
    if (vt.length < 1) {
        vt.push(mncf_settings.mncf_filters_association_all_templates);
    }
    string = string.replace('%pt%', pt.join(', '));
    string = string.replace('%tx%', tx.join(', '));
    string = string.replace('%vt%', vt.join(', '));
    jQuery('#mncf-fields-form-filters-association-summary').html(string);
}

// Title func
function _mncfFilterTitle(e, title, title_not_empty, title_empty) {
    if (e == 'empty') {
        return title + ' ' + title_empty;
    } else {
        return title + ' ' + title_not_empty;
    }
}

/**
 * Autocomplete slugs
 */
jQuery('input.mncf-forms-field-slug').live('blur focus click', function(){
    var slug = jQuery(this).val();
    if ( '' == slug ){
//        jQuery(this).val(mncf_slugize(jQuery(this).parent().find('input.mncf-forms-field-name').val()));
    }
});

var CSSLayoutEditor = '';
var HTMMLLayoutEditor = '';
// Edit Button
//
//
//
function mncfFilterEditClick(object, edit, title, title_not_empty, title_empty) {
    var parent = object.parents('.mncf-filter-wrap');
    var toggle = parent.next();
    /*
     *
     * Built-in filters
     *
     *
     * Custom types
     */
    if (edit == 'custom_post_types') {

        /*
         *
         * Take a snapshot
         */
        window.mncfPostTypesText = new Array();
        window.mncfFormGroupsSupportPostTypesState = new Array();
        toggle.slideToggle().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfPostTypesText.push(jQuery(this).next().html());
                window.mncfFormGroupsSupportPostTypesState.push(jQuery(this).attr('id'));
            }
        });
    /*
         *
         *
         *
         *
         *
         *
         * Do taxonomies
         */
    } else if (edit == 'custom_taxonomies') {
        /*
         *
         * Take a snapshot
         */
        window.mncfTaxText = new Array();
        window.mncfFormGroupsSupportTaxState = new Array();
        toggle.slideToggle().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfTaxText.push(jQuery(this).next().html());
                window.mncfFormGroupsSupportTaxState.push(jQuery(this).attr('id'));
            }
        });
    } else if (edit == 'templates') {
        window.mncfTemplatesText = new Array();
        window.mncfFormGroupsTemplatesState = new Array();
        toggle.slideToggle().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfTemplatesText.push(jQuery(this).next().html());
                window.mncfFormGroupsTemplatesState.push(jQuery(this).attr('id'));
            }
        });
        jQuery(this).css('visibility', 'hidden');
    }
	else if (edit == 'admin_styles') {
        toggle.slideToggle();
		//.CodeMirror-scroll{
	//width:713px;
//}
		jQuery("#mncf-admin-styles-box").css({width:'700px','border-color':'#808080','box-shadow':'5px 5px 10px #888888','z-index':'10000'});

		if (CSSLayoutEditor == ''){
			jQuery("#mncf-update-preview-div").resizable({});
			document.getElementById("mncf-form-groups-admin-html-preview").innerHTML = typesBase64.decode( mncfEditMode );
			CSSLayoutEditor = CodeMirror.fromTextArea(document.getElementById("mncf-form-groups-css-fields-editor"), {mode: "css", tabMode: "indent",
				 lineWrapping: true, lineNumbers: true});
			HTMMLLayoutEditor = CodeMirror.fromTextArea(document.getElementById("mncf-form-groups-admin-html-preview"), {mode: "text/html", tabMode: "indent",
				 readOnly:true, lineWrapping: true, lineNumbers: true});
			mncfPreviewHtml();
			jQuery(".CodeMirror-scroll").css({width:'675px'});
			jQuery(".CodeMirror").resizable({
			  stop: function() { CSSLayoutEditor.refresh(); HTMMLLayoutEditor.refresh(); },
			  resize: function() {
				jQuery(this).find(".CodeMirror-scroll").height(jQuery(this).height());
				jQuery(this).find(".CodeMirror-scroll").width(jQuery(this).width());
				CSSLayoutEditor.refresh();HTMMLLayoutEditor.refresh();
			  }
			});

		}


    }

    // Hide until OK or Cancel
    object.css('visibility', 'hidden');

    /**
     * remove functionality from links in preview mode
     */
    jQuery('#mncf-update-preview-div a').on('click', function() {
        alert(mncfFormAlertOnlyPreview);
        return false;
    });
}


function changePreviewHtml(mode){
	if (mode == 'readonly'){
		HTMMLLayoutEditor.setValue( typesBase64.decode( mncfReadOnly) );
	}
	else{
		HTMMLLayoutEditor.setValue( typesBase64.decode( mncfEditMode ) );
	}
	HTMMLLayoutEditor.refresh();
	mncfPreviewHtml();
}
function mncfPreviewHtml(){
	jQuery("#mncf-update-preview-div").resizable( "destroy" );
	jQuery("<style type='text/css'> "+ CSSLayoutEditor.getValue() +" </style>").appendTo("head");
	document.getElementById("mncf-update-preview-div").innerHTML = HTMMLLayoutEditor.getValue();
	jQuery("#mncf-update-preview-div").resizable({});
}


// OK Button
//
//
//
//
function mncfFilterOkClick(object, edit, title, title_not_empty, title_empty) {
    var toggle = object.parent();
    var parent = toggle.prev('.mncf-filter-wrap');
    /*
     *
     * Built-in filters
     *
     *
     *
     *
     * Do post types
     */
    if (edit == 'custom_post_types') {

        /*
         *
         * Take a snapshot of current state
         */
        window.mncfPostTypesText = new Array();
        window.mncfFormGroupsSupportPostTypesState = new Array();
        toggle.slideUp().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfPostTypesText.push(jQuery(this).next().html());
                window.mncfFormGroupsSupportPostTypesState.push(jQuery(this).attr('id'));
            }
        });

        /*
         *
         *
         * Set TEXT
         */
        if (window.mncfPostTypesText.length < 1) {
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('empty', title, title_not_empty, title_empty)
                );
        } else {
            var title_not_empty = mncfPostTypesText.join(', ');
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('has', title, title_not_empty, title_empty)
                );
        };

    /*
         *
         *
         *
         *
         *
         *
         *
         *
         *
         * Now do taxonomies
         */

    } else if (edit == 'custom_taxonomies') {
        /*
         *
         * Take a snapshot of current state
         */
        window.mncfTaxText = new Array();
        window.mncfFormGroupsSupportTaxState = new Array();
        toggle.slideToggle().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfTaxText.push(jQuery(this).next().html());
                window.mncfFormGroupsSupportTaxState.push(jQuery(this).attr('id'));
            }
        });

        /*
         *
         * Set TEXT
         */
        if (window.mncfTaxText.length < 1) {
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('empty', title, title_not_empty, title_empty)
                );
        } else {
            title_not_empty = window.mncfTaxText.join(', ');
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('has', title, title_not_empty, title_empty)
                );
        }
    /*
         *
         *
         *
         *
         *
         *
         * Do templates
         */
    } else if (edit == 'templates') {
        /*
         *
         * Take snaphot
         */
        window.mncfTemplatesText = new Array();
        window.mncfFormGroupsTemplatesState = new Array();
        toggle.slideUp().find('.checkbox').each(function(index){
            if (jQuery(this).is(':checked')) {
                window.mncfTemplatesText.push(jQuery(this).next().html());
                window.mncfFormGroupsTemplatesState.push(jQuery(this).attr('id'));
            }
        });
        /*
         *
         *
         * Set title
         */
        if (window.mncfTemplatesText.length < 1) {
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('empty', title, title_not_empty, title_empty)
                );
        } else {
            title_not_empty = window.mncfTemplatesText.join(', ');
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('has', title, title_not_empty, title_empty)
                );
        }

    }
	else if (edit == 'admin_styles') {
		jQuery("#mncf-admin-styles-box").css({width:'400px','border-color':'#dfdfdf','box-shadow':'none','z-index':'0'});
		//jQuery('html, body').animate({scrollTop:jQuery('#mncf-admin-styles-box').position().top}, 'fast');
        toggle.slideUp();
    }

    parent.children('a').css('visibility', 'visible');
}

// CANCEL Button
//
//
//
//
function mncfFilterCancelClick(object, edit, title, title_not_empty, title_empty) {
    var toggle = object.parent();
    var parent = toggle.prev('.mncf-filter-wrap');
    /*
     *
     * Built-in filters
     *
     *
     *
     *
     * Do post types
     */
    if (edit == 'custom_post_types') {
        /*
         *
         *
         * Take a snaphot
         */
        toggle.slideUp().find('input').removeAttr('checked');
        if (window.mncfFormGroupsSupportPostTypesState.length > 0) {
            for (var element in window.mncfFormGroupsSupportPostTypesState) {
                jQuery('#'+window.mncfFormGroupsSupportPostTypesState[element])
                .attr('checked', 'checked');
            }
        }
        /*
         *
         *
         * Set title
         */
        if (window.mncfPostTypesText.length > 0) {
            title_not_empty = window.mncfPostTypesText.join(', ');
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('has', title, title_not_empty, title_empty)
                );
        } else {
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('empty', title, title_not_empty, title_empty)
                );
        }
    /*
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         * Now do taxonomies
         */
    } else if (edit == 'custom_taxonomies') {
        /*
         *
         *
         * Take a snaphot
         */
        toggle.slideUp().find('input').removeAttr('checked');
        if (window.mncfFormGroupsSupportTaxState.length > 0) {
            for (var element in window.mncfFormGroupsSupportTaxState) {
                jQuery('#'+window.mncfFormGroupsSupportTaxState[element])
                .attr('checked', 'checked');
            }
        }
        /*
         *
         *
         * Set title
         */
        if (window.mncfTaxText.length > 0) {
            title_not_empty = window.mncfTaxText.join(', ');
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('has', title, title_not_empty, title_empty)
                );
        } else {
            parent.find('.mncf-filter-ajax-response').html(
                _mncfFilterTitle('empty', title, title_not_empty, title_empty)
                );
        }
    /*
         *
         *
         *
         *
         *
         *
         *
         * Do templates
         */
    } else if (edit == 'templates') {
        toggle.slideUp().find('input').removeAttr('checked');
        if (window.mncfFormGroupsTemplatesState.length > 0) {
            for (var element in window.mncfFormGroupsTemplatesState) {
                jQuery('#'+window.mncfFormGroupsTemplatesState[element])
                .attr('checked', 'checked');
            }
        }
        if (window.mncfTemplatesText.length > 0) {
            title_not_empty = window.mncfTemplatesText.join(', ');
            parent.find('.mncf-filter-ajax-response')
            .html(_mncfFilterTitle('has', title, title_not_empty, title_empty));
        } else {
            parent.find('.mncf-filter-ajax-response')
            .html(_mncfFilterTitle('empty', title, title_not_empty, title_empty));
        }
    }
	// Do admin styles
	else if (edit == 'admin_styles') {
	  jQuery("#mncf-admin-styles-box").css({width:'400px','border-color':'#dfdfdf','box-shadow':'none','z-index':'0'});
	  jQuery('html, body').animate({scrollTop:jQuery('#mncf-admin-styles-box').position().top}, 'fast');
      CSSLayoutEditor.setValue( typesBase64.decode(mncfDefaultCss) );
	  toggle.slideUp();
    }

    parent.children('a').css('visibility', 'visible');
}

