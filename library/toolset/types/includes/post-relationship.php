<?php
/*
 * Post relationship code.
 *
 */
require_once MNCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
add_action( 'mncf_custom_types_save', 'mncf_pr_custom_types_save_action' );

/**
 * Init funtion.
 */
function mncf_post_relationship_init() {
    add_thickbox();
    mn_enqueue_script(
        'mncf-post-relationship',
        MNCF_EMBEDDED_RELPATH . '/resources/js/post-relationship.js',
        array('jquery', 'toolset_select2'),
        MNCF_VERSION
    );
    add_filter('mncf_meta_box_order_defaults', 'mncf_post_relationship_add_metabox', 10, 2);
}

/**
 * add metabox relationship to list
 */

function mncf_post_relationship_add_metabox($meta_boxes, $type )
{
    if ( 'post_type' == $type ) {
        $meta_boxes['relationship'] = array(
            'callback' => 'mncf_admin_metabox_relationship',
            'title' => __('Post Relationships (Parent / Child)', 'mncf'),
            'default' => 'normal',
            'priority' => 'low',
        );
    }
    return $meta_boxes;
}

/**
 * Saves relationships.
 *
 * @param type $data
 */
function mncf_pr_custom_types_save_action( $data )
{
    $relationships = get_option( 'mncf_post_relationship', array() );
    $save_has_data = array();
    // Reset has
    if ( !empty( $relationships[$data['slug']] ) ) {
        foreach ( $relationships[$data['slug']] as $post_type_has => $rel_data ) {
            if ( !isset( $data['post_relationship']['has'][$post_type_has] ) ) {
                unset( $relationships[$data['slug']][$post_type_has] );
            }
        }
    }
    if ( !empty( $data['post_relationship']['has'] ) ) {
        foreach ( $data['post_relationship']['has'] as $post_type => $true ) {
            if ( empty( $relationships[$data['slug']][$post_type] ) ) {
                $save_has_data[$data['slug']][$post_type] = array();
            } else {
                $save_has_data[$data['slug']][$post_type] = $relationships[$data['slug']][$post_type];
            }
        }
        $relationships[$data['slug']] = $save_has_data[$data['slug']];
    }
    // Reset belongs
    foreach ( $relationships as $post_type => $rel_data ) {
        if ( empty( $data['post_relationship']['belongs'] )
                || !array_key_exists( $post_type, $data['post_relationship']['belongs'] ) ) {
            unset( $relationships[$post_type][$data['slug']] );
        }
    }
    if ( !empty( $data['post_relationship']['belongs'] ) ) {
        foreach ( $data['post_relationship']['belongs'] as $post_type => $true ) {
            if ( empty( $relationships[$post_type][$data['slug']] )
                    && !isset( $relationships[$data['slug']][$post_type] ) ) {
                // Check that can't exist same belongs and has
                $relationships[$post_type][$data['slug']] = array();
            }
        }
    }

    /**
     * sanitization
     */
    $post_types = get_post_types();
    foreach( $relationships as $parent => $data ) {
        if ( !isset($post_types[$parent]) ) {
            unset($relationships[$parent]);
            continue;
        }
        foreach( $data as $child => $chid_data) {
            if ( !isset($post_types[$child]) ) {
                unset($relationships[$parent][$child]);
                continue;
            }
        }
    }

    update_option( 'mncf_post_relationship', $relationships );
}

/**
 * Edit fields form.
 *
 * @param type $parent
 * @param type $child
 */
