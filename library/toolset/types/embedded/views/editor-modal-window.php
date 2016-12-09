<?php
/*
 * Editor modal window.
 * Used to display shortcode options and trigger inserting.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge(
    array(
        'field_name' => $data['field_type_data']['title'],
        'title' => stripcslashes(sprintf('%s %s', mn_kses_post($data['field']['name']), __('field', 'mncf'))),
        'submit_button_title' => __('Insert shortcode', 'mncf'),
        'tabs' => array(),
        'supports' => array(),
        'user_form' => '',
        'parents' => array(),
        'post_types' => array(),
        'style' => '',
        'class' => '',
        'is_repetitive' => false,
        'show_name' => false,
    ),
    (array) $data
);


?>

<!-- TYPES MODAL WINDOW -->
<div id="types-editor-modal" class="clearfix" style="visibility: hidden">
    <div class="types-media-modal mn-core-ui">
        <div class="types-media-modal-content">
            <div class="types-media-frame-menu">
                <div class="types-media-menu">
                    <?php foreach ( $data['tabs'] as $tab ): ?>
                        <a class="types-media-menu-item js-raw-disable" href="#"><?php echo $tab['menu_title']; ?></a>
                    <?php endforeach; ?>
                    <a id="menu-item-styling" class="types-media-menu-item js-raw-disable" href="#" data-bind="visible: showMenuStyling(), tedSupports: 'styling'"><?php _e( 'Styling', 'mncf' ); ?></a>
                    <a class="types-media-menu-item" data-bind="tedSupports: 'separator'" href="#"><?php _e( 'Separator', 'mncf' ); ?></a>
                    <a class="types-media-menu-item" data-bind="tedSupports: 'user_id'" href="#"><?php _e( 'User','mncf' ); ?></a>
                    <a class="types-media-menu-item" data-bind="tedSupports: 'post_id'" href="#"><?php _e( 'Post selection', 'mncf' ); ?></a>
                    <div class="separator"></div>
                    <p class="form-inline">
                        <input type="checkbox" id="types-modal-raw" name="raw_mode" value="1" data-bind="checked: raw, click: rawDisableAll" />
                        <label for="types-modal-raw"><?php _e( 'Display this field without any formatting', 'mncf' ); ?></label>
                        <i class="fa fa-question-circle icon-question-sign js-show-tooltip" data-header="<?php _e( 'RAW mode', 'mnv-views' ) ?>" data-content="<?php _e( 'When checked, displays raw data stored in database.', 'mncf' ) ?>"></i>
                    </p>
                </div>
            </div>

            <div class="types-media-frame-title">
                <div class="types-media-frame-title-inner">
                    <h1><i class="<?php echo $data['icon_class']; ?>"></i><?php echo $data['title']; ?></h1>
                </div>
                <i class="fa fa-times icon-remove js-close-types-popup"></i>
            </div>

            <div class="types-media-frame-content">
                <div class="types-media-frame-content-inner">
                    <div class="message updated" data-bind="visible: raw()">
                        <p><?php _e( 'RAW mode is selected. The field value will be displayed, without any formatting.', 'mncf' ); ?></p>
                    </div>
                        <?php foreach ( $data['tabs'] as $tab ): ?>
                        <div class="tab js-raw-disable">
                            <h2><?php echo $tab['title']; ?></h2>
                            <?php echo $tab['content']; ?>
                        </div>
                        <?php endforeach; ?>
                    <div class="tab js-raw-disable" data-bind="tedSupports: 'styling', template: {name:'tpl-types-editor-modal-styling'}"></div>
                    <div class="tab" data-bind="tedSupports: 'separator', template: {name:'tpl-types-editor-modal-separator'}"></div>
                    
                    <div class="tab" data-bind="tedSupports: 'post_id', template: {name:'tpl-types-editor-modal-post_id'}"></div>
					<div class="mncf-extra" data-bind="tedSupports: 'term_id', template: {name:'tpl-types-editor-modal-term_id'}"></div>
					<div class="tab" data-bind="tedSupports: 'user_id',  template: {name:'tpl-types-editor-modal-user_id'}"></div>
                </div>
            </div>
            <div class="types-media-frame-toolbar">
                <div class="types-media-frame-toolbar-inner">
                    <div class="media-toolbar-secondary"></div>
                    <div class="types-media-toolbar-primary">
                        <a class="button media-button button-secondary button-large media-button-cancel" href="#"><?php _e( 'Cancel', 'mncf' ); ?></a>
                        <a class="button media-button button-primary button-large media-button-insert" href="#"><?php echo $data['submit_button_title']; ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STYLE FORM -->
<script id="tpl-types-editor-modal-styling" type="text/html">

    <h2><?php _e( 'Styling', 'mncf' ); ?></h2>
    <p><?php _e( 'You can style this field by applying a CSS class to it, or by entering the CSS attributes that would appear with the HTML.', 'mncf' ); ?></p>
    <div class="fieldset">
        <p>
            <label for="types-modal-css-class" class="input-title"><?php _e( 'CSS class:', 'mncf' ); ?></label>
            <input type="text" name="class" value="<?php echo $data['class']; ?>" id="types-modal-css-class" data-bind="disable: !output()" />
            <span class="help-text"><?php _e( 'enter your class names separated by a space (e.g. myclass1 myclass2)', 'mncf' ); ?></span>
        </p>
        <p>
            <label for="types-modal-style" class="input-title"><?php _e( 'CSS style:', 'mncf' ); ?></label>
            <input type="text" name="style" value="<?php echo $data['style']; ?>" id="types-modal-style" data-bind="disable: !output() || !supports('style')" />
            <!-- ko ifnot: supports('style') -->
            <i class="fa fa-exclamation-triangle icon-warning-sign js-show-tooltip" data-header="<?php _e( 'Warning', 'mncf' ); ?>" data-content="<?php printf( __( 'Style is not available for %s field', 'mncf' ), $data['field_name'] ); ?>"></i><?php printf( __( 'Style is not available for %s field', 'mncf' ), $data['field_name'] ); ?><!-- /ko -->
            <!-- ko if: supports('style') -->
            <span class="help-text"><?php _e( 'enter the css for your inline style (e.g. color:red;font-weight:bold)', 'mncf' ); ?></span><!-- /ko -->
        </p>
    </div>

</script><!-- END STYLE FORM -->

<!-- SEPARATOR FORM -->
<script id="tpl-types-editor-modal-separator" type="text/html">

    <h2><?php _e( 'Separator', 'mncf' ); ?></h2>
    <p>
        <?php _e('The separator will be displayed between each of your repeating field values', 'mncf'); ?>
    </p>
        <div class="fieldset form-inline">
        	<ul>
        		<li>
        			<input id="separator-comma"  type="radio" name="separator" value=", " data-bind="checked: separator" />
        			<label for="separator-comma"><?php _e( 'Comma', 'mncf' ); ?></label>
        		</li>
        		<li>
        			<input id="separator-space" type="radio" name="separator" value=" " data-bind="checked: separator" />
        			<label for="separator-space"><?php _e( 'Space', 'mncf' ); ?></label>
        		</li>
        		<li>
        			<input id="separator-nbsp" type="radio" name="separator" value="&amp;nbsp;" data-bind="checked: separator" />
        			<label for="separator-nbsp"><?php _e( 'Non-breaking space', 'mncf' ); ?></label>
        		</li>
        		<li>
        			<input id="separator-sc" type="radio" name="separator" value=";" data-bind="checked: separator" />
        			<label for="separator-sc"><?php _e( 'Semicolon', 'mncf' ); ?></label>
        		</li>
        		<li>
        			<input id="separator-custom" type="radio" name="separator" value="custom" data-bind="checked: separator" />
        			<label for="separator-custom"><?php _e( 'Custom', 'mncf' ); ?></label>
        			<input type="text" name="separator_custom" value="" data-bind="visible: separator() == 'custom'" />
        		</li>
        	</ul>
        </div>

</script><!-- END SEPARATOR FORM -->

<!-- POST ID FORM -->
<script id="tpl-types-editor-modal-post_id" type="text/html">

    <h2><?php _e( 'Display this field for:', 'mncf' ); ?></h2>

    <p class="form-inline">
        <input type="radio" id="post-id-current" name="post_id" value="current" data-bind="checked: relatedPost"	/>
        <label for="post-id-current"><?php _e( 'The current post being displayed either directly or in a View loop', 'mncf' ); ?></label>
    </p>

    <p class="form-inline">
        <input type="radio" id="post-id-parent" name="post_id" value="parent" data-bind="checked: relatedPost" />
        <label for="post-id-parent"><?php _e( 'The parent of the current post (Mtaandao parent)', 'mncf' ); ?></label>
    </p>

    <?php if ( !empty( $data['parents'] ) ): ?>
    <p class="form-inline">
        <input type="radio" id="post-id-related" name="post_id" value="related" data-bind="checked: relatedPost" />
        <label for="post-id-related"><?php _e( 'The parent of this post, set by Types (parent/child relationship)', 'mncf' ); ?></label>
    </p>
    <div class="group-nested" data-bind="visible: relatedPost() == 'related'">
        <p class="form-inline"><?php foreach ( $data['parents'] as $post ): ?>
        <input type="radio" name="related_post" id="post-id-<?php echo $post->ID; ?>" value="<?php echo $post->post_type; ?>" data-bind="checked: radioPostType" />
        <label for="post-id-<?php echo $post->ID; ?>"><?php echo $post->post_type; ?></label>
    <?php endforeach; ?></p>
    </div>
    <?php endif; ?>

    <?php if ( empty( $data['parents'] ) ): ?>
    <p class="form-inline">
        <input type="radio" id="post-id-related" name="post_id" value="related" data-bind="checked: relatedPost" />
        <label for="post-id-related"><?php _e( 'The parent of this post, set by Types (parent/child relationship)', 'mncf' ); ?></label>
    </p>
    <div class="group-nested">
        <p class="form-inline">
            <label for="post-id-related-post-type"><?php _e( 'Post Type', 'mncf' ); ?></label>
            <select id="post-id-related-post-type" name="related_post" data-bind="selectedOptions: selectPostType">
                <?php foreach ( $data['post_types'] as $post_type ): ?>
                <option value="<?php echo $post_type; ?>"><?php echo $post_type; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
    </div>
    <?php endif; ?>

    <p class="form-inline">
        <input type="radio" id="post-id" name="post_id" value="post_id" data-bind="checked: relatedPost" />
        <label for="post-id"><?php _e( 'A specific post ID', 'mncf' ); ?></label>
    </p>
    <div class="group-nested" data-bind="visible: relatedPost() == 'post_id'">
        <p class="form-inline">
            <label for="post-id-post_id"><?php _e( 'Post selection', 'mncf' ); ?></label>
            <input type="number" id="post-id-post_id" name="specific_post_id" min="0" data-bind="value: specificPostID" />
        </p>
    </div>

</script><!-- END POST ID FORM -->

<!-- TERM ID FORM -->
<script id="tpl-types-editor-modal-term_id" type="text/html">

    <input class="mncf-form-hidden form-hidden hidden" type="hidden" value="true" name="is_termmeta">

</script><!-- END TERM ID FORM -->

<!-- USER ID FORM -->
<script id="tpl-types-editor-modal-user_id" type="text/html">

    <?php if ( in_array( 'user_id', $data['supports'] ) ) { ?>
		<h2><?php _e( 'Display the field for this user', 'mncf' ); ?></h2>
        <?php echo $data['user_form']; ?>
	<?php } ?>

</script><!-- END USER ID FORM -->

<!--<p class="form-inline">
        <input id="types-modal-output" type="checkbox" name="output" value="html" data-bind="checked: output" />
        <label for="types-modal-output"><?php _e( 'Output HTML', 'mncf' ); ?></label>
    </p>-->
    <!--<p class="form-inline">
        <input id="types-modal-showname" type="checkbox" name="show_name" value="1" />
        <label for="types-modal-showname"><?php _e( 'Show name', 'mncf' ); ?></label>
    </p>-->
