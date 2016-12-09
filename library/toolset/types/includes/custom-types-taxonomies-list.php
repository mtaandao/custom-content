<?php
/*
 * Types and Taxonomies list functions
 */

function mncf_admin_ctt_list_header() {
	$custom_types = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
	$custom_taxonomies = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

	if ( empty( $custom_types ) && empty( $custom_taxonomies ) ) {
		printf( '<p>%s %s<a href="%s" target="_blank">%s &raquo;</a></p>',
			__( 'Post Types are user-defined content types. Taxonomies are used to categorize your content.', 'mncf' ),
			__( 'You can read more about Post Types and Taxonomies in this tutorial.', 'mncf' ),
			Types_Helper_Url::get_url( 'custom-post-types' ),
			Types_Helper_Url::get_url( 'custom-post-types' )
		);
	}
}

function mncf_admin_custom_post_types_list()
{
    include_once dirname(__FILE__).'/classes/class.types.admin.post.types.list.table.php';
    //Create an instance of our package class...
    $listTable = new Types_Admin_Post_Types_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $listTable->prepare_items();
    ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="cpt-filter" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php $listTable->search_box(__('Search Post Types', 'mncf'), 'search_id'); ?>
            <!-- Now we can render the completed list table -->
            <?php $listTable->display() ?>
        </form>
    <?php
}

function mncf_admin_custom_taxonomies_list()
{
    include_once dirname(__FILE__).'/classes/class.types.admin.taxonomies.list.table.php';
    //Create an instance of our package class...
    $listTable = new Types_Admin_Taxonomies_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $listTable->prepare_items();
    ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="ct-filter" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php $listTable->search_box(__('Search Taxonomies', 'mncf'), 'search_id'); ?>
            <!-- Now we can render the completed list table -->
            <?php $listTable->display() ?>
        </form>
    <?php
}

