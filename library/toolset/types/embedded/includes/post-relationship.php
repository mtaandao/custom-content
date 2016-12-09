<?php
/*
 * Post relationship code.
 *
 *
 */
add_action( 'mncf_admin_post_init', 'mncf_pr_admin_post_init_action', 10, 4 );

add_action( 'save_post', 'mncf_pr_admin_save_post_hook', 20, 2 ); // Trigger afer main hook

/*
 * Temporary fix for https://core.trac.mtaandao.org/ticket/17817
 *
 * Mtaandao 4.7 fixes this issue and the code below is not needed anymore.
 *
 * Supported by MNML 3.6.0 and above.
 */
$mn_version = get_bloginfo( 'version' );
if( version_compare( $mn_version, '4.7' ) == -1 ) {

	global $sitepress;
	$is_mnml_active = (
		defined( 'ICL_SITEPRESS_VERSION' )
		&& ! ICL_PLUGIN_INACTIVE
		&& ! is_null( $sitepress )
		&& class_exists( 'SitePress' )
	);

	$is_mnml_required_version = ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.6.0' ) >= 0 );

	if( $is_mnml_active && $is_mnml_required_version ) {
		// MNML *guarantees* that the mnml_after_save_post action will be fired for each post.
		remove_action( 'save_post', 'mncf_pr_admin_save_post_hook', 20 );
		add_action( 'mnml_after_save_post', 'mncf_pr_admin_save_post_hook', 10, 2 );
	}
}


if ( is_admin() ) {
    add_action('mn_ajax_mncf_relationship_search', 'mncf_pr_admin_mncf_relationship_search');
	// Deprecated since the introduction of select v.4
    add_action('mn_ajax_mncf_relationship_entry', 'mncf_pr_admin_mncf_relationship_entry');
	// Deprecated since the introduction of select v.4
    add_action('mn_ajax_mncf_relationship_delete', 'mncf_pr_admin_mncf_relationship_delete');
	// Deprecated since the introduction of select v.4
    add_action('mn_ajax_mncf_relationship_save', 'mncf_pr_admin_mncf_relationship_save');
	// Used since the introduction of select2 v.4
	add_action('mn_ajax_mncf_relationship_update', 'mncf_pr_admin_mncf_relationship_update');
	
	add_filter( 'mncf_pr_belongs_post_numberposts', 'mncf_pr_belongs_post_numberposts_minimum', PHP_INT_MAX, 1 );
}

/**
 * Init function.
 *
 * Enqueues styles and scripts on post edit page.
 *
 * @param type $post_type
 * @param type $post
 * @param type $groups
 * @param type $mncf_active
 */
