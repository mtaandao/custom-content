<?php
// make sure needed css is enqueued
mn_enqueue_style('mncf-css-embedded');

$medium = 'undefined';

if( ( isset( $_REQUEST['post'] ) && isset( $_REQUEST['action'] ) )
    || isset( $_REQUEST['post_type'] ) )  {
	$medium = 'post-edit';
} elseif( isset( $_REQUEST['page'] )
          && ( empty( $medium ) || ('types' == $medium) || ('undefined' == $medium ) ) ) {
    switch ($_REQUEST['page']) {
        case 'mncf-edit-type':
            $medium = 'post-type-edit';
            break;
        case 'mncf-edit-tax':
            $medium = 'taxonomy-edit';
            break;
        case 'mncf-edit':
            $medium = 'custom-fields-group-edit';
            break;
        case 'mncf-edit-usermeta':
            $medium = 'user-fields-group-edit';
            break;
        default:
            $medium = 'types';
    }

}

Types_Helper_Url::load_documentation_urls();
Types_Helper_Url::set_medium( $medium );

return array(
	'title' => __('Displaying Custom Content', 'mncf'),
	'content' => '
		<div class="mncf-howto-views mncf-info-box-with-icon">
            <div class="mncf-icon">
                <span class="icon-types-logo"></span>
            </div>
            <div class="mncf-info">
                <p>
                    ' . __( 'The complete Custom Content package lets you display custom content on the site’s front-end easily, without writing PHP code.', 'mncf' ) . '
                </p>

                 <a target="_blank" href="'. Types_Helper_Url::get_url( 'compare-toolset-php', true ) . '"><b>' . __( 'Custom Content vs. PHP comparison', 'mncf' ) . '</b></a>
            </div>


            <p style="padding-top: 10px; margin-bottom: 0; border-top: 1px solid #eee;">
                <a class="mncf-arrow-right mncf-no-glow" href="javascript:void(0)" data-mncf-toggle-trigger="mncf-displaying-custom-content-php-info">
                    ' . __( 'Show instructions for displaying custom content with PHP', 'mncf' ) . '
                </a>
            </p>

            <div data-mncf-toggle="mncf-displaying-custom-content-php-info" style="display: none;">
                <p>
                    ' . __( 'If you are customizing an existing theme, consider <a href="https://codex.mtaandao.org/Child_Themes" target="_blank">creating a child theme</a> first.', 'mncf' ) . '
                </p>

                <p>
                    ' . __( 'Read about <a href="https://codex.mtaandao.org/Post_Type_Templates" target="_blank">post type templates</a>, in the Mtaandao codex, to learn which template functions you should edit in your theme.', 'mncf' ) . '
                </p>

                <p>
                    ' . __( 'Use <a href="https://codex.mtaandao.org/Class_Reference/MN_Query" target="_blank">MN_Query</a> to load content from the database and display it.', 'mncf' ) . '
                </p>

                <p>
                    ' . sprintf(
							__( 'Use %s to display custom fields in your PHP templates.', 'mncf' ),
							sprintf(
								'<a href="%s" target="_blank">%s</a>',
								Types_Helper_Url::get_url( 'types-fields-api', true ),
								__( 'Types fields API', 'mncf' )
							)
						) . '
                </p>
            </div>
        </div>'
);