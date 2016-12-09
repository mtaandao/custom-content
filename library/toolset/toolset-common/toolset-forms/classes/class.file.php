<?php
/**
 *
 *
 */
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class MNToolset_Field_File extends MNToolset_Field_Textfield
{

    protected $_validation = array('required');
    //protected $_defaults = array('filename' => '', 'button_style' => 'btn2');

    public function init()
    {
        MNToolset_Field_File::file_enqueue_scripts();
        $this->set_placeholder_as_attribute();
    }

    public static function file_enqueue_scripts()
    {
        mn_register_script(
            'mntoolset-field-file',
            MNTOOLSET_FORMS_RELPATH . '/js/file-mn35.js',
            array('jquery', 'jquery-masonry'),
            MNTOOLSET_FORMS_VERSION,
            true
        );

        if ( !mn_script_is( 'mntoolset-field-file', 'enqueued' ) ) {
            mn_enqueue_script( 'mntoolset-field-file' );
        }

        if ( is_admin() ) {
            $screen = get_current_screen();
            if (isset($screen->parent_base) && 'users' == $screen->parent_base) {
                mn_enqueue_media();
            }

            if (isset($screen->post_type) && isset($screen->base) && 'post' == $screen->base) {
                global $post;
                if ( is_object($post) ) {
                    mn_enqueue_media(array('post' => $post->ID));
                }
            }
        }

    }

    public function enqueueStyles()
    {
    }

    /**
     *
     * @global object $mndb
     *
     */
    public function metaform()
    {
        $value = $this->getValue();
        $type = $this->getType();
        $translated_type = '';
        $form = array();
        $preview = '';
		$mnml_action = $this->getMNMLAction();

        // Get attachment by guid
        if ( !empty( $value ) ) {
            global $mndb;
            $attachment_id = $mndb->get_var(
                $mndb->prepare(
                    "SELECT ID FROM {$mndb->posts} WHERE post_type = 'attachment' AND guid=%s",
                    $value
                )
            );
        }

        // Set preview
        if ( !empty( $attachment_id ) ) {
            $attributes = array();
            $full = mn_get_attachment_image_src($attachment_id, 'full');
            if ( !empty($full) ) {
                  $attributes['data-full-src'] = esc_attr($full[0]);
            }
            $preview = mn_get_attachment_image( $attachment_id, 'thumbnail', false, $attributes);
        } else {
            // If external image set preview
            $file_path = parse_url( $value );
            if ( $file_path && isset( $file_path['path'] ) ) {
                $file = pathinfo( $file_path['path'] );
            }
            else {
                $file = pathinfo( $value );
            }
            if (
                isset( $file['extension'] )
                && in_array( strtolower( $file['extension'] ), array('jpg', 'jpeg', 'gif', 'png') )
            ) {
                $preview = '<img alt="" src="' . $value . '" />';
            }
        }

        // Set button
        switch( $type ) {
            case 'audio':
                $translated_type = __( 'audio', 'mnv-views' );
                break;
            case 'image':
                $translated_type = __( 'image', 'mnv-views' );
                break;
            case 'video':
                $translated_type = __( 'video', 'mnv-views' );
                break;
            default:
                $translated_type = __( 'file', 'mnv-views' );
                break;
        }
		
		$button_status = '';
		if (
			is_admin()
			&& defined( 'MNML_TM_VERSION' ) 
			&& intval( $mnml_action ) === 1 
			&& function_exists( 'mncf_mnml_post_is_original' )
			&& ! mncf_mnml_post_is_original()
			&& function_exists( 'mncf_mnml_have_original' )
			&& mncf_mnml_have_original()
		) {
			$button_status = ' disabled="disabled"';
		}
		
        $button = sprintf(
            '<button class="js-mnt-file-upload button button-secondary" data-mnt-type="%s"%s>%s</button>',
            $type,
			$button_status,
            sprintf( __( 'Select %s', 'mnv-views' ), $translated_type )
        );

        // Set form
        $form[] = array(
            '#type'			=> 'textfield',
            '#name'			=> $this->getName(),
            '#title'		=> $this->getTitle(),
            '#description'	=> $this->getDescription(),
            '#value'		=> $value,
            '#suffix'		=> '&nbsp;' . $button,
            '#validate'		=> $this->getValidationData(),
            '#repetitive'	=> $this->isRepetitive(),
            '#attributes'	=> $this->getAttr(),
			'mnml_action'	=> $mnml_action,
        );

        $form[] = array(
            '#type' => 'markup',
            '#markup' => '<div class="js-mnt-file-preview mnt-file-preview">' . $preview . '</div>',
        );

        return $form;
    }
}