function mncf_pr_admin_post_init_action( $post_type, $post, $groups, $mncf_active )
{
    // See if any data
    $has = mncf_pr_admin_get_has( $post_type );
    $belongs = mncf_pr_admin_get_belongs( $post_type );

    /*
     * Enqueue styles and scripts
     */
    if ( !empty( $has ) || !empty( $belongs ) ) {

        $output = mncf_pr_admin_post_meta_box_output( $post, array('post_type' => $post_type, 'has' => $has, 'belongs' => $belongs) );
        add_meta_box(
            'mncf-post-relationship',
            __( 'Post Relationship', 'mncf' ),
            'mncf_pr_admin_post_meta_box',
            $post_type,
            'normal',
            'default',
            array('output' => $output)
        );
        if ( !empty( $output ) ) {
            mn_register_script(
                'mncf-post-relationship',
                MNCF_EMBEDDED_RELPATH . '/resources/js/post-relationship.js',
                array('jquery', 'toolset_select2'),
                MNCF_VERSION
            );
            mn_localize_script(
                'mncf-post-relationship',
                'mncf_post_relationship_messages',
                array(
                    'parent_saving'			=> __('Saving post parent.', 'mncf'),
                    'parent_saving_success'	=> __('Saved.', 'mncf'),
					'parent_per_page'		=> apply_filters( 'mncf_pr_belongs_post_numberposts', 10 )
                )
            );
            mn_enqueue_script( 'mncf-post-relationship');

            mn_enqueue_style( 'mncf-post-relationship',
                    MNCF_EMBEDDED_RELPATH . '/resources/css/post-relationship.css',
                    array(), MNCF_VERSION );
            if ( !$mncf_active ) {
                mncf_enqueue_scripts();
                mn_enqueue_style( 'mncf-pr-post',
                        MNCF_EMBEDDED_RES_RELPATH . '/css/fields-post.css',
                        array(), MNCF_VERSION );
                mn_enqueue_script( 'mncf-form-validation',
                        MNCF_EMBEDDED_RES_RELPATH . '/js/'
                        . 'jquery-form-validation/jquery.validate.min.js',
                        array('jquery'), MNCF_VERSION );
                mn_enqueue_script( 'mncf-form-validation-additional',
                        MNCF_EMBEDDED_RES_RELPATH . '/js/'
                        . 'jquery-form-validation/additional-methods.min.js',
                        array('jquery'), MNCF_VERSION );
            }
            mncf_admin_add_js_settings( 'mncf_pr_del_warning',
                    '\'' . __( 'Are you sure about deleting this post?', 'mncf' ) . '\'' );
            mncf_admin_add_js_settings( 'mncf_pr_pagination_warning',
                    '\'' . __( 'If you continue without saving your changes, they might get lost.', 'mncf' ) . '\'' );
        }
    }
}


/**
 * Determine if a post type can take a part in a post relationship.
 *
 * @param string $post_type_slug
 * @return bool
 * @since 2.3
 */
function mncf_pr_is_post_type_available_for_relationships( $post_type_slug ) {
	$is_active = ( null != get_post_type_object( $post_type_slug ) );
	$is_excluded_from_relationships = ( 'attachment' == $post_type_slug );
	return ( $is_active && ! $is_excluded_from_relationships );
}


/**
 * Gets post types that belong to current post type.
 *
 * @param string $parent_post_type_slug
 * @return array|false
 */
function mncf_pr_admin_get_has( $parent_post_type_slug ) {
    static $cache = array();
    if ( isset( $cache[$parent_post_type_slug] ) ) {
        return $cache[$parent_post_type_slug];
    }
    $relationships = get_option( 'mncf_post_relationship', array() );
    if ( empty( $relationships[$parent_post_type_slug] ) ) {
        return false;
    }
    // See if enabled
    foreach ( $relationships[ $parent_post_type_slug ] as $child_post_type_slug => $ignored ) {
        if ( ! mncf_pr_is_post_type_available_for_relationships( $child_post_type_slug ) ) {
            unset( $relationships[ $parent_post_type_slug ][ $child_post_type_slug ] );
        }
    }
    $cache[$parent_post_type_slug] = !empty( $relationships[$parent_post_type_slug] ) ? $relationships[$parent_post_type_slug] : false;
    return $cache[$parent_post_type_slug];
}

/**
 * Gets post types that current post type belongs to.
 *
 * @param string $post_type
 * @return array|false
 */
function mncf_pr_admin_get_belongs( $post_type ) {
    static $cache = array();
    if ( isset( $cache[$post_type] ) ) {
        return $cache[$post_type];
    }
    $relationships = get_option( 'mncf_post_relationship', array() );
    $results = array();
    if ( is_array( $relationships ) ) {
        foreach ( $relationships as $has => $belongs ) {

            if ( ! mncf_pr_is_post_type_available_for_relationships( $has ) ) {
                continue;
            }

            if ( array_key_exists( $post_type, $belongs ) ) {
                $results[$has] = $belongs[$post_type];
            }
        }
    }
    $cache[$post_type] = !empty( $results ) ? $results : false;
    return $cache[$post_type];
}

