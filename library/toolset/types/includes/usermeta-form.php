<?php
/*
 * Fields and groups form functions.
 *
 *
 */
require_once MNCF_EMBEDDED_ABSPATH . '/classes/validate.php';
require_once MNCF_ABSPATH . '/includes/conditional-display.php';

global $mn_version;

/**
 * Saves user fields and groups.
 *
 * If field name is changed in specific group - new one will be created,
 * otherwise old one will be updated and will appear in that way in other grups.
 *
 * @return type
 */
function mncf_admin_save_usermeta_groups_submit($form)
{
    if (
           !isset($_POST['mncf'])
        || !isset($_POST['mncf']['group'])
        || !isset($_POST['mncf']['group']['name'])
    ) {
        return false;
    }

    $_POST['mncf']['group'] = apply_filters('mncf_group_pre_save', $_POST['mncf']['group']);

    $group_name = mn_kses_post($_POST['mncf']['group']['name']);

    if ( empty($group_name) ) {
        $form->triggerError();
        mncf_admin_message( __( 'Group name can not be empty.', 'mncf' ), 'error');
        return $form;
    }

    $new_group = false;

    $group_slug = sanitize_title($group_name);

    // Basic check


    if (isset($_REQUEST['group_id'])) {
        // Check if group exists
        $post = get_post(intval($_REQUEST['group_id']));
        // Name changed
        if (strtolower($group_name) != strtolower($post->post_title)) {
            // Check if already exists
            $exists = get_page_by_title($group_name, 'OBJECT', TYPES_USER_META_FIELD_GROUP_CPT_NAME);
            if (!empty($exists)) {
                $form->triggerError();
                mncf_admin_message(
                    sprintf(
                        __("A group by name <em>%s</em> already exists. Please use a different name and save again.", 'mncf'),
                        apply_filters('the_title', $exists->post_title)
                    ),
                    'error'
                );
                return $form;
            }
        }
        if (empty($post) || $post->post_type != TYPES_USER_META_FIELD_GROUP_CPT_NAME) {
            $form->triggerError();
            mncf_admin_message(sprintf(__("Wrong group ID %d", 'mncf'), intval($_REQUEST['group_id'])), 'error');
            return $form;
        }
        $group_id = $post->ID;

    } else {
        $new_group = true;
        // Check if already exists
        $exists = get_page_by_title($group_name, 'OBJECT', TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        if (!empty($exists)) {
            $form->triggerError();
            mncf_admin_message(
                sprintf(
                    __("A group by name <em>%s</em> already exists. Please use a different name and save again.", 'mncf'),
                    apply_filters('the_title', $exists->post_title)
                ),
                'error'
            );
            return $form;
        }
    }

    // Save fields for future use
    $fields = array();
    if (!empty($_POST['mncf']['fields'])) {
        // Before anything - search unallowed characters
        foreach ($_POST['mncf']['fields'] as $key => $field) {
            if (empty( $field['slug'] ) && !empty($field['name']) && preg_match( '#[^a-zA-Z0-9\s\_\-]#', $field['name'])) {
                $field['slug'] = sanitize_title($field['name']);
            }
            if ( empty($field['slug'] ) ) {
                $form->triggerError();
                mncf_admin_message( sprintf( __( 'Field slugs cannot be empty. Please edit this field name %s and save again.', 'mncf' ), $field['name'] ), 'error' );
                return $form;
            }
            if ((empty($field['slug']) && preg_match('#[^a-zA-Z0-9\s\_\-]#', $field['name']))
                    || (!empty($field['slug']) && preg_match('#[^a-zA-Z0-9\s\_\-]#',
                            $field['slug']))) {
                $form->triggerError();
                mncf_admin_message(sprintf(__('Field slugs cannot contain non-English characters. Please edit this field name %s and save again.', 'mncf'), $field['name']), 'error');
                return $form;
            }
        }

        foreach ($_POST['mncf']['fields'] as $key => $field) {
            $field = apply_filters('mncf_field_pre_save', $field);
            if (!empty($field['is_new'])) {
                // Check name and slug
                if (mncf_types_cf_under_control('check_exists',
                                sanitize_title($field['name']), TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta')) {
                    $form->triggerError();
                    mncf_admin_message(sprintf(__('Field with name "%s" already exists',
                                            'mncf'), $field['name']), 'error');
                    return $form;
                }
                if (isset($field['slug']) && mncf_types_cf_under_control('check_exists',
                                sanitize_title($field['slug']), TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta')) {
                    $form->triggerError();
                    mncf_admin_message(sprintf(__('Field with slug "%s" already exists',
                                            'mncf'), $field['slug']), 'error');
                    return $form;
                }
            }
            // Field ID and slug are same thing
            $field_id = mncf_admin_fields_save_field( $field, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta' );
            if (!empty($field_id)) {
                $fields[] = $field_id;
            }

        }
    }

    // Save group
    $roles = isset($_POST['mncf']['group']['supports']) ? $_POST['mncf']['group']['supports'] : array();
    /**
     * Admin styles
     */
    if ( isset( $_POST['mncf']['group']['admin_styles'] ) ) {
        $admin_style = esc_html($_POST['mncf']['group']['admin_styles']);
    }
    // Rename if needed
    if (isset($_REQUEST['group_id'])) {
        $_POST['mncf']['group']['id'] = intval($_REQUEST['group_id']);
    }

    $group_id = mncf_admin_fields_save_group($_POST['mncf']['group'], TYPES_USER_META_FIELD_GROUP_CPT_NAME);

    // Set open fieldsets
    if ($new_group && !empty($group_id)) {
        $open_fieldsets = get_user_meta(get_current_user_id(),
                'mncf-group-form-toggle', true);
        if (isset($open_fieldsets[-1])) {
            $open_fieldsets[$group_id] = $open_fieldsets[-1];
            unset($open_fieldsets[-1]);
            update_user_meta(get_current_user_id(), 'mncf-group-form-toggle',
                    $open_fieldsets);
        }
    }

    // Rest of processes
    if (!empty($group_id)) {
        mncf_admin_fields_save_group_fields($group_id, $fields, false, TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        mncf_admin_fields_save_group_showfor($group_id, $roles);
        /**
         * Admin styles
         */
        if (
            defined('TYPES_USE_STYLING_EDITOR')
            && TYPES_USE_STYLING_EDITOR
            && isset($admin_style)
        ) {
            mncf_admin_fields_save_group_admin_styles($group_id, $admin_style);
        }
        $_POST['mncf']['group']['fields'] = isset($_POST['mncf']['fields']) ? $_POST['mncf']['fields'] : array();
        do_action('mncf_save_group', $_POST['mncf']['group']);
        mncf_admin_message_store(
            apply_filters(
                'types_message_usermeta_saved',
                __('Group saved', 'mncf'),
                $group_name,
                $new_group ? false : true
            ),
            'custom'
        );
        mn_safe_redirect(
            admin_url(sprintf('admin.php?page=mncf-edit-usermeta&group_id=%d', $group_id))
        );
        exit;
    } else {
        mncf_admin_message_store(__('Error saving group', 'mncf'), 'error');
    }
}


/**
 * Generates form data.
 */
function mncf_admin_usermeta_form()
{
    /**
     * include common functions
     */
    include_once dirname(__FILE__).'/common-functions.php';

    global $mncf;
    mncf_admin_add_js_settings('mncf_nonce_toggle_group',
            '\'' . mn_create_nonce('group_form_collapsed') . '\'');
    mncf_admin_add_js_settings('mncf_nonce_toggle_fieldset',
            '\'' . mn_create_nonce('form_fieldset_toggle') . '\'');
    $default = array();

    $current_user_can_edit = MNCF_Roles::user_can_create('user-meta-field');

    // If it's update, get data
    $update = false;
    if (isset($_REQUEST['group_id'])) {
        $update = mncf_admin_fields_get_group(intval($_REQUEST['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        $current_user_can_edit = MNCF_Roles::user_can_edit('user-meta-field', $update);
        if (empty($update)) {
            $update = false;
            mncf_admin_message(sprintf(__("Group with ID %d do not exist", 'mncf'), intval($_REQUEST['group_id'])));
        } else {
            $update['fields'] = mncf_admin_fields_get_fields_by_group( sanitize_text_field( $_REQUEST['group_id'] ), 'slug', false, true, false, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta');
            $update['show_for'] = mncf_admin_get_groups_showfor_by_group( sanitize_text_field( $_REQUEST['group_id'] ) );
            $update['admin_styles'] = mncf_admin_get_groups_admin_styles_by_group( sanitize_text_field( $_REQUEST['group_id'] ) );
        }
    }

    $form = array();
    $form['#form']['callback'] = array('mncf_admin_save_usermeta_groups_submit');

    $form['form-open'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<div id="poststuff" class="%s">',
            $current_user_can_edit? '':'mncf-types-read-only'
        ),
    );

    // Form sidebars

    if ( $current_user_can_edit ) {
        $form['open-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="mncf-form-fields-align-right">',
        );
        // Set help icon
        $form['help-icon'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
	            '<div class="mncf-admin-fields-help"><img src="%s" style="position:relative;top:2px;" />&nbsp;
					<a href="%s" target="_blank">%s</a>
				</div>',
	            MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/res/images/question.png',
                Types_Helper_Url::get_url( 'using-post-fields' ),
	            __( 'Usermeta help', 'mncf' )
            ),
        );
        $form['submit2'] = array(
            '#type' => 'submit',
            '#name' => 'save',
            '#value' => __('Save', 'mncf'),
            '#attributes' => array('class' => 'button-primary mncf-disabled-on-submit'),
        );
        $form['fields'] = array(
            '#type' => 'fieldset',
            '#title' => __('Available fields', 'mncf'),
        );

        // Get field types
        $fields_registered = mncf_admin_fields_get_available_types();
        foreach ($fields_registered as $filename => $data) {
            $form['fields'][basename($filename, '.php')] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url('admin-ajax.php'
                . '?action=mncf_ajax&amp;mncf_action=fields_insert'
                . '&amp;field=' . basename($filename, '.php')
                . '&amp;page=mncf-edit-usermeta' )
                . '&amp;_mnnonce=' . mn_create_nonce('fields_insert') . '" '
                . 'class="mncf-fields-add-ajax-link button-secondary">' . $data['title'] . '</a> ',
            );
            // Process JS
            if (!empty($data['group_form_js'])) {
                foreach ($data['group_form_js'] as $handle => $script) {
                    if (isset($script['inline'])) {
                        add_action('admin_footer', $script['inline']);
                        continue;
                    }
                    $deps = !empty($script['deps']) ? $script['deps'] : array();
                    $in_footer = !empty($script['in_footer']) ? $script['in_footer'] : false;
                    mn_register_script($handle, $script['src'], $deps, MNCF_VERSION,
                        $in_footer);
                    mn_enqueue_script($handle);
                }
            }

            // Process CSS
            if (!empty($data['group_form_css'])) {
                foreach ($data['group_form_css'] as $handle => $script) {
                    if (isset($script['src'])) {
                        $deps = !empty($script['deps']) ? $script['deps'] : array();
                        mn_enqueue_style($handle, $script['src'], $deps,
                            MNCF_VERSION);
                    } else if (isset($script['inline'])) {
                        add_action('admin_head', $script['inline']);
                    }
                }
            }
        }


        // Get fields created by user
        $fields = mncf_admin_fields_get_fields( true, true, false, 'mncf-usermeta' );
        if ( !empty( $fields ) ) {
            $form['fields-existing'] = array(
                '#type' => 'fieldset',
                '#title' => __( 'User created fields', 'mncf' ),
                '#id' => 'mncf-form-groups-user-fields',
            );
            foreach ( $fields as $key => $field ) {
                if ( isset( $update['fields'] ) && array_key_exists( $key,
                    $update['fields'] ) ) {
                        continue;
                    }
                if ( !empty( $field['data']['removed_from_history'] ) ) {
                    continue;
                }
                $form['fields-existing'][$key] = array(
                    '#type' => 'markup',
                    '#markup' => '<div id="mncf-user-created-fields-wrapper-' . $field['id'] . '" style="float:left; margin-right: 10px;"><a href="' . admin_url( 'admin-ajax.php'
                    . '?action=mncf_ajax'
                    . '&amp;page=mncf-edit'
                    . '&amp;mncf_action=usermeta_insert_existing'
                    . '&amp;field=' . $field['id'] ) . '&amp;_mnnonce='
                    . mn_create_nonce( 'usermeta_insert_existing' ) . '" '
                    . 'class="mncf-fields-add-ajax-link button-secondary" onclick="jQuery(this).parent().fadeOut();" '
                    . 'data-slug="' . $field['id'] . '">'
                    . htmlspecialchars( stripslashes( $field['name'] ) ) . '</a>'
                    . '<a href="' . admin_url( 'admin-ajax.php'
                    . '?action=mncf_ajax'
                    . '&amp;mncf_action=remove_from_history2'
                    . '&amp;field_id=' . $field['id'] ) . '&amp;_mnnonce='
                    . mn_create_nonce( 'remove_from_history2' ) . '&amp;mncf_warning='
                    . sprintf( __( 'Are you sure that you want to remove field %s from history?', 'mncf' ),
                    htmlspecialchars( stripslashes( $field['name'] ) ) )
                    . '&amp;mncf_ajax_update=mncf-user-created-fields-wrapper-'
                    . $field['id'] . '" title="'
                    . sprintf( __( 'Remove field %s', 'mncf' ),
                        htmlspecialchars( stripslashes( $field['name'] ) ) )
                        . '" class="mncf-ajax-link"><img src="'
                        . MNCF_RES_RELPATH
                        . '/images/delete-2.png" style="postion:absolute;margin-top:5px;margin-left:-4px;" /></a></div>',
                    );
            }
        }
        $form['close-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );
    }

    // Group data

    $form['open-main'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="mncf-form-fields-main" class="mncf-form-fields-main">',
    );

    $form['title'] = array(
        '#type' => 'textfield',
        '#name' => 'mncf[group][name]',
        '#id' => 'mncf-group-name',
        '#value' => $update ? mn_kses_post($update['name']): '',
        '#inline' => true,
        '#attributes' => array(
            'style' => 'width:100%;margin-bottom:10px;',
            'placeholder' => __('Enter group title', 'mncf'),
        ),
        '#validate' => array(
            'required' => array(
                'value' => true,
            ),
        )
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#id' => 'mncf-group-description',
        '#name' => 'mncf[group][description]',
        '#value' => $update ? mn_kses_post($update['description']):'',
        '#attributes' => array(
            'placeholder' => __('Enter a description for this group', 'mncf'),
        ),
    );

    // Show Fields for
    global $mn_roles;
    $options = array();
    $users_currently_supported = array();
    $form_types = array();
    foreach ( $mn_roles->role_names as $role => $name   ) :
        $options[$role]['#name'] = 'mncf[group][supports][' . $role . ']';
        $options[$role]['#title'] = ucwords($role);
        $options[$role]['#default_value'] = ($update && !empty($update['show_for']) && in_array($role,
                        $update['show_for'])) ? 1 : 0;
        $options[$role]['#value'] = $role;
        $options[$role]['#inline'] = TRUE;
        $options[$role]['#suffix'] = '<br />';
        $options[$role]['#id'] = 'mncf-form-groups-show-for-' . $role;
        $options[$role]['#attributes'] = array('class' => 'mncf-form-groups-support-post-type');
        if ($update && !empty($update['show_for']) && in_array($role,
                        $update['show_for'])) {
            $users_currently_supported[] = ucwords($role);
        }
    endforeach;

    if (empty($users_currently_supported)) {
        $users_currently_supported[] = __('Displayed for all users roles', 'mncf');
    }

    /*
     * Show for FILTER
     */
    $temp = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'mncf[group][supports]',
        '#inline' => true,
    );
    /*
     *
     * Here we use unique function for all filters
     * Since Types 1.1.4
     */
    $form_users = _mncf_filter_wrap('custom_post_types',
            __('Show For:', 'mncf'),
            implode(', ', $users_currently_supported),
            __('Displayed for all users roles', 'mncf'), $temp);

    /*
     * Now starting form
     */
    $access_notification = '';
    if (function_exists('mncf_access_register_caps')){
        $access_notification = '<div class="message custom mncf-notif"><span class="mncf-notif-congrats">'
        . __('This groups visibility is also controlled by the Access plugin.', 'mncf')  .'</span></div>';
    }
    $form['supports-table-open'] = array(
        '#type' => 'markup',
        '#markup' => '<table class="widefat"><thead><tr><th>'
        . __('Where to display this group', 'mncf')
        . '</th></tr></thead><tbody><tr><td>'
        . '<p>'
        . __('Each usermeta group can display different fields for user roles.', 'mncf')
        . $access_notification
        . '</p>',
    );
    /*
     * Join filter forms
     */
    // User Roles
    $form['p_wrap_1_' . mncf_unique_id(serialize($form_users))] = array(
        '#type' => 'markup',
        '#markup' => '<p class="mncf-filter-wrap">',
    );
    $form = $form + $form_users;

    $form['supports-table-close'] = array(
        '#type' => 'markup',
        '#markup' => '</td></tr></tbody></table><br />',
    );




    /** Admin styles**/
    if (
        defined('TYPES_USE_STYLING_EDITOR')
        && TYPES_USE_STYLING_EDITOR
        && $current_user_can_edit
    ) {
        $form['adminstyles-table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="widefat" id="mncf-admin-styles-box"><thead><tr><th>'
            . __('Styling Editor', 'mncf')
            . '</th></tr></thead><tbody><tr><td>'
            . '<p>'
            . __('Customize Fields for admin panel.', 'mncf')
                . '</p>',
            );

        $admin_styles_value = $preview_profile = $edit_profile = '';
        if ( isset ($update['admin_styles']) ){
            $admin_styles_value = $update['admin_styles'];
        }
        $temp = '';
        if ($update){
            require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
            require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
            require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';

            $user_id = mncf_usermeta_get_user();
            $preview_profile = mncf_usermeta_preview_profile( $user_id, $update, 1 );

            $group = $update;
            $group['fields'] = mncf_admin_usermeta_process_fields( $user_id, $group['fields'], true, false );
            $edit_profile = mncf_admin_render_fields($group, $user_id, 1);
            add_action( 'admin_enqueue_scripts', 'mncf_admin_fields_form_fix_styles', PHP_INT_MAX  );
        }
        $temp[] = array(
            '#type' => 'radio',
            '#suffix' => '<br />',
            '#value' => 'edit_mode',
            '#title' => 'Edit mode',
            '#name' => 'mncf[group][preview]','#default_value' => '',
            '#before' => '<div class="mncf-admin-css-preview-style-edit">',
            '#inline' => true,
            '#attributes' => array('onclick' => 'changePreviewHtml(\'editmode\')','checked'=>'checked')
        );

        $temp[] = array(
            '#type' => 'radio',
            '#title' => 'Read Only',
            '#name' => 'mncf[group][preview]','#default_value' => '',
            '#after' => '</div>',
            '#inline' => true,
            '#attributes' => array('onclick' => 'changePreviewHtml(\'readonly\')')
        );

        $temp[] = array(
            '#type' => 'textarea',
            '#name' => 'mncf[group][admin_html_preview]',
            '#inline' => true,
            '#value' => '',
            '#id' => 'mncf-form-groups-admin-html-preview',
            '#before' => '<h3>Field group HTML</h3>'
        );

        $temp[] = array(
            '#type' => 'textarea',
            '#name' => 'mncf[group][admin_styles]',
            '#inline' => true,
            '#value' => $admin_styles_value,
            '#default_value' => '',
            '#id' => 'mncf-form-groups-css-fields-editor',
            '#after' => '
            <div class="mncf-update-preview-btn"><input type="button" value="Update preview" onclick="mncfPreviewHtml()" style="float:right;" class="button-secondary"></div>
            <h3>'.__('Field group preview', 'mncf').'</h3>
            <div id="mncf-update-preview-div">Preview here</div>
            <script type="text/javascript">
var mncfReadOnly = ' .  json_encode( base64_encode( $preview_profile) ) . ';
var mncfEditMode = ' .  json_encode( base64_encode($edit_profile) ) . ';
var mncfDefaultCss = ' .  json_encode( base64_encode($admin_styles_value) ) . ';
        </script>
        ',
        '#before' => sprintf('<h3>%s</h3>', __('Your CSS', 'mncf')),
    );

    $admin_styles = _mncf_filter_wrap( 'admin_styles', __('Admin styles for fields:', 'mncf'), '', '', $temp, __( 'Open style editor', 'mncf' ) );
    $form['p_wrap_1_' . mncf_unique_id(serialize($admin_styles))] = array(
        '#type' => 'markup',
        '#markup' => '<p class="mncf-filter-wrap">',
    );
    $form = $form + $admin_styles;
    $form['adminstyles-table-close'] = array(
        '#type' => 'markup',
        '#markup' => '</td></tr></tbody></table><br />',
    );
    }
    /** End admin Styles **/


    // Group fields

    $form['fields_title'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __('Fields', 'mncf') . '</h2>',
    );
    $show_under_title = true;

    $form['ajax-response-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="mncf-fields-sortable" class="ui-sortable">',
    );

    // If it's update, display existing fields
    $existing_fields = array();
    if ($update && isset($update['fields'])) {
        foreach ($update['fields'] as $slug => $field) {
            $field['submitted_key'] = $slug;
            $field['group_id'] = $update['id'];
            $form_field = mncf_fields_get_field_form_data($field['type'], $field);
            if (is_array($form_field)) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
            $existing_fields[] = $slug;
            $show_under_title = false;
        }
    }
    // Any new fields submitted but failed? (Don't double it)
    if (!empty($_POST['mncf']['fields'])) {
        foreach ($_POST['mncf']['fields'] as $key => $field) {
            if (in_array($key, $existing_fields)) {
                continue;
            }
            $field['submitted_key'] = $key;
            $form_field = mncf_fields_get_field_form_data($field['type'], $field);
            if (is_array($form_field)) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
        }
        $show_under_title = false;
    }
    $form['ajax-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>' . '<div id="mncf-ajax-response"></div>',
    );

    if ($show_under_title) {
        $form['fields_title']['#markup'] = $form['fields_title']['#markup']
                . '<div id="mncf-fields-under-title">'
                . __('There are no fields in this group. To add a field, click on the field buttons at the right.', 'mncf')
                . '</div>';
    }

    // If update, create ID field
    if ($update) {
        $form['group_id'] = array(
            '#type' => 'hidden',
            '#name' => 'group_id',
            '#value' => $update['id'],
            '#forced_value' => true,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => __('Save', 'mncf'),
        '#attributes' => array('class' => 'button-primary mncf-disabled-on-submit'),
    );

    // Close main div
    $form['close-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );


    mncf_admin_add_js_settings( 'mncf_filters_association_or',
            '\'' . __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% or Content Template is: %vt%', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_filters_association_and',
            '\'' . __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% and Content Template is: %vt%', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_filters_association_all_pages',
            '\'' . __( 'all', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_filters_association_all_taxonomies',
            '\'' . __( 'any', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_filters_association_all_templates',
            '\'' . __( 'any', 'mncf' ) . '\'' );
    // Add JS settings
    mncf_admin_add_js_settings('mncfFormUniqueValuesCheckText',
            '\'' . __('Warning: same values selected', 'mncf') . '\'');
    mncf_admin_add_js_settings('mncfFormUniqueNamesCheckText',
            '\'' . __('Warning: field name already used', 'mncf') . '\'');
    mncf_admin_add_js_settings('mncfFormUniqueSlugsCheckText',
            '\'' . __('Warning: field slug already used', 'mncf') . '\'');

    /**
     * close form div
     */
    $form['form-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    /**
     * return form if current_user_can edit
     */
    if ( $current_user_can_edit) {
        return $form;
    }

    return mncf_admin_common_only_show($form);
}


/**
 * Dynamically adds existing field on AJAX call.
 *
 * @param type $form_data
 */
function mncf_usermeta_insert_existing_ajax() {
    $field = mncf_admin_fields_get_field( sanitize_text_field( $_GET['field'] ), false, true, false, 'mncf-usermeta');

    if ( !empty( $field ) ) {
        echo mncf_fields_get_field_form( $field['type'], $field );
    } else {
        echo '<div>' . __( "Requested field don't exist", 'mncf' ) . '</div>';
    }
}
