<?php
/**
 *
 *
 */

if ( is_admin() ) {
    $child_div_classes = array( 'js-mnt-field-items' );
    if ( $cfg['use_bootstrap'] && in_array( $cfg['type'], array( 'date', 'select' ) ) ) {
        $child_div_classes[] = 'form-inline';
    }
    ?><div class="js-mnt-field mnt-field js-mnt-<?php echo $cfg['type']; ?> mnt-<?php echo $cfg['type']; ?><?php if ( @$cfg['repetitive'] ) echo ' js-mnt-repetitive mnt-repetitive'; ?><?php do_action('mntoolset_field_class', $cfg); ?>" data-mnt-type="<?php echo $cfg['type']; ?>" data-mnt-id="<?php echo $cfg['id']; ?>">
        <div class="<?php echo implode( ' ', $child_div_classes ); ?>">
	<?php foreach ( $html as $out ):
		include 'metaform-item.php';
	endforeach; ?>
    <?php if ( @$cfg['repetitive'] ): ?>
        <a href="#" class="js-mnt-repadd mnt-repadd button button-small button-primary-toolset" data-mnt-type="<?php echo $cfg['type']; ?>" data-mnt-id="<?php echo $cfg['id']; ?>"><?php echo apply_filters( 'toolset_button_add_repetition_text', sprintf(__('Add new %s', 'mnv-views'), $cfg['title']), $cfg); ?></a>
	<?php endif; ?>
		</div>
	</div>
<?php
} else {
	// CHeck if we need a wrapper
	$types_without_wrapper = array( 'submit', 'hidden' );
	$needs_wrapper = true;
	if ( isset( $cfg['type'] ) && in_array( $cfg['type'], $types_without_wrapper ) ) {
		$needs_wrapper = false;
	}
	// Adjust the data-initial-conditional
	ob_start();
	do_action('mntoolset_field_class', $cfg);
	$conditional_classes = ob_get_clean();
	if (strpos($conditional_classes, 'mnt-hidden') === false) {
		$conditional_classes = '';
	} else {
		$conditional_classes = 'true';
	}
	// Adjust classnames for container and buttons
	$button_extra_classnames = '';
	$container_classes = '';
	if ( array_key_exists( 'use_bootstrap', $cfg ) && $cfg['use_bootstrap'] ) {
		$button_extra_classnames .= ' btn btn-default btn-sm';
		$container_classes .= ' form-group';
	}
	if ( array_key_exists( 'repetitive', $cfg ) ) {
		$container_classes .= ' js-mnt-repetitive mnt-repetitive';
	}
	// Render
	if ( $needs_wrapper) {
        $identifier = $cfg['type'] . '-' . $cfg['name'];
		echo '<div class="js-mnt-field-items' . $container_classes . '" data-initial-conditional="' . $conditional_classes . '" data-item_name="' . $identifier .'">';
	}
    foreach ( $html as $out ) {
        include 'metaform-item.php';
    }
	if ( $cfg['repetitive'] ) {
		echo '<input type="button" class="js-mnt-repadd mnt-repadd' . $button_extra_classnames . '" data-mnt-type="' . $cfg['type'] . '" data-mnt-id="' . $cfg['id'] . '" value="';
		echo apply_filters( 'toolset_button_add_repetition_text', esc_attr( sprintf( __( 'Add new %s', 'mnv-views' ), $cfg['title'] ) ), $cfg );
		echo '" />';
	}
	if ( $needs_wrapper) {
		echo '</div>';
	}
}