function mncf_pr_admin_edit_fields( $parent, $child ) {

    $post_type_parent = get_post_type_object( $parent );
    $post_type_child = get_post_type_object( $child );
    if ( null == $post_type_parent || null == $post_type_child ) {
        die( __( 'Wrong post types', 'mncf' ) );
    }
    $relationships = get_option( 'mncf_post_relationship', array() );
    if ( !isset( $relationships[$parent][$child] ) ) {
        die( __( 'Relationship do not exist', 'mncf' ) );
    }
    $data = $relationships[$parent][$child];

    mn_enqueue_script( 'jquery' );
    mn_enqueue_style( 'types' );
    mncf_admin_ajax_head( 'Edit fields', 'mncf' );
    // Process submit
    if ( isset( $_POST['_mnnonce'] ) && mn_verify_nonce( $_POST['_mnnonce'], 'pt_edit_fields' ) ) {
        $relationships[$parent][$child]['fields_setting'] = sanitize_text_field( $_POST['fields_setting'] );

        /**
         * sanitize
         */
        $relationships[$parent][$child]['fields'] = array();
        if (  isset( $_POST['fields'] ) && is_array($_POST['fields'])) {
            $allowed_keys = mncf_post_relationship_get_specific_fields_keys($child);
            foreach( $_POST['fields'] as $key => $value ) {
                /**
                 * sanitize Taxonomy
                 */
                if ( '_mncf_pr_taxonomies' == $key ) {
                    if ( is_array($value) ) {
                        $relationships[$parent][$child]['fields'][$key] = array();
                        foreach( array_keys($value) as $taxonomy) {
                            $taxonomy = get_taxonomy($taxonomy);
                            if ( is_object($taxonomy) ) {
                                $relationships[$parent][$child]['fields'][$key][$taxonomy->name] = 1;
                            }
                        }
                    }
                    continue;
                }
                if ( array_key_exists( $key, $allowed_keys) ) {
                    $relationships[$parent][$child]['fields'][$key] = 1;
                }
            }
        }
        update_option( 'mncf_post_relationship', $relationships );
        ?>
        <script type="text/javascript">
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
            window.parent.location.reload();
        </script>
        <?php
        die();
    }

    $groups = mncf_admin_get_groups_by_post_type( $child );
    $options_cf = array();
    $repetitive_warning = false;
    $repetitive_warning_markup = array();
    $repetitive_warning_txt = __( 'Repeating fields should not be used in child posts. Types will update all field values.', 'mncf' );
    foreach ( $groups as $group ) {
        $fields = mncf_admin_fields_get_fields_by_group( $group['id'] );
        foreach ( $fields as $key => $cf ) {
            $__key = mncf_types_cf_under_control( 'check_outsider', $key ) ? $key : MNCF_META_PREFIX . $key;
            $options_cf[$__key] = array();
            $options_cf[$__key]['#title'] = $cf['name'];
            $options_cf[$__key]['#name'] = 'fields[' . $__key . ']';
            $options_cf[$__key]['#default_value'] = isset( $data['fields'][$__key] ) ? 1 : 0;
            // Repetitive warning
            if ( mncf_admin_is_repetitive( $cf ) ) {
                if ( !$repetitive_warning ) {
                    $repetitive_warning_markup = array(
                        '#type' => 'markup',
                        '#markup' => '<div class="message error" style="display:none;" id="mncf-repetitive-warning"><p>' . $repetitive_warning_txt . '</p></div>',
                    );
                }
                $repetitive_warning = true;
                $options_cf[$__key]['#after'] = !isset( $data['fields'][$__key] ) ? '<div class="message error" style="display:none;"><p>' : '<div class="message error"><p>';
                $options_cf[$__key]['#after'] .= $repetitive_warning_txt;
                $options_cf[$__key]['#after'] .= '</p></div>';
                $options_cf[$__key]['#attributes'] = array(
                    'onclick' => 'jQuery(this).parent().find(\'.message\').toggle();',
                    'disabled' => 'disabled',
                );
            }
        }
    }

    $form = array();
    $form['repetitive_warning_markup'] = $repetitive_warning_markup;
    $form['select'] = array(
        '#type' => 'radios',
        '#name' => 'fields_setting',
        '#options' => array(
            __( 'Title, all custom fields and parents', 'mncf' ) => 'all_cf',
            __( 'Do not show management options for this post type', 'mncf' ) => 'only_list',
            __( 'All fields, including the standard post fields', 'mncf' ) => 'all_cf_standard',
            __( 'Specific fields', 'mncf' ) => 'specific',
        ),
        '#default_value' => empty( $data['fields_setting'] ) ? 'all_cf' : $data['fields_setting'],
    );
    /**
     * check default, to avoid missing configuration
     */
    if ( !in_array($form['select']['#default_value'], $form['select']['#options']) ) {
        $form['select']['#default_value'] = 'all_cf';
    }
    /**
     * build options for "Specific fields"
     */
    $options = array();
    /**
     * check and add built-in properites
     */
    $supports= mncf_post_relationship_get_supported_fields_by_post_type($child);
    foreach ( $supports as $child_field_key => $child_field_data ) {
        $options[$child_field_data['name']] = array(
            '#title' => $child_field_data['title'],
            '#name' => sprintf('fields[%s]', $child_field_data['name']),
            '#default_value' => isset( $data['fields'][$child_field_data['name']] ) ? 1 : 0,
        );
    }
    /**
     * add custom fields
     */
    $options = $options + $options_cf;
    $temp_belongs = mncf_pr_admin_get_belongs( $child );
    foreach ( $temp_belongs as $temp_parent => $temp_data ) {
        if ( $temp_parent == $parent ) {
            continue;
        }
        $temp_parent_type = get_post_type_object( $temp_parent );
        $options[$temp_parent] = array();
        $options[$temp_parent]['#title'] = $temp_parent_type->label;
        $options[$temp_parent]['#name'] = 'fields[_mncf_pr_parents][' . $temp_parent . ']';
        $options[$temp_parent]['#default_value'] = isset( $data['fields']['_mncf_pr_parents'][$temp_parent] ) ? 1 : 0;
    }
    /**
     * remove "Specific fields" if there is no fields
     */
    if ( empty($options) ) {
        unset($form['select']['#options'][__('Specific fields', 'mncf')]);
        if ('specific' == $form['select']['#default_value']) {
            $form['select']['#default_value'] = 'all_cf';
        }
    }

    // Taxonomies
    $taxonomies = get_object_taxonomies( $post_type_child->name, 'objects' );
    if ( !empty( $taxonomies ) ) {
        foreach ( $taxonomies as $tax_id => $taxonomy ) {
            $options[$tax_id] = array();
            $options[$tax_id]['#title'] = sprintf( __('Taxonomy - %s', 'mncf'), $taxonomy->label );
            $options[$tax_id]['#name'] = 'fields[_mncf_pr_taxonomies][' . $tax_id . ']';
            $options[$tax_id]['#default_value'] = isset( $data['fields']['_mncf_pr_taxonomies'][$tax_id] ) ? 1 : 0;
        }
    }
    $form['specific'] = array(
        '#type' => 'checkboxes',
        '#name' => 'fields',
        '#options' => $options,
        '#default_value' => isset( $data['fields'] ),
        '#before' => '<div id="mncf-specific" style="display:none;margin:10px 0 0 20px;">',
        '#after' => '</div>',
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'submit',
        '#value' => __( 'Save', 'mncf' ),
        '#attributes' => array('class' => 'button-primary'),
    );
    echo '<form method="post" action="" class="types-select-child-fields">';
    echo mncf_form_simple( $form );
    echo mn_nonce_field( 'pt_edit_fields' );
    echo '</form>';
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            if (jQuery('input[name="fields_setting"]:checked').val() == 'specific') {
                jQuery('#mncf-specific').show();
            } else {
    <?php if ( $repetitive_warning && 'only_list' != $form['select']['#default_value']) {

?>
                    jQuery('#mncf-repetitive-warning').show();
        <?php
    }
    ?>
            }
            jQuery('input[name="fields_setting"]').change(function(){
                if (jQuery(this).val() == 'specific') {
                    jQuery('#mncf-specific').slideDown();
                } else {
                    jQuery('#mncf-specific').slideUp();
    <?php if ( $repetitive_warning ) { ?>
                    if ( 'only_list' != jQuery('input[name="fields_setting"]:checked').val()) {
                        jQuery('#mncf-repetitive-warning').show();
                    }
        <?php } ?>
                }
            });
        });
    </script>
    <?php
    mncf_admin_ajax_footer();
}