/**
 * Meta boxes contents.
 *
 * @param type $post
 * @param type $args
 */
function mncf_pr_admin_post_meta_box( $post, $args )
{
    if ( !empty( $args['args']['output'] ) ) {
        echo $args['args']['output'];
    } else {
        $mncf_pr_admin_belongs = mncf_pr_admin_get_belongs( $post->post_type );
        if ( empty( $mncf_pr_admin_belongs ) ) {
            _e( 'You will be able to manage child posts after saving this post.', 'mncf' );
        } else {
            _e( 'You will be able to add parent posts after saving this post.', 'mncf' );
        }
    }
}

function mncf_admin_notice_post_locked_no_parent() {
    if ( ! $post = get_post() ) {
        return;
    }
    $parent_type = mncf_pr_admin_get_belongs( $post->post_type );
    if ( is_array( $parent_type ) && count( $parent_type ) ) {
        $parent_type = array_shift( array_keys( $parent_type ) );
        $parent_type = get_post_type_object( $parent_type );
    } else {
        return;
    }

    if ( ( $sendback = mn_get_referer() ) && false === strpos( $sendback, 'post.php' ) && false === strpos( $sendback, 'post-new.php' ) ) {
        $sendback_text = __('Go back', 'mncf');
    } else {
        $sendback = admin_url( 'edit.php' );
        if ( 'post' != $post->post_type ) {
            $sendback = esc_url(add_query_arg( 'post_type', $post->post_type, $sendback ));
        }
        $sendback_text = get_post_type_object( $post->post_type )->labels->all_items;
    }
?>
<div id="post-lock-dialog" class="notification-dialog-wrap">
    <div class="notification-dialog-background"></div>
        <div class="notification-dialog">
            <div class="post-locked-message">
                <p>
<?php
    if ( 'auto-draft' == $post->post_status ) {
        printf(
            __( 'You will be able to add child posts after saving at least one <b>%s</b>.', 'mncf' ),
            $parent_type->labels->singular_name
        );
    } else {
        printf(
            __( 'You will be able to edit child posts after saving at least one <b>%s</b>.', 'mncf' ),
            $parent_type->labels->singular_name
        );
    }
?>
                </p>
                <p><a class="button button-primary mn-tab-last" href="<?php echo $sendback; ?>"><?php echo $sendback_text; ?></a></p>
            </div>
        </div>
    </div>
</div>
<?php
}

/**
 * Meta boxes contents output.
 *
 * @param MN_Post $post
 * @param array $args
 *
 * @return string
 */
