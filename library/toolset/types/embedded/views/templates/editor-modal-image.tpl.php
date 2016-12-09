<?php
/*
 * Image editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'alignment' => 'none',
    'alignment_options' => array(),
    'alt' => '',
    'height' => '',
    'image' => '',
    'image_size' => 'full',
    'preview' => '',
    'size_options' => array(),
    'title' => '',
    'warning_remote' => false,
    'width' => '',
    'url' => false,
    'onload' => '',
        ), (array) $data );

if ($data['warning_remote']) {
    if ( mncf_is_embedded() ) {
        $warning_remote = __( 'Remote image resize is disabled, so Types will only resize images that you upload.', 'mncf' );
    } else {
        $warning_remote = sprintf( __( 'Remote image resize is currently disabled, so Types will only resize images that you upload. To change, go to the %sTypes settings page%s.', 'mncf' ),
                '<a href="' . admin_url( 'admin.php?page=mncf-custom-settings#types-image-settings' ) . '" target="_blank">',
                '</a>' );
    }
}
?>

<div data-bind="template: {name:'tpl-types-modal-image'}"></div>

<!--TYPES MODAL IMAGE-->
<script type="text/javascript">
	jQuery( 'body' ).on( 'click', '.js-mncf-attachment-placeholder', function() {
		var inputField = jQuery( '#' + jQuery( this ).data( 'placeholder-target' ) );

		if( inputField.val() == '' ) {
			inputField.val( inputField.val() + jQuery( this ).html() );
		} else {
			inputField.val( inputField.val() + ' ' + jQuery( this ).html() );
		}

		jQuery( '.mn-pointer-buttons .close' ).trigger( 'click' );
		inputField.focus();
	});
</script>

<script id="tpl-types-modal-image" type="text/html">

<div class="fieldset">
	<?php
	if( !function_exists( 'mncf_print_placeholder_list' ) ) {
		function mncf_print_placeholder_list( $target ) {
			$placeholders = array(
				'%%TITLE%%',
				'%%ALT%%',
				'%%CAPTION%%',
				'%%DESCRIPTION%%'
			);

			$placeholder_list = '';

			foreach( $placeholders as $placeholder ) {
				$placeholder_list .= '<br />- ';
				$placeholder_list .= "<a class='js-mncf-attachment-placeholder' data-placeholder-target='" . $target . "' href='javascript:void(0);'>" . $placeholder . '</a>';
			}

			printf(
				__( 'You can display image information using the following placeholders: %s', 'mncf' ),
				$placeholder_list
			);
		}
	}

	?>
	<p>
		<label for="image-title" class="input-title"><?php _e( 'Image title', 'mncf' ); ?> <i class="fa fa-question-circle icon-question-sign js-show-tooltip" data-header="<?php _e( 'Placeholders', 'mncf' ) ?>" data-content="<?php mncf_print_placeholder_list( 'image-title') ?>"></i></label>

		<input id="image-title" type="text" name="title" value="<?php echo $data['title']; ?>" />
	</p>
	<p>
		<label for="image-alt" class="input-title"><?php _e( 'Alternative text', 'mncf' ); ?> <i class="fa fa-question-circle icon-question-sign js-show-tooltip" data-header="<?php _e( 'Placeholders', 'mncf' ) ?>" data-content="<?php mncf_print_placeholder_list( 'image-alt') ?>"></i></label>
		<input id="image-alt" type="text" name="alt" value="<?php echo $data['alt']; ?>" />
	</p>
</div>

<div class="fieldset form-inline">
	<h2><?php _e( 'Position and size', 'mncf' ); ?></h2>
	<p>
	<!--<h2><?php _e( 'Alignment', 'mncf' ); ?></h2>-->
		<label for="image-alignment"><?php _e( 'Alignment', 'mncf' ); ?></label>
		<select id="image-alignment" name="alignment">
			<?php foreach ( $data['alignment_options'] as $align => $title ): ?>
				<option id="image-align-<?php echo $align; ?>"<?php if ( $data['alignment'] == $align ) echo 'selected="selected"'; ?>> <?php echo $align; ?></option>
				<label for="image-align-<?php echo $align; ?>"><?php echo $title; ?></label>
			<?php endforeach; ?>
		</select>
	</p>
</div>

<div class="fieldset form-inline">
	<p>
	<!--<h2><?php _e( 'Pre-defined sizes', 'mncf' ); ?></h2>-->
		<label for="image_size"><?php _e( 'Pre-defined sizes', 'mncf' ); ?></label>
		<select id="image_size" name="image_size" data-bind="value: image_size, disable: ted.params.warning_remote || false ">
			<?php foreach ( $data['size_options'] as $size => $title ): ?>
				<option id="image_size-<?php echo $size; ?>" value="<?php echo $size; ?>">
					<?php echo $title; ?>
				</option>
			<?php endforeach; ?>
		</select>

<?php if ( $data['warning_remote'] ) : ?>
<!--		Conditional icon displaying for dismissed warning message -->
<i class="fa fa-exclamation-triangle icon-warning-sign js-show-tooltip" data-header="<?php _e( 'Image resize disabled', 'mncf' ); ?>" data-content="<?php echo esc_attr($warning_remote); ?>"></i>
<?php endif; ?>

	</p>

	<div class="group-nested" data-bind="visible: image_size() == 'mncf-custom'">
		<p>
	    	<label for="image-width" class="input-title"><?php _e( 'Width', 'mncf' ); ?></label>
	    	<input id="image-width" type="text" name="width" value="<?php echo $data['width']; ?>" />
	    </p>
	    <p>
	    	<label for="image-height" class="input-title"><?php _e( 'Height', 'mncf' ); ?></label>
	    	<input id="image-height" type="text" name="height" value="<?php echo $data['height']; ?>" />
	    </p>
	</div>
</div>

<div class="fieldset form-inline" data-bind="visible: image_size() != 'full'">
    <p><?php _e('If images have a different aspect ratio than the display size, what would you like to do?', 'mncf'); ?></p>
    <p>
        <label for="image-proportional" class="input-title"><?php _e( 'Keep proportional', 'mncf' ); ?></label>
        <input id="image-proportional" type="checkbox" name="proportional" value="1" data-bind="checked: imageKeepProportional" />
    </p>
    <div class="group-nested" data-bind="visible: imageKeepProportional()">
        <p>
            <input id="resize-proportional" type="radio" name="resize" value="proportional" data-bind="checked: imageResize" />
            <label for="resize-proportional" class="input-title"><?php _e( 'Resize images to fit inside the new size. Width or height might be smaller than the specified dimensions.', 'mncf' ); ?></label>
        </p>
        <p>
            <input id="resize-pad" type="radio" name="resize" value="pad" data-bind="checked: imageResize" />
            <label for="resize-pad" class="input-title"><?php _e( 'Pad images, so that they fill the specified dimensions exactly.', 'mncf' ); ?></label>
        </p>
        <p data-bind="visible: imageResize() == 'pad'">
            <label for="padding-transparent" class="input-title"><?php _e( 'Transparent', 'mncf' ); ?></label>
            <input id="padding-transparent" type="checkbox" name="padding_transparent" data-bind="checked: imagePaddingTransparent" value="1" />
        </p>
        <p data-bind="visible: imageResize() == 'pad' && !imagePaddingTransparent()">
            <label for="padding-color" class="input-title"><?php _e( 'Padding color', 'mncf' ); ?></label>
            <input id="padding-color" type="text" name="padding_color" data-bind="value: imagePaddingColor()" class="js-types-colorpicker" />
        </p>
        <p>
            <input id="resize-crop" type="radio" name="resize" value="crop" data-bind="checked: imageResize" />
            <label for="resize-crop" class="input-title"><?php _e( 'Crop images, so that they fill the specified dimensions exactly.', 'mncf' ); ?></label>
        </p>
    </div>
</div>

<p class="form-inline">
	<input id="image-url" type="checkbox" name="url" value="1" data-bind="checked: imageUrl, click: imageUrlDisable" />
	<label for="image-url"><?php _e( 'Output only the URL of the re-sized image instead of the img tag', 'mncf' ); ?></label>
</p>

<!--<input id="image-onload" type="text" name="onload" value="<?php echo $data['onload']; ?>" />
<label for="image-onload"><?php _e( 'Onload callback', 'mncf' ); ?></label>-->


</script><!--END TYPES MODAL IMAGE-->

<?php
MNCF_Loader::loadInclude('fields/colorpicker');
mncf_fields_colorpicker_enqueue_scripts();
mncf_fields_colorpicker_render_js();
?>