function mncf_admin_metabox_relationship($post_type)
{
    $form = array();
    $form['description'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<p class="description">%s</p>',
            sprintf(
	            '%s <a href="%s" target="_blank">%s</a>.',
	            __( 'Parent/child relationship lets you connect between posts of different types. When you edit a parent, you will see its children listed in a table and you can edit the fields of the children. When you edit a child, you can choose the parents. On the front-end, you can display a list of children or information about the parents.', 'mncf'),
	            Types_Helper_Url::get_url( 'post-relationship', true, 'parent_child_relationship', Types_Helper_Url::UTM_MEDIUM_POSTEDIT ),
	            __( 'Parent/child documentation', 'mncf' )
            )
        )
    );

	$custom_types = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );

	$is_error = false;
	$error_message = '';

	// Detect situations when we cannot configure post relationships yet. Render a message and finish.
	$is_unsaved_post_type = ( ! isset( $_REQUEST['mncf-post-type'] ) || ! isset( $custom_types[ $_REQUEST['mncf-post-type'] ] ) );
	if ( $is_unsaved_post_type ) {
		$is_error = true;
		$error_message = __( 'Please save first, before you can edit the relationship.', 'mncf' );
	}

	$is_attachment = ( isset( $_REQUEST['mncf-post-type'] ) && 'attachment' == $_REQUEST['mncf-post-type'] );
    if( $is_attachment ) {
	    $is_error = true;
	    $error_message = __( 'Post relationships are not allowed for the Media post type.', 'mncf' );
    }

    if( $is_error ) {
        $form['alert'] = array(
            '#type' => 'notice',
            '#markup' => $error_message,
        );
        $form = mncf_form( __FUNCTION__, $form );
        echo $form->renderForm();
        return;
    }

    $post_type = $custom_types[$_REQUEST['mncf-post-type']];

    unset($custom_types);

	// No problems detected, go ahead and render the options.

    // belongs/children section
    $has = mncf_pr_admin_get_has( $post_type['slug'] );
    $belongs = mncf_pr_admin_get_belongs( $post_type['slug'] );
    $post_types = get_post_types( '', 'objects' );

    // parents
    $form['parent-h3'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<h3>%s</h3>',
            __('Parent Post Types:', 'mncf')
        )
    );

    $form['parent-description'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<p class="description">%s</p>',
            __('Select which post types will be parents of this type.', 'mncf')
        )
    );
    $options = array();

    // Build excluded post types
    global $mncf;
    $excluded_post_types = $mncf->excluded_post_types;
    $excluded_post_types[] = $post_type['slug'];
	// Explicitly exclude attachments for post relationships because there is no GUI for it
	// (but we're not excluding them from all Types functionality)
	$excluded_post_types[] = 'attachment';

    foreach ( $post_types as $post_type_option_slug => $post_type_option ) {

    	$is_excluded = in_array( $post_type_option_slug, $excluded_post_types );
	    $has_no_ui = ( ! $post_type_option->show_ui && ! apply_filters('mncf_show_ui_hide_in_relationships', true ) );

        if ( $is_excluded || $has_no_ui ) {
            continue;
        }

        $options[$post_type_option_slug] = array(
            '#name' => 'ct[post_relationship][belongs][' . $post_type_option_slug . ']',
            '#title' => $post_type_option->labels->singular_name,
            '#default_value' => isset( $belongs[$post_type_option_slug] ),
            '#inline' => true,
            '#before' => '<li>',
            '#after' => '</li>',
            '#attributes' => array(
                'class' => 'js-mncf-relationship-checkbox',
                'data-mncf-type' => 'belongs',
                'data-mncf-value' => esc_attr($post_type_option_slug),
                'data-mncf-message-disabled' => esc_attr__('This post type is disabled, becouse is used as child post.', 'mncf'),
            ),
        );
        if ( isset( $has[$post_type_option_slug] ) ) {
            $options[$post_type_option_slug]['#before'] = '<li class="disabled">';
            $options[$post_type_option_slug]['#attributes']['disabled'] = 'disabled';
        }
    }
    $form['table-pr-belongs-form'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'ct[post_relationship]',
        '#inline' => true,
        '#before' => '<ul>',
        '#after' => '</ul>',
    );


    // child posts
    $form['child-h3'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<hr /><h3>%s</h3>',
            __('Children Post Types:', 'mncf')
        )
    );

    $form['child-description'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<p class="description">%s</p>',
            __('Select which post types will be children of this type.', 'mncf')
        )
    );
    $options = array();
    foreach ( $post_types as $post_type_option_slug => $post_type_option ) {
        if (
            in_array( $post_type_option_slug, $excluded_post_types )
            || (
                !$post_type_option->show_ui
                && !apply_filters('mncf_show_ui_hide_in_relationships', true)
            )
        ) {
            continue;
        }

        $nonce = sprintf(
            'child-post-fields-%s-%s',
            $post_type['slug'],
            $post_type_option_slug
        );
        $a = sprintf(
            ' <span>(<a class="js-mncf-edit-child-post-fields" href="#" data-mncf-nonce="%s" data-mncf-child="%s" data-mncf-parent="%s" data-mncf-title="%s" data-mncf-buttons-apply="%s" data-mncf-buttons-cancel="%s" data-mncf-message-loading="%s" data-mncf-save-status="%s">%s</a>)</span>',
            esc_attr(mn_create_nonce($nonce)),
            esc_attr($post_type_option_slug),
            esc_attr($post_type['slug']),
            esc_attr(
                sprintf(
                    __('Select child fields from %s to be displayed in Post Relationship table', 'mncf'),
                    $post_type_option->labels->singular_name
                )
            ),
            esc_attr__('Apply', 'mncf'),
            esc_attr__('Cancel', 'mncf'),
            esc_attr__('Please Wait, Loading…', 'mncf'),
            esc_attr(isset( $has[$post_type_option_slug] )?'saved':'new'),
            esc_attr__('Select fields', 'mncf')
        );

        $options[$post_type_option_slug] = array(
            '#name' => 'ct[post_relationship][has][' . $post_type_option_slug . ']',
            '#title' => $post_type_option->labels->singular_name,
            '#inline' => true,
            '#before' => '<li>',
            '#after' => $a.'</li>',
            '#attributes' => array(
                'class' => 'js-mncf-relationship-checkbox',
                'data-mncf-type' => 'has',
                'data-mncf-value' => esc_attr($post_type_option_slug),
                'data-mncf-message-disabled' => esc_attr__('This post type is disabled, becouse is used as parent post.', 'mncf'),
            ),
        );
        // Check if it already belongs
        if ( isset( $belongs[$post_type_option_slug] ) ) {
            $options[$post_type_option_slug]['#before'] = '<li class="disabled">';
            $options[$post_type_option_slug]['#attributes']['disabled'] = 'disabled';
        } else if ( isset( $has[$post_type_option_slug] ) ) {
            $options[$post_type_option_slug]['#default_value'] = true;
            $options[$post_type_option_slug]['#before'] = '<li class="active">';
        }
    }
    $form['table-pr-has-form'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'ct[post_relationship]',
        '#inline' => true,
        '#before' => '<ul>',
        '#after' => '</ul>',
    );

     $form = mncf_form( __FUNCTION__, $form );
     echo $form->renderForm();
}