function mncf_pr_admin_post_meta_box_output( $post, $args )
{
    if ( empty($post) || empty( $post->ID ) ) {
        return array();
    }

    global $mncf;

    $output = '';
    $relationships = $args;
    $post_id = !empty( $post->ID ) ? $post->ID : -1;
    $current_post_type = mncf_admin_get_edited_post_type( $post );

    /*
     * Render has form (child form)
     */
    if ( !empty( $relationships['has'] ) ) {
        foreach ( $relationships['has'] as $post_type => $data ) {
            if ( isset($data['fields_setting']) && 'only_list' == $data['fields_setting'] ) {
                $output .= $mncf->relationship->child_list( $post, $post_type, $data );
            } else {
                $output .= $mncf->relationship->child_meta_form( $post, $post_type, $data );
            }
        }
    }
    /*
     * Render belongs form (parent form)
     */
    if ( !empty( $relationships['belongs'] ) ) {
        $meta = get_post_custom( $post_id );
        $belongs = array('belongs' => array(), 'posts' => array());
        foreach ( $meta as $meta_key => $meta_value ) {
            if ( strpos( $meta_key, '_mncf_belongs_' ) === 0 ) {
                $temp_post = get_post( $meta_value[0] );
                if ( !empty( $temp_post ) ) {
                    $belongs['posts'][$temp_post->ID] = $temp_post;
                    $belongs['belongs'][$temp_post->post_type] = $temp_post->ID;
                }
            }
        }
        foreach ( $relationships['belongs'] as $post_type => $data ) {
            $parent_post_type_object =  get_post_type_object($post_type);
            $output .= '<div class="belongs">';
            $form = mncf_pr_admin_post_meta_box_belongs_form( $post, $post_type, $belongs );
            if ( isset($form[$post_type]) ) {
                $form[$post_type]['#before'] = 
					'<p>' 
					. sprintf(
						__( 'This <em>%s</em> belongs to <em>%s</em>', 'mncf' ),
						get_post_type_object($current_post_type)->labels->singular_name,
						$parent_post_type_object->labels->singular_name
					);
				$button_classname	= ( $form[$post_type]['#default_value'] > 0 ) ? 'button mncf-pr-parent-edit js-mncf-pr-parent-edit' : 'button mncf-pr-parent-edit js-mncf-pr-parent-edit disabled';
				$button_style		= ( $form[$post_type]['#default_value'] > 0 ) ? '' : 'display:none';
				$form[$post_type]['#after'] = 
					'<a'
					. ' href="' . get_edit_post_link( $form[$post_type]['#default_value'] ) . '"' 
					. ' style="' . $button_style . '"'
					. ' class="' . $button_classname . '"'
					. ' target="_blank"'
					. '>' 
						. $parent_post_type_object->labels->edit_item 
					. '</a>'
					. '</p>';
            }
            if ( $x = mncf_form_simple( $form ) ) {
                $output .= $x;
            } else {
                $output .= $parent_post_type_object->labels->not_found;
            }
            $output .= '</div>';
            unset($parent_post_type_object);
        }
    }
    return $output;
}

/**
 * AJAX delete child item call.
 *
 * @param int $post_id
 * @return string
 */
function mncf_pr_admin_delete_child_item( $post_id ) {
    mn_delete_post( $post_id, true );
    return __( 'Post deleted', 'mncf' );
}

/**
 *
 * Belongs form helper to build correct SQL string to prepare.
 *
 * Belongs form helper to build correct SQL string to $mndb->prepare - replace 
 * any item by digital placeholder.
 *
 * @param any $item
 * @return string
 *
 */
function mncf_pr_admin_post_meta_box_belongs_form_items_helper( $item )
{
    return '%d';
}

/**
 * Belongs form.
 *
 * @param type $post
 * @param type $post_type
 * @param type $data
 * @param type $parent_post_type
 */
