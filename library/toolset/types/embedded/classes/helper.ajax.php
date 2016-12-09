<?php
/*
 * Conditional class.
 */

/**
 * Conditional class.
 */
class MNCF_Helper_Ajax
{

    /**
     * Process AJAX conditional verify.
     * 
     * @global type $mncf
     * @param type $data
     * @return boolean|string
     *
     * @deprecated Is this used anywhere? No usages found in Types 2.1.
     */
    public static function conditionalVerify( $data ) {

        MNCF_Loader::loadInclude( 'fields' );
        MNCF_Loader::loadInclude( 'fields-post' );
        MNCF_Loader::loadInclude( 'conditional-display' );

        global $mncf;
        $js_execute = '';
        $_flag_relationship = false;
        /*
         * 
         * Determine post.
         */
        if ( empty( $data['mncf'] ) && !empty( $data['mncf_post_relationship'] ) ) {
            /*
             * Relationship case
             */
            $_temp = $data['mncf_post_relationship'];
            $parent_id = key( $_temp );
            $_data = array_shift( $_temp );
            $post_id = key( $_data );
            $post = get_post( $post_id );
            $posted_fields = $_data[$post_id];
            $_flag_relationship = true;
            /*
             * 
             * Regular submission
             */
        } else {
            if ( isset( $data['mncf_main_post_id'] ) ) {
                $post_id = intval( $data['mncf_main_post_id'] );
                $post = get_post( $post_id );
            }
        }

        // No post
        if ( empty( $post->ID ) ) {
            return false;
        }

        // Get Groups (Fields) for current post
        $groups = mncf_admin_post_get_post_groups_fields( $post );

        $_processed = array();
        foreach ( $groups as $group ) {
            if ( !empty( $group['fields'] ) ) {
                foreach ( $group['fields'] as $field_id => $field ) {

                    // Check if already processed
                    if ( isset( $_processed[$field_id] ) ) {
                        continue;
                    }

                    if ( $mncf->conditional->is_conditional( $field_id ) ) {
                        if ( $_flag_relationship ) {
                            // Process only submitted fields
                            if ( !isset( $posted_fields[MNCF_META_PREFIX . $field_id] ) ) {
                                continue;
                            }
                            $mncf->conditional->set( $post, $field_id );
                            $mncf->conditional->context = 'relationship';
                            $_relationship_name = false;
                            // Set name and other values processed by hooks
                            $parent = get_post( $parent_id );
                            if ( !empty( $parent->ID ) ) {
                                $mncf->relationship->set( $parent, $post );
                                $mncf->relationship->cf->set( $post, $field_id );
                                $_child = $mncf->relationship->get_child();
                                $_child->form->cf->set( $post, $field_id );
                                $_relationship_name = $_child->form->alter_form_name( 'mncf[' . $mncf->conditional->cf['id'] . ']' );
                            }
                            if ( !$_relationship_name ) {
                                continue;
                            }
                            /*
                             * BREAKPOINT
                             * Adds filtering regular evaluation (not mnv_conditional)
                             */
                            add_filter( 'types_field_get_submitted_data',
                                    'mncf_relationship_ajax_data_filter', 10, 2 );

                            $name = $_relationship_name;
                        } else {
                            $mncf->conditional->set( $post, $field_id );
                            $name = 'mncf[' . $mncf->conditional->cf['id'] . ']';
                        }

                        // Evaluate
                        $passed = $mncf->conditional->evaluate();

                        if ( $passed ) {
                            $js_execute .= 'jQuery(\'[name^="' . $name . '"]\').parents(\'.'
                                    . 'mncf-conditional' . '\').show().removeClass(\''
                                    . 'mncf-conditional' . '-failed\').addClass(\''
                                    . 'mncf-conditional' . '-passed\');' . " ";
                            $js_execute .= 'jQuery(\'[name^="' . $name
                                    . '"]\').parents(\'.mncf-repetitive-wrapper\').show();';
                        } else {
                            $js_execute .= 'jQuery(\'[name^="' . $name
                                    . '"]\').parents(\'.mncf-repetitive-wrapper\').hide();';
                            $js_execute .= 'jQuery(\'[name^="' . $name . '"]\').parents(\'.'
                                    . 'mncf-conditional' . '\').hide().addClass(\''
                                    . 'mncf-conditional' . '-failed\').removeClass(\''
                                    . 'mncf-conditional' . '-passed\');' . " ";
                        }
                    }
                    $_processed[$field_id] = true;
                }
            }
        }
        return $js_execute;
    }

}
