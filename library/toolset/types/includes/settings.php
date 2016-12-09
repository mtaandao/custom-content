<?php
/*
 * Settings form
 */

/**
 * Settings form.
 *
 * @return string
 *
 * @deprecated to remove
 */
function mncf_admin_general_settings_form()
{
    $settings = mncf_get_settings();

    $form = array();
    $form['#form']['callback'] = 'mncf_admin_general_settings_form_submit';

    $form['open-table'] = array(
        '#type' => 'markup',
        '#markup' => '<table class="form-table"><tbody>',
    );

    $form['images'] = array(
        '#id' => 'add_resized_images_to_library',
        '#name' => 'mncf_settings[add_resized_images_to_library]',
        '#title' => __('Images', 'mncf'),
        '#type' => 'checkbox',
        '#label' => __('Add resized images to the media library', 'mncf'),
        '#description' => __('Types will automatically add the resized images as attachments to the media library.', 'mncf'),
        '#inline' => true,
        '#default_value' => !empty($settings['add_resized_images_to_library']),
        '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote'] = array(
    '#title' => __('Remote Images', 'mncf'),
        '#id' => 'images_remote',
        '#name' => 'mncf_settings[images_remote]',
        '#type' => 'checkbox',
        '#label' => __('Allow resizing of remote images', 'mncf'),
        '#description' => __('Types will try to scale remote images.', 'mncf'),
        '#inline' => true,
        '#default_value' => !empty($settings['images_remote']),
        '#pattern' => '<br /><ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote_clear'] = array(
        '#id' => 'images_remote_cache_time',
        '#name' => 'mncf_settings[images_remote_cache_time]',
        '#type' => 'select',
        '#title' => __('Images cache', 'mncf'),
        '#pattern' => sprintf(
            '<br />%s',
            __('Invalidate cached images that are more than <ELEMENT> hours old.', 'mncf')
        ),
        '#options' => array(
            __('Never', 'mncf') => '0',
            '24' => '24',
            '36' => '36',
            '48' => '48',
            '72' => '72',
        ),
        '#inline' => true,
        '#default_value' => intval($settings['images_remote_cache_time']),
    );
    $form['clear_images_cache'] = array(
        '#type' => 'submit',
        '#name' => 'clear-cache-images',
        '#id' => 'clear-cache-images',
        '#attributes' => array('id' => 'clear-cache-images','class' => 'button-secondary'),
        '#value' => __('Clear Cached Images', 'mncf'),
        '#inline' => true,
        '#pattern' => '<br /><ELEMENT>',
    );
    $form['clear_images_cache_outdated'] = array(
        '#id' => 'clear-cache-images-outdated',
        '#type' => 'submit',
        '#name' => 'clear-cache-images-outdated',
        '#attributes' => array('id' => 'clear-cache-images-outdated','class' => 'button-secondary'),
        '#value' => __('Clear Outdated Cached Images', 'mncf'),
        '#inline' => true,
         '#pattern' => ' <ELEMENT></td></tr>',
    );


    if (function_exists('icl_register_string')) {
        $form['register_translations_on_import'] = array(
            '#id' => 'register_translations_on_import',
            '#name' => 'mncf_settings[register_translations_on_import]',
            '#type' => 'checkbox',
            '#title' => __('MNML Integration', 'mncf'),
            '#label' => __("When importing, add texts to MNML's String Translation table", 'mncf'),
            '#inline' => true,
            '#default_value' => !empty($settings['register_translations_on_import']),
            '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><LABEL><DESCRIPTION></td></th>',
            '#inline' => true,
        );
    }

    $form['help-box'] = array(
        '#id' => 'help_box',
        '#name' => 'mncf_settings[help_box]',
        '#type' => 'radios',
        '#options' => array(
            'all' => array(
                '#value' => 'all',
                '#title' => __("Show promotional messages on all custom post editing screens and on all Types create/edit pages", 'mncf')
            ),
            'by_types' => array(
                '#value' => 'by_types',
                '#title' => __("Show promotional messages only on post types that were created by Types and on all Types create/edit pages", 'mncf')
            ),
            'no' => array(
                '#value' => 'no',
                '#title' => __("Don't show promotional messages anywhere", 'mncf')
            ),
        ),
        '#inline' => true,
        '#default_value' => $settings['help_box'],
        '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><DESCRIPTION></td></th>',
        '#title' =>  __('Help Box', 'mncf'),
    );

    $form['hide_standard_custom_fields_metabox'] = array(
        '#id' => 'hide_standard_custom_fields_metabox',
        '#name' => 'mncf_settings[hide_standard_custom_fields_metabox]',
        '#type' => 'radios',
        '#options' => array(
            'all' => array(
                '#value' => 'show',
                '#title' => __('Show standard Mtaandao Custom Field Metabox', 'mncf')
            ),
            'by_types' => array(
                '#value' => 'hide',
                '#title' => __('Hide standard Mtaandao Custom Field Metabox', 'mncf')
            ),
        ),
        '#inline' => true,
        '#default_value' => preg_match('/^(show|hide)$/', $settings['hide_standard_custom_fields_metabox'])? $settings['hide_standard_custom_fields_metabox']:'show',
        '#title' => __('Custom Field Metabox', 'mncf'),
        '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><DESCRIPTION></td></th>',
    );

    /* This is obsolete with our new way to handle these messages (see types-508)
    if ( !MNCF_Types_Marketing_Messages::check_register() ) {
        $form['toolset_messages'] = array(
            '#id' => 'toolset_messages',
            '#name' => 'mncf_settings[toolset_messages]',
            '#type' => 'checkbox',
            '#label' => __('Disable all messages about other Custom Content components', 'mncf'),
            '#default_value' => isset($settings['toolset_messages'])? intval($settings['toolset_messages']):0,
            '#title' =>  __('Custom Content Messages', 'mncf'),
            '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><LABEL><DESCRIPTION></td></th>',
            '#inline' => true,
        );
    }
    */

    $form['postmeta-unfiltered-html'] = array(
        '#id' => 'postmeta_unfiltered_html',
        '#name' => 'mncf_settings[postmeta_unfiltered_html]',
        '#type' => 'radios',
        '#title' => __('Custom fields - unfiltered HTML', 'mncf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __('Allow saving unfiltered HTML in Types custom fields for users with higher roles', 'mncf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __('Disallow saving unfiltered HTML in Types custom fields for all users', 'mncf'),
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['postmeta_unfiltered_html'],
        '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><DESCRIPTION></td></th>',
    );
    $form['usermeta-unfiltered-html'] = array(
        '#id' => 'usermeta_unfiltered_html',
        '#name' => 'mncf_settings[usermeta_unfiltered_html]',
        '#type' => 'radios',
        '#title' => __('Usermeta fields - unfiltered HTML', 'mncf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __("Allow saving unfiltered HTML in Types usermeta fields for users with higher roles", 'mncf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __("Disallow saving unfiltered HTML in Types usermeta fields for all users", 'mncf')
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['usermeta_unfiltered_html'],
        '#pattern' => '<tr><th scope="row"><TITLE></th><td><ELEMENT><DESCRIPTION></td></th>',
    );

    $form['open-close'] = array(
        '#type' => 'markup',
        '#markup' => '</tbody></table>',
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'submit',
        '#attributes' => array('id'=>'general-settings-submit','class' => 'button-primary'),
        '#value' => __('Save Changes', 'mncf'),
    );
    return $form;
}

/**
 * Saves settings.
 *
 * @param type $form
 */
function mncf_admin_general_settings_form_submit($form)
{
    if (isset($_POST['clear-cache-images']) || isset($_POST['clear-cache-images-outdated'])) {
        require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields/image.php';
        $cache_dir = mncf_fields_image_get_cache_directory(true);
        if (is_mn_error($cache_dir)) {
            mncf_admin_message_store($cache_dir->get_error_message());
        } else {
            if (isset($_POST['clear-cache-images'])) {
                mncf_fields_image_clear_cache($cache_dir, 'all');
            } else {
                mncf_fields_image_clear_cache($cache_dir);
            }
            mncf_admin_message_store(__('Images cache cleared', 'mncf'));
        }
        return true;
    }
    $settings = mncf_get_settings();
    $data = $_POST['mncf_settings'];

    $keys = array(
        'add_resized_images_to_library' => 'esc_html',
        'help_box' => 'esc_html',
        'hide_standard_custom_fields_metabox' => 'esc_html',
        'images_remote' => 'intval',
        'images_remote_cache_time' => 'intval',
        'register_translations_on_import' => 'esc_html',
        'toolset_messages' => 'intval',
        'postmeta_unfiltered_html' => 'on-off',
        'usermeta_unfiltered_html' => 'on-off',
    );

    foreach ( $keys as $key => $validation) {
        if (!isset($data[$key])) {
            $settings[$key] = 0;
        } else {
            switch($validation) {
            case 'intval':
                $settings[$key] = intval($data[$key]);
                break;
            case 'on-off':
                if ( preg_match( '/^(on|off)$/', $data[$key])) {
                    $settings[$key] = $data[$key];
                } else {
                    $settings[$key] = 'off';
                }
                break;

            case 'esc_html':
            default:
                $settings[$key] = esc_html($data[$key]);
                break;
            }
        }
    }

    /**
     * validate hide_standard_custom_fields_metabox
     */
    if ( !preg_match('/^(show|hide)$/', $settings['hide_standard_custom_fields_metabox']) ) {
        $settings['hide_standard_custom_fields_metabox'] = 'show';
    }

    /**
     * update_option
     */
    update_option('mncf_settings', $settings);

    mncf_admin_message_store(__('Settings saved.', 'mncf'));
}