function mncf_pr_admin_post_meta_box_belongs_form( $post, $type, $belongs )
{
    global $mndb;
    $temp_type = get_post_type_object( $type );
    if ( empty( $temp_type ) ) {
        return array();
    }
    $form = array();
    $id = esc_attr(sprintf('mncf_pr_belongs_%d_%s', $post->ID, $type));
	$belongs_id = isset( $belongs['belongs'][$type] ) ? $belongs['belongs'][$type] : 0;
	
	$options_array				= array();
	
	$values_to_prepare			= array();
	
	$post_status				= array( 'publish', 'private' );
	
	$mnml_join = $mnml_where	= "";
	$is_translated_post_type	= apply_filters( 'mnml_is_translated_post_type', false, $type );
	
	if ( $is_translated_post_type ) {
		$mnml_current_language	= apply_filters( 'mnml_current_language', '' );
		$mnml_join				= " JOIN {$mndb->prefix}icl_translations t ";
		$mnml_where				= " AND p.ID = t.element_id AND t.language_code = %s ";
		$values_to_prepare[]	= $mnml_current_language;
	}
	
	$values_to_prepare[]		= sanitize_text_field( $type );
	
	$not_in_selected = '';
	if ( $belongs_id ) {
		$not_in_selected		= ' AND p.ID != %d';
		$values_to_prepare[]	= (int) $belongs_id;
		$options_array[ $belongs_id ] = array(
			'#title' => get_the_title( $belongs_id ),
			'#value' => $belongs_id,
		);
	} else {
		$options_array[ '' ] = array(
			'#title' => '',
			'#value' => '',
		);
	}
	
	$parents_available = $mndb->get_results(
		$mndb->prepare(
			"SELECT p.ID, p.post_title 
			FROM {$mndb->posts} p {$mnml_join} 
			WHERE p.post_status IN ('" . implode( "','" , $post_status ) . "') 
			{$mnml_where} 
			AND p.post_type = %s 
			{$not_in_selected} 
			ORDER BY p.post_date DESC 
			LIMIT 15",
			$values_to_prepare
		)
	);

	foreach ( $parents_available as $parent_option ) {
		$options_array[ $parent_option->ID ] = array(
			'#title' => $parent_option->post_title,
			'#value' => $parent_option->ID,
		);
	}
	
	
	$form[$type] = array(
        '#type' => 'select',
        '#name' => 'mncf_pr_belongs[' . $post->ID . '][' . $type . ']',
        '#default_value' => $belongs_id,
        '#id' => $id,
		'#options' => $options_array,
        '#attributes' => array(
            'class' => 'mncf-pr-belongs',
            'data-loading' => esc_attr__('Please Wait, Loading…', 'mncf'),
            'data-nounce' => mn_create_nonce($id),
            'data-placeholder' => esc_attr( sprintf( __('Search for %s', 'mncf'), $temp_type->labels->name ) ),
            'data-post-id' => $post->ID,
            'data-post-type' => esc_attr($type),
			'autocomplete'	=> 'off'
        ),
    );

    return $form;
}

/**
 * Updates belongs data.
 *
 * @param int $post_id
 * @param array $data $post_type => $post_id
 * @return string
 */
function mncf_pr_admin_update_belongs( $post_id, $data ) {

    $errors = array();
    $post = get_post( intval( $post_id ) );
    if ( empty( $post->ID ) ) {
        return new MN_Error(
            'mncf_update_belongs',
            sprintf(
                __( 'Missing child post ID %d', 'mncf' ),
                intval( $post_id )
            )
        );
    }

    foreach ( $data as $post_type => $post_owner_id ) {
        // Check if relationship exists
        if ( !mncf_relationship_is_parent( $post_type, $post->post_type ) ) {
            $errors[] = sprintf(
                __( 'Relationship do not exist %s -> %s', 'mncf' ),
                strval( $post_type ),
                strval( $post->post_type )
            );
            continue;
        }
        if ( $post_owner_id == '0' ) {
            delete_post_meta( $post_id, "_mncf_belongs_{$post_type}_id" );
            continue;
        }
        $post_owner = get_post( intval( $post_owner_id ) );
        // Check if owner post exists
        if ( empty( $post_owner->ID ) ) {
            $errors[] = sprintf( __( 'Missing parent post ID %d', 'mncf' ), intval( $post_owner_id ) );
            continue;
        }
        // Check if owner post type matches required
        if ( $post_owner->post_type != $post_type ) {
            $errors[] = sprintf(
                __( 'Parent post ID %d is not type of %s', 'mncf' ),
                intval( $post_owner_id ),
                strval( $post_type )
            );
            continue;
        }
        update_post_meta( $post_id, "_mncf_belongs_{$post_type}_id", $post_owner->ID );
    }

    if ( !empty( $errors ) ) {
        return new MN_Error( 'mncf_update_belongs', implode( '; ', $errors ) );
    }

    return __( 'Post updated', 'mncf' );
}

/**
 * Pagination link.
 *
 * @param type $post
 * @param type $post_type
 * @param type $page
 * @param type $prev
 * @param type $next
 * @return string
 */
