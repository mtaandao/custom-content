<?php
/*
 * Plugin contextual help
 *
 *
 */

/**
 * Returns contextual help.
 *
 * @param string $page
 * @param $contextual_help
 *
 * @return string
 * @deprecated Use Types_Asset_Help_Tab_Loader instead.
 */
function mncf_admin_help($page, $contextual_help = '')
{
	Types_Helper_Url::load_documentation_urls();
	Types_Helper_Url::set_medium( Types_Helper_Url::UTM_MEDIUM_HELP );

    $help = '';
    switch ($page) {
        // Post Fields (list)
        case 'custom_fields':
		case 'mncf-cf':
            $help.= ''
                .__("Types plugin organizes post fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.", 'mncf')
                .PHP_EOL
                .PHP_EOL
                .sprintf(
                    __('You can read more about Post Fields in this tutorial: %s.', 'mncf'),
                    sprintf(
	                    '<a href="%s" target="_blank">%s &raquo;</a>',
	                    Types_Helper_Url::get_url( 'using-post-fields', true, 'using-custom-fields' ),
	                    Types_Helper_Url::get_url( 'using-post-fields', false, false, false, false )
                    )
                )
                .PHP_EOL
                .PHP_EOL
                .__("On this page you can see your current post field groups, as well as information about which post types and taxonomies they are attached to, and whether they are active or not.", 'mncf')
                .PHP_EOL
                .PHP_EOL
                .sprintf('<h3>%s</h3>', __('You have the following options:', 'mncf'))
                .'<dl>'
                .'<dt>'.__('Add New', 'mncf').'</dt>'
                .'<dd>'.__('Use this to add a new post fields group which can be attached to a post type', 'mncf').'</dd>'
                .'<dt>'.__('Edit', 'mncf').'</dt>'
                .'<dd>'.__('Click to edit the post field group', 'mncf').'</dd>'
                .'<dt>'.__('Activate', 'mncf').'</dt>'
                .'<dd>'.__('Click to activate a post field group', 'mncf').'</dd>'
                .'<dt>'.__('Deactivate', 'mncf').'</dt>'
                .'<dd>'.__('Click to deactivate a post field group (this can be re-activated at a later date)', 'mncf').'</dd>'
                .'<dt>'.__('Delete', 'mncf').'</dt>'
                .'<dd>'.__('Click to delete a post field group.', 'mncf')
                .' '
                .sprintf('<strong>%s</strong>', __('Warning: This cannot be undone.', 'mncf'))
                .'</dd>'
                .'</dl>'
                ;
            break;

        case 'need-more-help':

			// Post fields
            $help .= sprintf('<h4>%s</h4>', __('Post Fields', 'mncf'));
            $help .= '<ul>';
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'adding-fields', true ),
                __('Adding post fields to content', 'mncf')
            );
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'displaying-fields', true ),
                __('Displaying post fields on front-end', 'mncf')
            );
            $help .= '</ul>';

			// User fields
            $help .= sprintf('<h4>%s</h4>', __('User Fields', 'mncf'));
            $help .= '<ul>';
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'adding-user-fields', true ),
                __('Adding user fields to user profiles', 'mncf')
            );
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'displaying-user-fields', true ),
                __('Displaying user fields on front-end', 'mncf')
            );
            $help .= '</ul>';

	        // Term fields
	        $help .= sprintf(
		        '<h4>%s</h4>
				<ul>
					<li><a target="_blank" href="%s">%s &raquo;</a></li>
					<li><a target="_blank" href="%s">%s &raquo;</a></li>
				</ul>',
		        __( 'Term Fields', 'mncf' ),
		        Types_Helper_Url::get_url( 'adding-term-fields', true ),
		        __( 'Adding term fields to taxonomies', 'mncf' ),
		        Types_Helper_Url::get_url( 'displaying-term-fields', true ),
		        __( 'Displaying term fields on front-end', 'mncf' )
	        );

            $help .= sprintf('<h4>%s</h4>', __('Post Types and Taxonomy', 'mncf'));
            $help .= '<ul>';
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'custom-post-types', true ),
                __('Creating and using post types', 'mncf')
            );
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'custom-taxonomy', true ),
                __('Arranging content with Taxonomy', 'mncf')
            );
            $help .= sprintf(
                '<li><a target="_blank" href="%s">%s &raquo;</a></li>',
                Types_Helper_Url::get_url( 'post-relationship', true ),
                __('Creating parent / child relationships', 'mncf')
            );
            $help .= '</ul>';



            break;
		case 'mncf-ctt':
        case 'custom_taxonomies_list':
            $help .= ''
                . __('This is the Taxonomies list. It provides you with an overview of your data.', 'mncf')
                .PHP_EOL
                .PHP_EOL
                .sprintf(
                    __('You can read more about Post Types and Taxonomies in this tutorial. %s', 'mncf'),
                    sprintf(
	                    '<a href="%s" target="_blank">%s &raquo;</a>',
	                    Types_Helper_Url::get_url( 'custom-post-types', true ),
	                    Types_Helper_Url::get_url( 'custom-post-types', false, false, false, false )
                    )
                )
                .PHP_EOL
                .PHP_EOL
                .sprintf('<h3>%s</h3>', __('On this page you have the following options:', 'mncf'))
                .'<dl>'
                .'<dt>'.__('Add New', 'mncf')
                .'<dd>'.__('Use to create a new Taxonomy', 'mncf')
                .'<dt>'.__('Edit', 'mncf')
                .'<dd>'.__('Click to edit the settings of a Taxonomy', 'mncf').'</dd>'
                .'<dt>'.__('Deactivate', 'mncf')
                .'<dd>'.__('Click to deactivate a Taxonomy (this can be reactivated at a later date)', 'mncf').'</dd>'
                .'<dt>'.__('Duplicate', 'mncf')
                .'<dd>'.__('Click to duplicate a Taxonomy', 'mncf').'</dd>'
                .'<dt>'.__('Delete', 'mncf')
                .'<dd>'.__('Click to delete a Taxonomy.', 'mncf')
                .' '
                .sprintf('<strong>%s</strong>', __('Warning: This cannot be undone.', 'mncf'))
                .'</dd>'
                .'</dl>';
            break;
		case 'mncf-cpt':
        case 'post_types_list':
            $help .= ''
                . __('This is the main admin page for built-in Post Types and your Post Types. It provides you with an overview of your data.', 'mncf')
               .PHP_EOL
               .PHP_EOL
               .__('Post Types are built-in and user-defined content types.', 'mncf')
               .PHP_EOL
               .PHP_EOL
               .sprintf(
                    __('You can read more about Post Types and Taxonomies in this tutorial. %s', 'mncf'),
		            sprintf(
			            '<a href="%s" target="_blank">%s &raquo;</a>',
			            Types_Helper_Url::get_url( 'custom-post-types', true ),
			            Types_Helper_Url::get_url( 'custom-post-types', false, false, false, false )
		            )
                )
                .PHP_EOL
                .PHP_EOL
                .sprintf('<h3>%s</h3>', __('On this page you have the following options:', 'mncf'))
                .'<dl>'
                .'<dt>'.__('Add New', 'mncf').'</dt>'
                .'<dd>'.__('Use to create a new Post Type', 'mncf').'</dd>'
                .'<dt>'.__('Edit', 'mncf').'</dt>'
                .'<dd>'.__('Click to edit the settings of a Post Type', 'mncf').'</dd>'
                .'<dt>'.__('Deactivate', 'mncf').'</dt>'
                .'<dd>'.__('Click to deactivate a Post Type (this can be reactivated at a later date)', 'mncf').'</dd>'
                .'<dt>'.__('Duplicate', 'mncf')
                .'<dd>'.__('Click to duplicate a Post Type', 'mncf').'</dd>'
                .'<dt>'.__('Delete', 'mncf').'</dt>'
                .'<dd>'.__('Click to delete a Post Type.', 'mncf')
                .' '
                .sprintf('<strong>%s</strong>', __('Warning: This cannot be undone.', 'mncf'))
                .'</dd>'
                .'</dl>'
                ;
            break;
		
        // Add/Edit group form page
		case 'mncf-edit':
        case 'edit_group':
            $help .= ''
                .__('This is the edit page for your Post Field Groups.', 'mncf')
                 .PHP_EOL
                 .PHP_EOL
                .__('On this page you can create and edit your groups. To create a group, do the following:', 'mncf')
                .'<ol style="list-style-type:decimal;"><li style="list-style-type:decimal;">'
                .__('Add a Title.', 'mncf')
                .'</li><li style="list-style-type:decimal;">'
                .__('Choose where to display your group. You can attach this to both default Mtaandao post types and Post Types (you can also associate Taxonomy terms with Post Field Groups).', 'mncf')
                .'</li><li style="list-style-type:decimal;">'
                .__('To add a field, click on "Add New Field" and choose the field you desire. This will be added to your Post Field Group.', 'mncf')
                .'</li><li style="list-style-type:decimal;">'
                .__('Add information about your Post Field.', 'mncf')
                .'</li></ol>'
                .'<h3>' .__('Tips', 'mncf') .'</h3>'
                .'<ul><li>'
                .__('To ensure a user fills out a field, check Required in Validation section.', 'mncf')
                .'</li><li>'
                .__('Once you have created a field, it will be saved for future use under "Choose from previously created fields" of "Add New Field" dialog.', 'mncf')
                .'</li><li>'
                .__('You can drag and drop the order of your post fields.', 'mncf')
                .'</li></ul>';
            break;

            // Add/Edit custom type form page
		case 'mncf-edit-type':
        case 'edit_type':
            $help .= ''
               .__('Use this page to create a Mtaandao post type. If you’d like to learn more about Post Types you can read our detailed guide: <a href="https://mn-types.com/user-guides/create-a-custom-post-type/" target="_blank">https://mn-types.com/user-guides/create-a-custom-post-type/</a>', 'mncf')
               .PHP_EOL
               .PHP_EOL
               .'<dt>'.__('Name and Description', 'mncf').'</dt>'
               .'<dd>'.__('Add a singular and plural name for your post type. You should also add a slug. This will be created from the post type name if none is added.', 'mncf').'</dd>'
               .'<dt>'.__('Visibility', 'mncf').'</dt>'
               .'<dd>'.__('Determine whether your post type will be visible on the admin menu to your users.', 'mncf').'</dd>'
               .'<dd>'.__('You can also adjust the menu position. The default position is 20, which means your post type will appear under “Pages”. You can find more information about menu positioning in the Mtaandao Codex. <a href="http://codex.mtaandao.org/Function_Reference/register_post_type#Parameters" target="_blank">http://codex.mtaandao.org/Function_Reference/register_post_type#Parameters</a>', 'mncf').'</dd>'
               .'<dd>'.__('The default post type icon is the pushpin icon that appears beside Mtaandao posts. You can change this by adding your own icon of 16px x 16px.', 'mncf').'</dd>'
               .'<dt>'.__('Select Taxonomies', 'mncf').'</dt>'
               .'<dd>'.__('Choose which taxonomies are to be associated with this post type.', 'mncf').'</dd>'
               .'<dt>'.__('Labels', 'mncf').'</dt>'
               .'<dd>'.__('Labels are the text that is attached to your post type name. Examples of them in use are “Add New Post” (where “Add New” is the label”) and “Edit Post” (where “Edit” is the label). In normal circumstances the defaults will suffice.', 'mncf').'</dd>'
               .'<dt>'.__('Custom Post Properties', 'mncf').'</dt>'
               .'<dd>'.__('Choose which sections to display on your “Add New” page.', 'mncf').'</dd>'
               .'<dt>'.__('Advanced Settings', 'mncf').'</dt>'
               .'<dd>'.__('Advanced settings give you even more control over your post type. You can read in detail what all of these settings do on our tutorial.', 'mncf').'</dd>'
                .'</dl>'
                ;
            break;

        // Add/Edit Taxonomy form page
		case 'mncf-edit-tax':
        case 'edit_tax':
            $help .= ''
                .__('You can use Taxonomies to categorize your content. Read more about what they are on our website: <a href="https://mn-types.com/user-guides/create-a-custom-post-type/" target="_blank">https://mn-types.com/user-guides/create-a-custom-post-type/ &raquo;</a> or you can read our guide about how to set them up: <a href="http://mn-types.com/user-guides/create-custom-taxonomies/" target="_blank">http://mn-types.com/user-guides/create-custom-taxonomies/</a>', 'mncf')
                .'<dl>'
                .'<dt>'.__('Name and Description', 'mncf') .'</dt>'
                .'<dd>'.__('Add a singular and plural name for your Taxonomy. You should also add a slug. This will be created from the Taxonomy name if none is added.', 'mncf').'</dd>'
                .'<dt>'.__('Visibility', 'mncf') .'</dt>'
                .'<dd>'.__('Determine whether your Taxonomy will be visible on the admin menu to your users.', 'mncf').'</dd>'
                .'<dt>'.__('Select Post Types', 'mncf') .'</dt>'
                .'<dd>'.__('Choose which Post Types this Taxonomy should be associated with.', 'mncf').'</dd>'
                .'<dt>'.__('Labels', 'mncf') .'</dt>'
                .'<dd>'.__('Labels are the text that is attached to your Taxonomy name. Examples of them in use are “Add New Taxonomy” (where “Add New” is the label”) and “Edit Taxonomy” (where “Edit” is the label). In normal circumstances the defaults will suffice.', 'mncf').'</dd>'
                .'<dt>'.__('Options', 'mncf') .'</dt>'
                .'<dd>'.__('Advanced settings give you even more control over your Taxonomy. You can read in detail what all of these settings do on our tutorial.', 'mncf').'</dd>'
                .'</dl>'
                ;
            break;
		case 'mncf-um':
        case 'user_fields_list':
            $help .= ''
                .__("Types plugin organizes User Fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.", 'mncf')
                .PHP_EOL
                .PHP_EOL
                .__("On this page you can see your current User Fields groups, as well as information about which user role they are attached to, and whether they are active or not.", 'mncf')
                . sprintf('<h3>%s</h3>', __('You have the following options:', 'mncf'))
                .'<dl>'
                .'<dt>'.__('Add New', 'mncf').'</dt>'
                .'<dd>'.__('Use this to add a new User Field Group', 'mncf').'</dd>'
                .'<dt>'.__('Edit', 'mncf').'</dt>'
                .'<dd>'.__('Click to edit the User Field Group', 'mncf').'</dd>'
                .'<dt>'.__('Activate', 'mncf').'</dt>'
                .'<dd>'.__('Click to activate a User Field Group', 'mncf').'</dd>'
                .'<dt>'.__('Deactivate', 'mncf').'</dt>'
                .'<dd>'.__('Click to deactivate a User Field Group (this can be re-activated at a later date)', 'mncf').'</dd>'
                .'<dt>'.__('Delete', 'mncf').'</dt>'
                .'<dd>'.__('Click to delete a User Field Group.', 'mncf')
                .' '
                .sprintf('<strong>%s</strong>', __('Warning: This cannot be undone.', 'mncf'))
                .'</dd>'
                .'</dl>'
                ;
            break;
		case 'mncf-edit-usermeta':
        case 'user_fields_edit':
            $help .= ''
                .__('This is the edit page for your User Field Groups.', 'mncf')
                .PHP_EOL
                .PHP_EOL
                . __('On this page you can create and edit your groups. To create a group, do the following:', 'mncf')
                .'<ol><li>'
                . __('Add a Title', 'mncf')
                .'</li><li>'
                . __('Choose where to display your group. You can attach this to both default Mtaandao user roles and custom roles.', 'mncf')
                .'</li><li>'
                . __('To add a field, click on "Add New Field" and choose the field you desire. This will be added to your User Field Group.', 'mncf')
                .'</li><li>'
                . __('Add information about your User Field.', 'mncf')
                .'</li></ol>'
                .'<h3>' . __('Tips', 'mncf') .'</h3>'
                .'<ul><li>'
                . __('To ensure a user fills out a field, check Required in Validation section.', 'mncf')
                .'</li><li>'
                . __('Once you have created a field, it will be saved for future use under "Choose from previously created fields" of "Add New Field" dialog.', 'mncf')
                .'</li><li>'
                . __('You can drag and drop the order of your user fields.', 'mncf')
                .'</li></ul>';
            break;


    }

	// to keep already translated strings
	$help = str_replace(
		'href="https://mn-types.com/user-guides/create-a-custom-post-type/"',
		'href="' . Types_Helper_Url::get_url( 'custom-post-types', true ) . '"', $help
	);
	$help = str_replace(
		'href="http://mn-types.com/user-guides/create-a-custom-post-type/"',
		'href="' . Types_Helper_Url::get_url( 'custom-post-types', true ) . '"', $help 
	);
	$help = str_replace( 
		'href="http://mn-types.com/user-guides/create-custom-taxonomies/"', 
		'href="' . Types_Helper_Url::get_url( 'custom-taxonomy', true ) . '"', $help 
	);
	$help = str_replace( 
		'href="http://mn-types.com/user-guides/using-custom-fields/"', 
		'href="' . Types_Helper_Url::get_url( 'using-post-fields', true, 'post-fields' ) . '"', $help 
	);
	
    return mnautop( $help );
}

