<?php
$marketing = new MNCF_Types_Marketing_Messages();
$show_documentation_link = false;
?>
<?php
if ( $top = $marketing->show_top($update) ) {
    echo '<div class="mncf-notif">';
    echo $top;
    echo '</div>';
    } else {

        $message = false;

        switch( $type ) {
        case 'post_type':
            if ( $update ) {
                $message = __( 'Congratulations! Your Post Type %s was successfully updated.', 'mncf' );
            } else {
                $message = __( 'congratulations! your new Post Type %s was successfully created.', 'mncf' );
            }
            break;
        case 'fields':
            if ( $update) {
                $message = __( 'Congratulations! Your custom fields group %s was successfully updated.', 'mncf' );
            } else {
                $message = __( 'Congratulations! Your new custom fields group %s was successfully created.', 'mncf' );
            }
            break;
        case 'taxonomy':
            if ( $update) {
                $message = __( 'Congratulations! Your Taxonomy %s was successfully updated.', 'mncf' );
            } else {
                $message = __( 'Congratulations! Your new Taxonomy %s was successfully created.', 'mncf' );
            }
            break;
        case 'usermeta':
            if ( $update) {
                $message = __( 'Congratulations! Your user meta group %s was successfully updated.', 'mncf' );
            } else {
                $message = __( 'Congratulations! Your new user meta group %s was successfully created.', 'mncf' );
            }
            break;
        }
        $message = sprintf($message, sprintf('<strong>%s</strong>', $title));
        $marketing->update_message($message);
?>
<?php if ( $show_documentation_link ) { ?>
    <a href="javascript:void(0);" class="mncf-button show <?php if ( $update ) echo 'mncf-show'; else echo 'mncf-hide'; ?>"><?php _e( 'Show next steps and documentation', 'mncf' ); ?><span class="mncf-button-arrow show"></span></a>
<?php

	}

    $class = $update ? ' mncf-hide' : ' mncf-show';

	Types_Helper_Url::load_documentation_urls();
	Types_Helper_Url::set_medium( 'next-steps' );

	?>
    <div class="mncf-notif-dropdown<?php echo $class; ?>">
        <span><strong><?php _e( 'Next, learn how to:', 'mncf' ); ?></strong></span>
        <?php if ( $type == 'post_type' ): ?>
            <ul>
                <li>
	                <?php
                    printf(
	                    '<a target="_blank" href="%s">%s &raquo;</a>',
	                    Types_Helper_Url::get_url( 'adding-fields', true ),
	                    __( 'Enrich content using <strong>custom fields</strong>', 'mncf' )
                    );
	                ?>
                </li>

	            <li>
		            <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'custom-taxonomy', true, 'using-taxonomy' ),
		                __( 'Organize content using <strong>taxonomy</strong>', 'mncf' )
	                );
		            ?>
	            </li>

                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'parent-child', true ),
		                __( 'Connect post types as <strong>parents and children</strong>', 'mncf' )
	                );
	                ?>
                </li>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'custom-post-archives', true ),
		                __( 'Display custom post <strong>archives</strong>', 'mncf' )
	                );
	                ?>
                </li>
            </ul>
        <?php elseif ( $type == 'taxonomy' ): ?>
            <ul>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'using-taxonomy', true ),
		                __( 'Organize content using <strong>taxonomy</strong>', 'mncf' )
	                );
	                ?>
                </li>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'custom-taxonomy-archives', true ),
		                __( 'Display Taxonomy <strong>archives</strong>', 'mncf' )
	                );
	                ?>
                </li>
            </ul>
        <?php elseif ( $type == 'usermeta' ): ?>   
        	<ul>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'displaying-user-fields', true ),
		                __( 'Display user fields', 'mncf' )
	                );
	                ?>
                </li>
            </ul> 
        <?php else: ?>
            <ul>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'displaying-fields', true ),
		                __( 'Display post fields', 'mncf' )
	                );
	                ?>
                </li>
                <li>
	                <?php
	                printf(
		                '<a target="_blank" href="%s">%s &raquo;</a>',
		                Types_Helper_Url::get_url( 'repeating-fields-group', true ),
		                __( 'Create groups of repeating fields', 'mncf' )
	                );
	                ?>
                </li>
            </ul>
        <?php endif; ?>

        <div class="hr"></div>

        <span><strong><?php _e( 'Build complete sites without coding:', 'mncf' ); ?></strong></span>
        <ul>
            <li>
	            <?php
	            printf(
		            '<a target="_blank" href="%s">%s &raquo;</a>',
		            Types_Helper_Url::get_url( 'single-pages', true ),
		            __( 'Design templates for single pages', 'mncf' )
	            );
	            ?>
            </li>
            <li>
	            <?php
	            printf(
		            '<a target="_blank" href="%s">%s &raquo;</a>',
		            Types_Helper_Url::get_url( 'views-user-guide', true, 'query-and-display' ),
		            __( 'Load and display custom content', 'mncf' )
	            );
	            ?>
            </li>
        </ul>

        <a href="javascript:void(0);" class="mncf-button hide" style="float:right;"><?php _e( 'Hide notifications', 'mncf' ); ?><span class="mncf-button-arrow hide"></span></a>
    </div><!-- END .mncf-notif-dropdown -->
        <?php } ?>