function mncf_pr_admin_has_pagination( $post, $post_type, $page, $prev, $next,
        $per_page = 20, $count = 20 ) {

    global $mncf;

    $link = '';
    $add = '';
    if ( isset( $_GET['sort'] ) ) {
        $add .= '&sort=' . sanitize_text_field( $_GET['sort'] );
    }
    if ( isset( $_GET['field'] ) ) {
        $add .= '&field=' . sanitize_text_field( $_GET['field'] );
    }
    if ( isset( $_GET['post_type_sort_parent'] ) ) {
        $add .= '&post_type_sort_parent=' . sanitize_text_field( $_GET['post_type_sort_parent'] );
    }

    /**
     * default for next
     */
    $url_params = array(
        'action' => 'mncf_ajax',
        'mncf_action' => 'pr_pagination',
        'page' => $page + 1,
        'dir' => 'next',
        'post_id' => $post->ID,
        'post_type' => $post_type,
        $mncf->relationship->items_per_page_option_name => $mncf->relationship->get_items_per_page( $post->post_type, $post_type ),
        '_mnnonce' => mn_create_nonce( 'pr_pagination' ) . $add,
    );
    $url = admin_url('admin-ajax.php');


    if ( $prev ) {
        $url_params['page'] = $page - 1;
        $url_params['dir'] = 'prev';
        $link .= sprintf(
            '<a class="button-secondary mncf-pr-pagination-link mncf-pr-prev" href="%s" data-pagination-name="%s">',
            esc_url( add_query_arg( $url_params, $url) ),
            esc_attr($mncf->relationship->items_per_page_option_name)
        );
        $link .= __( 'Prev', 'mncf' ) . '</a>&nbsp;&nbsp;';
    }
    if ( $per_page < $count ) {
        $total_pages = ceil( $count / $per_page );
        $link .= sprintf(
            '<select class="mncf-pr-pagination-select" name="mncf-pr-pagination-select" data-pagination-name="%s">',
            esc_attr($mncf->relationship->items_per_page_option_name)
        );
        for ( $index = 1; $index <= $total_pages; $index++ ) {
            $link .= '<option';
            if ( ($index) == $page ) {
                $link .= ' selected="selected"';
            }
            $url_params['page'] = $index;

            $link .= sprintf( ' value="%s"', esc_url(add_query_arg( $url_params, $url)));
            $link .= '">' . $index . '</option>';
        }
        $link .= '</select>';
    }
    if ( $next ) {
        $url_params['page'] = $page + 1;
        $link .= sprintf(
            '<a class="button-secondary mncf-pr-pagination-link mncf-pr-next" href="%s" data-pagination-name="%s">',
            esc_url(add_query_arg( $url_params, $url)),
            esc_attr($mncf->relationship->items_per_page_option_name)
        );
        $link .= __( 'Next', 'mncf' ) . '</a>';
    }
    return !empty( $link ) ? '<div class="mncf-pagination-top">' . $link . '</div>' : '';
}

/**
 * Save post hook.
 *
 * @param type $parent_post_id
 * @return string
 */
function mncf_pr_admin_save_post_hook( $parent_post_id ) {

    global $mncf;
    /*
     * TODO https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/159760120/comments#225005357
     * Problematic This should be done once per save (on saving main post)
     * remove_action( 'save_post', 'mncf_pr_admin_save_post_hook', 11);
     */
    static $cached = array();
    /*
     *
     * TODO Monitor this
     */
    // Remove main hook?
    // CHECKPOINT We remove temporarily main hook
    if ( !isset( $cached[$parent_post_id] ) ) {
        if ( isset( $_POST['mncf_post_relationship'][$parent_post_id] ) ) {
            $mncf->relationship->save_children( $parent_post_id,
                    (array) $_POST['mncf_post_relationship'][$parent_post_id] );
        }
        // Save belongs if any
        if ( isset( $_POST['mncf_pr_belongs'][intval( $parent_post_id )] ) ) {
            mncf_pr_admin_update_belongs( intval( $parent_post_id ),
                    $_POST['mncf_pr_belongs'][intval( $parent_post_id )] );
        }

        // MNML
        mncf_mnml_relationship_save_post_hook( $parent_post_id );

	    /**
	     * Temporary workaround until https://core.trac.mtaandao.org/ticket/17817 is fixed.
	     *
	     * Saving child posts cancels all save_post actions for the parent post that would otherwise come
	     * after this one.
	     *
	     * @since 2.2
	     */
	    do_action( 'types_finished_saving_child_posts', $parent_post_id );

        $cached[$parent_post_id] = true;
    }

}