/**
 * @deprecated Use Types_Asset_Help_Tab_Loader instead.
 */
function mncf_admin_help_add_tabs_load_hook() {
	
	$screen = get_current_screen();
	
    if ( is_null( $screen ) ) {
        return;
    }
	
	$current_page = '';
	if ( isset( $_GET['page'] ) ) {
	    $current_page = sanitize_text_field( $_GET['page'] );
	} else {
		return;
	}
	
	$contextual_help = mncf_admin_help( $current_page );
	
	if ( ! empty( $contextual_help ) ) {
		$title = '';
		switch ( $current_page ) {
			// Post Fields (list)
			case 'custom_fields':
			case 'mncf-cf':
				$title = __('Post Fields', 'mncf');
				break;
			case 'need-more-help':
				break;
			case 'mncf-ctt':
			case 'custom_taxonomies_list':
				$title =  __( 'Taxonomies', 'mncf' );
				break;
			case 'mncf-cpt':
			case 'post_types_list':
				$title =  __( 'Post Types', 'mncf' );
				break;
			// Add/Edit group form page
			case 'mncf-edit':
			case 'edit_group':
				$title = __('Post Field Group', 'mncf');
				break;
				// Add/Edit custom type form page
			case 'mncf-edit-type':
			case 'edit_type':
				$title =  __( 'Post Type', 'mncf' );
				break;
			// Add/Edit Taxonomy form page
			case 'mncf-edit-tax':
			case 'edit_tax':
				$title =  __( 'Taxonomy', 'mncf' );
				break;
			case 'mncf-um':
			case 'user_fields_list':
				$title = __('User Field Groups', 'mncf');
				break;
			case 'mncf-edit-usermeta':
			case 'user_fields_edit':
				$title = __('User Field Group', 'mncf');
				break;
		}
		$args = array(
			'title'		=> $title,
			'id'		=> 'mncf',
			'content'	=> $contextual_help,
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );

		/**
		 * Need Help section for a bit advertising
		 */
		$args = array(
			'title'		=> __( 'Need More Help?', 'mncf' ),
			'id'		=> 'custom_fields_group-need-help',
			'content'	=> mncf_admin_help( 'need-more-help' ),
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );
	}
}

