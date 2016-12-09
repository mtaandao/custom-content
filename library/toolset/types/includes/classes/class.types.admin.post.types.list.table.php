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
 * Create a new list table package that extends the core MN_List_Table class.
 * MN_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 *
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 *
 * Our theme for this list table is going to be movies.
 */
class Types_Admin_Post_Types_List_Table extends MN_List_Table
{
    var $custom_types;
    var $buildin_post_types;
    var $bulk_action_field_name = 'mncf_cpt_ids';

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct()
    {
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'Post Type',     //singular name of the listed records
            'plural'    => 'Post Types',    //plural name of the listed records
            'ajax'      => true        //does this table support ajax?
        ) );

        $this->custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
        $this->buildin_post_types = mncf_get_builtin_in_post_types();
    }

    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title()
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as
     * possible.
     *
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     *
     * For more detailed insight into how columns are handled, take a look at
     * MN_List_Table::single_row_columns()
     *
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name)
    {
        switch($column_name){
            case 'title':
            case 'description':
                return stripslashes($item[$column_name]);
            case 'taxonomies':
                $rows = array();
                if (!empty($item[$column_name])) {
                    foreach ($item[$column_name] as $taxonomy_slug => $taxonomy_name) {
                        $rows[] = stripslashes(mncf_translate($taxonomy_name . ' name', $taxonomy_name, 'Types-TAX'));
                    }
                }
                return empty($rows)? __('None', 'mncf'):implode(', ', $rows);
            case 'status':
                return 'active' == $item[$column_name]? __('Yes', 'mncf'):__('No', 'mncf');
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     *
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     *
     *
     * @see MN_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_title($item)
    {
        $edit_link = esc_url(
            add_query_arg(
                array(
                    'page' => 'mncf-view-type',
                    'mncf-post-type' => $item['slug'],
                ),
                admin_url('admin.php')
            )
        );
        if ( MNCF_Roles::user_can_edit('custom-post-type', $item ) ) {
            $edit_link = esc_url(
                add_query_arg(
                    array(
                        'page' => 'mncf-edit-type',
                        'mncf-post-type' => $item['slug'],
                    ),
                    admin_url('admin.php')
                )
            );
        }

        //Build row actions
        $actions = array();
        $actions['edit'] = sprintf('<a href="%s">%s</a>', $edit_link, __('Edit', 'mncf'));
        if ( 'cpt' == $item['type'] ) {
            $a = array(
                'status' => 'active' == $item['status'] ? mncf_admin_custom_types_get_ajax_deactivation_link($item['slug']):mncf_admin_custom_types_get_ajax_activation_link($item['slug']),
                'duplicate'     => sprintf(
                    '<a href="%s" class="submitduplicate mncf-ajax-link" id="mncf-list-duplicate-%s">%s</a>',
                    esc_url(
                        add_query_arg(
                            array(
                                'action' => 'mncf_ajax',
                                'mncf_action' => 'duplicate_post_type',
                                'mncf-post-type' => $item['slug'],
                                'mncf_ajax_update' => 'mncf_list_ajax_response_'.$item['slug'],
                                '_mnnonce' => mn_create_nonce('duplicate_post_type'),
                            ),
                            admin_url('admin-ajax.php')
                        )
                    ),
                    $item['slug'],
                    __('Duplicate', 'mncf')
                ),
                'delete'     => sprintf(
                    '<a href="%s" class="submitdelete mncf-ajax-link" id="mncf-list-delete-%s">%s</a>',
                    esc_url(
                        add_query_arg(
                            array(
                                'action' => 'mncf_ajax',
                                'mncf_action' => 'delete_post_type',
                                'mncf-post-type' => $item['slug'],
                                'mncf_ajax_update' => 'mncf_list_ajax_response_'.$item['slug'],
                                '_mnnonce' => mn_create_nonce('delete_post_type'),
                                'mncf_warning' => urlencode(__('Are you sure?', 'mncf')),
                            ),
                            admin_url('admin-ajax.php')
                        )
                    ),
                    $item['slug'],
                    __('Delete', 'mncf')
                ),
            );
            $actions += $a;
        } elseif ( !mncf_is_builtin_post_types( $item['slug'] ) ) {
            $actions = array(
                'view' => sprintf('<a href="%s">%s</a>', $edit_link, __('View', 'mncf')),
            );
        }

        //Return the title contents
        return sprintf(
            '<strong><a href="%s" class="row-title">%s</strong>%s',
            $edit_link,
            $item['title'],
            $this->row_actions($actions)
        );
    }

    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     *
     * @see MN_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item)
    {
        /**
         * do not show checkbox for built-in post types
         */
        if ( isset($item['_builtin']) && $item['_builtin'] ) {
            return '';
        }
        if ( MNCF_Roles::user_can_edit('custom-post-type', $item ) ) {
            return sprintf(
                '<input type="checkbox" name="%s[]" value="%s" />',
                $this->bulk_action_field_name,
                $item['slug']
            );
        }
        return '';
    }

    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     *
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see MN_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns()
    {
        $columns = array(
            'cb'          => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'       => __('Name', 'mncf'),
            'description' => __('Description', 'mncf'),
            'status'      => __('Active', 'mncf'),
            'taxonomies'  => __('Taxonomies', 'mncf'),
        );
        return $columns;
    }

    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
     * you will need to register it here. This should return an array where the
     * key is the column that needs to be sortable, and the value is db column to
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     *
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     *
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'title'       => array('title',true),     //true means it's already sorted
            'description' => array('description',false),
            'status'      => array('status',false)
        );
        return $sortable_columns;
    }

    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     *
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     *
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     *
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions()
    {
        $actions = array(
            'activate'   => __('Activate', 'mncf'),
            'deactivate' => __('Deactivate', 'mncf'),
            'delete'     => __('Delete', 'mncf'),
        );
        return $actions;
    }

    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     *
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action()
    {
        $action = $this->current_action();

        /**
         * check nounce
         */
        if (!empty($action)) {
            $nonce = '';
            if ( isset($_REQUEST['_mnnonce'] ) ) {
                $nonce = sanitize_text_field( $_REQUEST['_mnnonce'] );
            }
            if ( ! mn_verify_nonce( $nonce, 'bulk-posttypes' ) ) {
                die( 'Security check' );
            }
        }

        //Detect when a bulk action is being triggered...
        if (
            !empty($this->custom_types)
            && isset($_POST[$this->bulk_action_field_name])
            && !empty($_POST[$this->bulk_action_field_name])
        ) {
            $slugs_to_delete = array();
            foreach( $_POST[$this->bulk_action_field_name] as $key ) {
                /**
                 * do not process if there is no entry
                 */
                if ( !isset($this->custom_types[$key]) ) {
                    continue;
                }
                /**
                 * check capability
                 */
                if ( !MNCF_Roles::user_can_edit('custom-post-type', $this->custom_types[$key]) ){
                    continue;
                }
                /**
                 * do it!
                 */
                switch($action) {
                case 'delete':
                    unset($this->custom_types[$key]);
                    $slugs_to_delete[] = $key;
                    break;
                case 'deactivate':
                    $this->custom_types[$key]['disabled'] = 1;
                    $this->custom_types[$key][TOOLSET_EDIT_LAST] = time();
                    break;
                case 'activate':
                    if ( isset($this->custom_types[$key]['disabled']) ) {
                        unset($this->custom_types[$key]['disabled']);
                        $this->custom_types[$key][TOOLSET_EDIT_LAST] = time();
                    }
                    break;
                }
            }
            /**
             * update post types
             */
            update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $this->custom_types);
            /**
             * update custom taxonomies
             */
            if ( count($slugs_to_delete) ) {
                $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
                if ( !empty($custom_taxonomies) ) {
                    foreach ( $slugs_to_delete as $slug ) {
                        foreach ( $custom_taxonomies as $custom_key => $data ) {
                            if ( !isset($data['supports'] ) ) {
                                continue;
                            }
                            if ( isset($data['supports'][$slug]) ) {
                                unset($custom_taxonomies[$custom_key]['supports'][$slug]);
                            }
                        }
                    }
                    update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
                }
            }
        }
    }

    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     *
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items()
    {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('mncf_cpt_per_page', 10);;

        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);

        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();

        /**
         * map taxonomies to Post Types
         */
        $taxonomies = mncf_get_builtin_in_taxonomies('objects');
        $map_taxonomies_by_post_type = array();
        foreach( $taxonomies as $slug => $data ) {
            if ( isset($data->object_type) ) {
                foreach( $data->object_type as $post_type ) {
                    if ( !isset($map_taxonomies_by_post_type[$post_type]) ) {
                        $map_taxonomies_by_post_type[$post_type] = array();
                    }
                    $map_taxonomies_by_post_type[$post_type][$slug] = $data->labels->name;
                }
            }
        }
        $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        foreach( $custom_taxonomies as $slug => $data ) {
            if (
                true
                && isset($data['labels'])
                && isset($data['labels']['name'])
                && isset($data['supports'])
                && !empty($data['supports'])
            ) {
                foreach( array_keys($data['supports']) as $post_type) {
                    $map_taxonomies_by_post_type[$post_type][$slug] = $data['labels']['name'];
                }
            }
        }

        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * package slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
         * our data. In a real-world implementation, you will probably want to
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */

        $s = isset($_POST['s'])? mb_strtolower(trim($_POST['s'])):false;

        $data = array();
        if ( !empty($this->custom_types) ){
            foreach( array_values($this->custom_types) as $type ) {
                if (empty($type) || empty($type['slug'])) {
                    continue;
                }
                $one = array(
                    'description' => isset($type['description'])? $type['description']:'',
                    'taxonomies' => isset($map_taxonomies_by_post_type[$type['slug']])? $map_taxonomies_by_post_type[$type['slug']]:array(),
                    'slug' => $type['slug'],
                    'status' => isset($type['disabled'])? 'inactive':'active',
                    'title' => stripslashes($type['labels']['name']),
                    'type' => 'cpt',
                    '_builtin' => false,
                    MNCF_AUTHOR => isset($type[MNCF_AUTHOR])? intval($type[MNCF_AUTHOR]):0,
                );
                $add_one = true;
                if ( $s ) {
                    $add_one = false;
                    foreach( array('description', 'slug', 'title' ) as $key ) {
                        if ( $add_one || empty( $one[$key] ) ) {
                            continue;
                        }
                        if ( is_numeric(strpos(mb_strtolower($one[$key]), $s))) {
                            $add_one = true;
                        }
                    }
                }
                if ( $add_one ) {
                    $data[$one['slug']] = $one;
                }
            }
        }


        /**
         * built-in post types
         */
        foreach( $this->buildin_post_types as $type ) {
            $pt = get_post_type_object($type);
            $one = array(
                'description' => __('This is built-in Mtaandao Post Type.', 'mncf'),
                'taxonomies' => isset($map_taxonomies_by_post_type[$type])? $map_taxonomies_by_post_type[$type]:array(),
                'slug' => $type,
                'status' => 'active',
                'title' => $pt->labels->name,
                '_builtin' => true,
                'type' => '_builtin',
            );
            $add_one = true;
            if ( $s ) {
                $add_one = false;
                foreach( array('description', 'slug', 'title' ) as $key ) {
                    if ( $add_one || empty( $one[$key] ) ) {
                        continue;
                    }
                    if ( is_numeric(strpos(mb_strtolower($one[$key]), $s))) {
                        $add_one = true;
                    }
                }
            }
            if ( $add_one ) {
                $data[$one['slug']] = $one;
            }
        }

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        usort($data, 'mncf_usort_reorder');

        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);

        /**
         * The MN_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     * @access public
     *
     * @param object $item The current item
     */
    public function single_row($item)
    {
        static $row_class = '';
        $row_class = ( $row_class == '' ? 'alternate' : '' );

        printf('<tr class="%s status-%s">', $row_class, $item['status']);
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function no_items()
    {
        if ( isset($_POST['s']) ) {
            _e('No post types found.','mncf');
            return;
        }
        mncf_admin_ctt_list_header();
        printf(
            '<a class="button-primary" href="%s">%s</a>',
            esc_url(
                add_query_arg(
                    array(
                        'page' => 'mncf-edit-type',
                    ),
                    admin_url('admin.php')
                )
            ),
            __('Add New', 'mncf')
        );
    }

}