/**
 * Adds filtering regular evaluation (not mnv_conditional)
 *
 * @global type $mncf
 * @param type $posted
 * @param type $field
 * @return type
 */
function mncf_relationship_ajax_data_filter( $posted, $field ) {

    global $mncf;

    $value = $mncf->relationship->get_submitted_data(
        $mncf->relationship->parent->ID,
        $mncf->relationship->child->ID,
        $field
    );

    return is_null( $value ) ? $posted : $value;
}

/**
 * Checks if post type is parent
 * @param type $parent_post_type
 * @param type $child_post_type
 * @return type
 */
function mncf_relationship_is_parent( $parent_post_type, $child_post_type ) {
    $has = mncf_pr_admin_get_has( $parent_post_type );
    return isset( $has[$child_post_type] );
}

function mncf_pr_admin_mncf_relationship_check($keys_to_check = array())
{
    $keys_to_check = array_unique(array_merge($keys_to_check, array('nounce', 'post_id', 'post_type')));
    foreach( $keys_to_check as $key ) {
        if ( !isset($_REQUEST[$key] ) ) {
            die(__('Sorry, something went wrong. The requested can not be completed.', 'mncf'));
        }
    }
    $id = esc_attr(sprintf('mncf_pr_belongs_%d_%s', (int) $_REQUEST['post_id'], sanitize_text_field( $_REQUEST['post_type'] )));
    if ( !mn_verify_nonce($_REQUEST['nounce'], $id) ) {
        die(__('Sorry, something went wrong. The requested can not be completed.', 'mncf'));
    }
}

function mncf_pr_admin_mncf_relationship_search()
{
    mncf_pr_admin_mncf_relationship_check();
	
	global $mndb;
	$values_to_prepare			= array();
	
	$posts_per_page				= apply_filters( 'mncf_pr_belongs_post_numberposts', 10 );
	$post_type					= sanitize_text_field( $_REQUEST['post_type'] );
	$post_status				= apply_filters( 'mncf_pr_belongs_post_status', array( 'publish', 'private' ) );
	
	$mnml_join = $mnml_where	= "";
	$is_translated_post_type	= apply_filters( 'mnml_is_translated_post_type', false, $post_type );
	
	if ( $is_translated_post_type ) {
		$mnml_current_language	= apply_filters( 'mnml_current_language', '' );
		$mnml_join				= " JOIN {$mndb->prefix}icl_translations t ";
		$mnml_where				= " AND p.ID = t.element_id AND t.language_code = %s ";
		$values_to_prepare[]	= $mnml_current_language;
	}
	
	$values_to_prepare[]		= sanitize_text_field( $post_type );
	
	$search_where				= "";
	
	if ( 
		isset( $_REQUEST['s'] ) 
		&& $_REQUEST['s'] != ''
	) {
		$search_term = "";
		if ( method_exists( $mndb, 'esc_like' ) ) { 
			$search_term = '%' . $mndb->esc_like( $_REQUEST['s'] ) . '%'; 
		} else { 
			$search_term = '%' . like_escape( esc_sql( $_REQUEST['s'] ) ) . '%'; 
		}
		$search_where			= " AND p.post_title LIKE %s ";
		$values_to_prepare[]	= $search_term;
		$orderby				= ' ORDER BY p.post_title ';
	} else {
		$orderby				= ' ORDER BY p.post_date DESC ';
	}
	
	if ( 
		isset( $_REQUEST['page'] ) 
		&& preg_match( '/^\d+$/', $_REQUEST['page'] ) 
	) {
        $values_to_prepare[]	= ( (int) $_REQUEST['page'] - 1 ) * $posts_per_page;
    } else {
		$values_to_prepare[]	= 0;
	}
	$values_to_prepare[]		= $posts_per_page;
	
	$parents_available = $mndb->get_results(
		$mndb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS p.ID as id, p.post_title as text, p.post_type as type, p.post_status as status 
			FROM {$mndb->posts} p {$mnml_join} 
			WHERE p.post_status IN ('" . implode( "','" , $post_status ) . "') 
			{$mnml_where} 
			AND p.post_type = %s 
			{$search_where} 
			{$orderby}  
			LIMIT %d,%d",
			$values_to_prepare
		)
	);
	
	$parents_count = $mndb->get_var('SELECT FOUND_ROWS()');
	
	$results = array(
		'items'					=> $parents_available,
        'total_count'			=> $parents_count,
        'incomplete_results'	=> $parents_count > $posts_per_page,
        'posts_per_page'		=> $posts_per_page,
	);
	
    echo json_encode( $results );
    die;
}