/**
 * @param $call
 * @param $hook
 * @param string $contextual_help
 * @deprecated Use Types_Asset_Help_Tab_Loader instead.
 */
function mncf_admin_help_add_tabs($call, $hook, $contextual_help = '')
{

    set_current_screen( $hook );
    $screen = get_current_screen();
    if ( is_null( $screen ) ) {
        return;
    }

    $title =  __( 'Types', 'mncf' );

    switch($call) {

    case 'edit_type':
        $title =  __( 'Post Type', 'mncf' );
        break;

    case 'post_types_list':
            $title =  __( 'Post Types', 'mncf' );
            break;

    case 'custom_taxonomies_list':
        $title =  __( 'Taxonomies', 'mncf' );
        break;

    case 'edit_tax':
        $title =  __( 'Taxonomy', 'mncf' );
        break;

    case 'custom_fields':
        $title = __('Post Fields', 'mncf');
        break;

    case 'edit_group':
        $title = __('Post Field Group', 'mncf');
        break;

    case 'user_fields_list':
        $title = __('User Field Groups', 'mncf');
        break;

    case 'user_fields_edit':
        $title = __('User Field Group', 'mncf');
        break;

    }

    $args = array(
        'title' => $title,
        'id' => 'mncf',
        'content' => mncf_admin_help( $call, $contextual_help),
        'callback' => false,
    );
    $screen->add_help_tab( $args );

    /**
     * Need Help section for a bit advertising
     */
    $args = array(
        'title' => __( 'Need More Help?', 'mncf' ),
        'id' => 'custom_fields_group-need-help',
        'content' => mncf_admin_help( 'need-more-help', $contextual_help ),
        'callback' => false,
    );
    $screen->add_help_tab( $args );

}
