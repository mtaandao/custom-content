<?php

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The MN_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary. In this tutorial, we are
 * going to use the MN_List_Table class directly from Mtaandao core.
 *
 * IMPORTANT:
 * Please note that the MN_List_Table class technically isn't an official API,
 * and it could change at some point in the distant future. Should that happen,
 * I will update this plugin with the most current techniques for your reference
 * immediately.
 *
 * If you are really worried about future compatibility, you can make a copy of
 * the MN_List_Table class (file path is shown just below) to use and distribute
 * with your plugins. If you do that, just remember to change the name of the
 * class to avoid conflicts with core.
 *
 * Since I will be keeping this tutorial up-to-date for the foreseeable future,
 * I am going to work with the copy of the class provided in Mtaandao core.
 */
if(!class_exists('MN_List_Table')){
    require_once( ABSPATH . 'admin/includes/class-mn-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 *
 * usermeta Table Class
 *
 * todo Oh dear god! Clean this up!
 */
class Types_Admin_Usermeta_Control_Table extends MN_List_Table
{
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct()
    {
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __('Usermeta Field', 'mncf'),     //singular name of the listed records
            'plural'    => __('Usermeta Fields', 'mncf'),    //plural name of the listed records
            'ajax'      => true        //does this table support ajax?
        ) );
    }

    /**
     * @global object $mndb
     */
    function prepare_items()
    {
        global $mndb;
        $per_page = $this->get_items_per_page('mncf_ufc_per_page', 10);

        // Get ours and enabled
        $cf_types = mncf_admin_fields_get_fields( true, true, false, 'mncf-usermeta' );
        $__groups = mncf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
        foreach ( $__groups as $__group_id => $__group ) {
            $__groups[$__group_id]['fields'] = mncf_admin_fields_get_fields_by_group( $__group['id'], 'slug', false, true, false, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta' );
        }

        foreach ( $cf_types as $cf_id => $cf ) {
            foreach ( $__groups as $__group ) {
                if ( isset( $__group['fields'][$cf_id] ) ) {
                    $cf_types[$cf_id]['groups'][$__group['id']] = $__group['name'];
                }
            }
            $cf_types[$cf_id]['groups_txt'] = empty( $cf_types[$cf_id]['groups'] ) ? __( 'None', 'mncf' ) : implode(', ', $cf_types[$cf_id]['groups'] );
        }

        $meta_key_not_like = array(
            '\_%',
            'closedpostboxes%',
            'meta-box-order%',
            'metaboxhidden%',
        );
        $having = '';
        foreach( $meta_key_not_like as $one ) {
            if ( $having ) {
                $having .= ' AND ';
            }
            $having .= sprintf( 'meta_key NOT LIKE \'%s\'', $one);
        }
        if ( $having ) {
            $having = ' HAVING '.$having;
        }
        $query = sprintf(
            'SELECT umeta_id, meta_key FROM %s GROUP BY meta_key %s ORDER BY meta_key',
            $mndb->usermeta,
            $having
        );

        // Get others (cache this result?)
        $cf_other = $mndb->get_results($query);

        $output = '';

        // Clean from ours
        foreach ($cf_other as $type_id => $type_data) {
            if (strpos($type_data->meta_key, MNCF_META_PREFIX) !== false) {
                $field_temp = mncf_admin_fields_get_field(str_replace(MNCF_META_PREFIX,
                                '', $type_data->meta_key), false, false, 'mncf-usermeta');
                if (!empty($field_temp)) {
                    if (!empty($field_temp['data']['disabled'])) {
                        $cf_types[$field_temp['id']] = array(
                            'id' => $field_temp['id'],
                            'slug' => $type_data->meta_key,
                            'name' => $type_data->meta_key,
                            'type' => 0,
                            'groups_txt' => __('None', 'mncf'),
                        );
                    } else {
                        unset($cf_other[$type_id]);
                    }
                } else if (mncf_types_cf_under_control('check_exists',
                                $type_data->meta_key, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta')) {
                    unset($cf_other[$type_id]);
                } else {
                    $cf_types[$type_data->meta_key] = array(
                        'id' => $type_data->meta_key,
                        'slug' => $type_data->meta_key,
                        'name' => $type_data->meta_key,
                        'type' => 0,
                        'groups_txt' => __('None', 'mncf'),
                    );
                }
            } else {
                if (mncf_types_cf_under_control('check_exists',
                                $type_data->meta_key, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta')) {
                    unset($cf_other[$type_id]);
                } else {
                    $cf_types[$type_data->meta_key] = array(
                        'id' => $type_data->meta_key,
                        'slug' => $type_data->meta_key,
                        'name' => $type_data->meta_key,
                        'type' => 0,
                        'groups_txt' => __('None', 'mncf'),
                    );
                }
            }
        }

        // Set some values
        foreach ($cf_types as $cf_id_temp => $cf_temp) {
            if (empty($cf_temp['type']) || !empty($cf_temp['data']['controlled'])) {
                $cf_types[$cf_id_temp]['slug'] = $cf_temp['name'];
            } else {
                $cf_types[$cf_id_temp]['slug'] = mncf_types_get_meta_prefix($cf_temp) . $cf_temp['slug'];
            }
        }

        // Order
        $orderby = isset($_REQUEST['orderby']) && !empty($_REQUEST['orderby'])? sanitize_text_field( $_REQUEST['orderby'] ):'c';
        $order = isset($_REQUEST['order']) && !empty($_REQUEST['order'])? sanitize_text_field( $_REQUEST['order'] ):'asc';
        $sort_matches = array(
            'c' => 'name',
            'g' => 'groups_txt',
            't' => 'slug',
            'f' => 'type'
        );
        $sorted_keys = array();
        $new_array = array();
        foreach ($cf_types as $cf_id_temp => $cf_temp) {
            if ( isset($sort_matches[$orderby] ) ) {
                $sorted_keys[$cf_temp['id']] = strtolower( $cf_temp[$sort_matches[$orderby]] );
            } else {
                $sorted_keys[$cf_temp['id']] = strtolower( $cf_temp[$sort_matches['c']] );
            }
        }
        asort($sorted_keys, SORT_STRING);
        if ('desc' == $order) {
            $sorted_keys = array_reverse($sorted_keys, true);
        }
        foreach ($sorted_keys as $cf_id_temp => $groups_txt) {
            $new_array[$cf_id_temp] = $cf_types[$cf_id_temp];
        }
        $cf_types = $new_array;

        // Search
        if (!empty($_REQUEST['s'])) {
            $search_results = array();
            foreach ($cf_types as $search_id => $search_field) {
                if (strpos(strval($search_field['name']), strval(trim(stripslashes($_REQUEST['s'])))) !== false) {
                    $search_results[$search_id] = $cf_types[$search_id];
                }
            }
            $cf_types = $search_results;
        }

        $total_items = count($cf_types);

        if ($total_items < $per_page) {
            $per_page = $total_items;
        }
        if ($this->get_pagenum() == 1) {
            $offset = 0;
        } else {
            $offset = ($this->get_pagenum() - 1) * $per_page;
        }
        // Display required number of entries on page
        $this->items = array_slice($cf_types, $offset, $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
        ));

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
    }

    function has_items() {
        return !empty($this->items);
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'cf_name' => __('User Field Name', 'mncf'),
            'group' => __('User Field Group', 'mncf'),
            'types_name' => __('Types Name', 'mncf'),
            'field_type' => __('Type', 'mncf'),
        );
        return $columns;
    }

    function get_sortable_columns() {
	    return array(
            'cf_name' => 'cf_name',
            'group' => 'group',
            'types_name' => 'types_name',
            'field_type' => 'field_type',
        );
    }

    function column_cb($item) {
        if (!mncf_types_cf_under_control('check_exists', $item['id'], TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta')) {
            $item['id'] = $item['id'] . '_' . md5('mncf_not_controlled');
        }
        return '<input type="checkbox" name="fields[]" value="' . $item['id'] . '" />';
    }

    function column_cf_name($item) {
        return stripcslashes($item['name']);
    }

    function column_group($item) {
        return empty( $item['groups'] ) ? __( 'None', 'mncf' ) : implode(', ', $item['groups'] );
    }

    function column_types_name($item) {
        return $item['slug'];
    }

    function column_field_type($item) {
        if (empty($item['type'])) {
            return __('Not under Types control', 'mncf');
        }
        $add = '';
        if (!empty($item['data']['disabled'])) {
            $add = '&nbsp;<span style="color:red;">(' . __("disabled", 'mncf') . ')</span>';
        }
        if (!empty($item['data']['disabled_by_type'])) {
            $add = '<br /><span style="color:red;">(' . __("This field was disabled during conversion. You need to set some further settings in the group editor.", 'mncf') . ')</span>';
            if (isset($item['groups']) && sizeof($item['groups'])) {
                $add .= ' <a href="' . admin_url('admin.php?page=mncf-edit-usermeta&group_id='
                    . key( $item['groups'] ) ) . '">' . __('Edit', 'mncf') . '</a>';
            }
        }
        return $item['type'] . $add;
    }

    public function get_bulk_actions()
    {
        return array(
            'mncf-add-to-group-bulk' => __('Add to Field Groups', 'mncf'),
            'mncf-remove-from-group-bulk' => __('Remove from Field Groups', 'mncf'),
            'mncf-change-type-bulk' => __('Change type', 'mncf'),
            'mncf-activate-bulk' => __('Start controlling with Types', 'mncf'),
            'mncf-deactivate-bulk' => __('Stop controlling with Types', 'mncf'),
            'mncf-delete-bulk' => __('Delete', 'mncf'),
        );
    }

}