// Deprecated since the introduction of select v.4
function mncf_pr_admin_mncf_relationship_entry()
{
    mncf_pr_admin_mncf_relationship_check(array('p'));
    $mncf_post = get_post( (int) $_REQUEST['p'], ARRAY_A);
    /**
     * remove unnecessary data and add some necessary
     */
    $mncf_post = array(
        'ID' => $mncf_post['ID'],
        'parent_id' => isset($_REQUEST['post_id'])? intval($_REQUEST['post_id']):0,
        'edit_link' => html_entity_decode(get_edit_post_link($mncf_post['ID'])),
        'post_title' => $mncf_post['post_title'],
        'post_type' => $mncf_post['post_type'],
        'save' => 'no-save',
    );
    echo json_encode($mncf_post);
    die;
}

// Deprecated since the introduction of select v.4
function mncf_pr_admin_mncf_relationship_delete()
{
    mncf_pr_admin_mncf_relationship_check();
    delete_post_meta( (int) $_REQUEST['post_id'], sprintf('_mncf_belongs_%s_id', sanitize_text_field( $_REQUEST['post_type'] )));
    echo json_encode(
        array(
            'target' => sprintf('#mncf_pr_belongs_%d_%s-wrapper', (int) $_REQUEST['post_id'], sanitize_text_field( $_REQUEST['post_type'] )),
        )
    );
    die;
}

// Deprecated since the introduction of select v.4
function mncf_pr_admin_mncf_relationship_save()
{
    mncf_pr_admin_mncf_relationship_check(array('p'));
    update_post_meta( (int) $_REQUEST['post_id'], sprintf('_mncf_belongs_%s_id', sanitize_text_field( $_REQUEST['post_type'] )), intval($_REQUEST['p']));
    die;
}

function mncf_pr_admin_mncf_relationship_update() {
	mncf_pr_admin_mncf_relationship_check();
	$post_id			= (int) $_REQUEST['post_id'];
	$parent_post_type	= sanitize_text_field( $_REQUEST['post_type'] );
	$data				= array();
	if (
		isset( $_REQUEST['p'] ) 
		&& (int) $_REQUEST['p'] > 0
	) {
		update_post_meta( $post_id, sprintf( '_mncf_belongs_%s_id', $parent_post_type ), (int) $_REQUEST['p'] );
		$data['edit_link'] = admin_url( 'post.php ');
	} else {
		delete_post_meta( $post_id, sprintf( '_mncf_belongs_%s_id', $parent_post_type ) );
	}
	mn_send_json_success( $data );
}

function mncf_pr_belongs_post_numberposts_minimum( $posts_per_page ) {
	if ( $posts_per_page < 6 ) {
		$posts_per_page = 7;
	}
	return $posts_per_page;
}

