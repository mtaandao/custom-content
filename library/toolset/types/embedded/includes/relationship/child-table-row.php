<?php
/*
 * Child table row
 */

?>

<tr id="types-child-row-<?php echo $child_id; ?>">
    <?php
    foreach ( $row as $td ) {

        ?>
        <td><?php echo $td; ?></td>
        <?php
    }

    ?>
    <td class="actions">

        <!--SAVE-->
        <a href="<?php
    echo admin_url( 'admin-ajax.php?action=mncf_ajax&amp;'
            . 'mncf_action=pr_save_child_post&amp;post_type_parent='
            . $this->parent_post_type
            . '&amp;post_id=' . $child_id
            . '&amp;parent_id=' . $this->parent->ID
            . '&amp;post_type_child='
            . $this->child_post_type . '&_mnnonce=' . mn_create_nonce( 'pr_save_child_post' ) );

    ?>" class="mncf-pr-save-ajax button-secondary"><?php
           echo __( 'Save', 'mncf' );

    ?></a>
        <!--EDIT-->
        <?php
        if ( strpos( $this->child->ID, 'new_' ) === false ):

            ?><a href="<?php echo get_edit_post_link( $child_id ); ?>" class="button-secondary"><?php
        echo __( 'Edit', 'mncf' );

            ?></a>
            <a href="<?php
            echo admin_url( 'admin-ajax.php?action=mncf_ajax&amp;'
                    . 'mncf_action=pr_delete_child_post'
                    . '&amp;post_id=' . $child_id
                    . '&amp;parent_id=' . $this->parent->ID
                    . '&_mnnonce=' . mn_create_nonce( 'pr_delete_child_post' ) );

            ?>" class="mncf-pr-delete-ajax button-secondary"><?php
           echo __( 'Delete', 'mncf' );

            ?></a>
            <?php
        endif;

        // Trigger Conditional
        // TODO Move to conditional.php
        if ( defined( 'DOING_AJAX' ) && !defined( 'MNTOOLSET_FORMS_VERSION' ) ):

            ?>
            <script type="text/javascript">
                //<![CDATA[
                jQuery(document).ready(function(){
                    mncfConditionalInit('#types-child-row-<?php echo $child_id; ?>');
                });
                //]]>
            </script>
            <?php
        endif;

        ?>
    </td>
</tr>