/**
 * Get allowed keys
 *
 * Function colect proper keys of all fields - built-in and CF's
 *
 * @since 1.9.0
 *
 * @param string $child post type slug
 * @return array array of allowed slugs
 */
function mncf_post_relationship_get_specific_fields_keys($child)
{
    $options = array();
    $groups = mncf_admin_get_groups_by_post_type( $child );
    foreach ( $groups as $group ) {
        $fields = mncf_admin_fields_get_fields_by_group( $group['id'] );
        foreach ( $fields as $key => $cf ) {
            $__key = mncf_types_cf_under_control( 'check_outsider', $key ) ? $key : MNCF_META_PREFIX . $key;
            $options[$__key] = 1;
        }
    }
    $supports = mncf_post_relationship_get_supported_fields_by_post_type($child);
    foreach ( $supports as $child_field_key => $child_field_data ) {
        $options[$child_field_data['name']] = 1;
    }
    return $options;
}

/**
 * Get built-in post fields
 *
 * Get only built-in post fields based on post type.
 *
 * @since 1.9.0
 *
 * @param string $post_type post type slug
 * @return array larray of supported built-in post fields
 */
function mncf_post_relationship_get_supported_fields_by_post_type($post_type)
{
    $check_support = array(
        'title' => array(
            'name' => '_mn_title',
            'title' => __( 'Post title', 'mncf' )
        ),
        'editor' => array(
            'name' => '_mn_body',
            'title' => __( 'Post body', 'mncf' )
        ),
        'excerpt' => array(
            'name' => '_mn_excerpt',
            'title' => __( 'Post excerpt', 'mncf' )
        ),
        'thumbnail' => array(
            'name' => '_mn_featured_image',
            'title' => __( 'Post featured image', 'mncf' )
        ),
    );
    foreach ( $check_support as $child_field_key => $child_field_data ) {
        if ( post_type_supports( $post_type, $child_field_key ) ) {
            continue;
        }
        unset($check_support[$child_field_key]);
    }
    return $check_support;
}
