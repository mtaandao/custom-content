<?php
/**
 *
 * Types Marketing Class
 *
 *
 */

/**
 * Types Marketing Class
 *
 * @since Types 1.6.5
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category Help
 * @author marcin <marcin.p@icanlocalize.com>
 */
class MNCF_Types_Marketing
{
    protected $option_name = 'types-site-kind';
    protected $option_disable = 'types-site-kind-disable';
    protected $options;
    protected $adverts;

    public function __construct()
    {
        $this->options = array();
        $this->adverts = include MNCF_ABSPATH.'/marketing/etc/types.php';
        add_filter('editor_addon_dropdown_after_title', array($this, 'add_views_advertising'));
    }

    /**
     * Add Views advertising on modal shortcode window.
     *
     * Add Views advertising on modal shortcode window. Advertisng will be 
     * added only when Views plugin is not active.
     *
     * @since 1.7
     * @param string $content Content of this filter.
     * @return string Content with advert or not.
     */
    public function add_views_advertising($content)
    {
        /**
         * do not load advertising if Views are active
         */
        if ( defined('MNV_VERSION') ) {
            return $content;
        }
        /**
         * Allow to turn off views advert.
         *
         * This filter allow to turn off views advert even Viwes plugin is not 
         * avaialbe.
         *
         * @since 1.7
         *
         * @param boolean $show Show adver?
         */
        if ( !apply_filters('show_views_advertising', true )) {
            return;
        }
        $content .= '<div class="types-marketing types-marketing-views">';
        $content .= sprintf(
            '<h4><span class="icon-toolset-logo ont-color-orange"></span>%s</h4>',
            __('Want to create templates with fields?', 'mncf')
        );
        $content .= sprintf(
            '<p>%s</p>',
            __('The full Custom Content package allows you to design templates for content and insert fields using the Mtaandao editor.', 'mncf')
        );
        $content .= sprintf(
            '<p class="buttons"><a href="%s" class="button" target="_blank">%s</a> <a href="%s" class="more" target="_blank">%s</a></p>',
            esc_attr(
	            Types_Helper_Url::get_url( 'mn-types', true, 'meet-toolset', Types_Helper_Url::UTM_MEDIUM_POSTEDIT )
            ),
            __('Meet Toolset', 'mncf'),
            esc_attr(
	            Types_Helper_Url::get_url( 'content-templates', true, 'creating-content-templates', Types_Helper_Url::UTM_MEDIUM_POSTEDIT )
            ),
            __('Creating Templates for Content', 'mncf')
        );
        $content .= '</div>';
        return $content;
    }

    protected function get_page_type() {
	    $screen = get_current_screen();
	    switch ( $screen->id ) {
		    case 'toolset_page_mncf-edit-type':
			    return 'cpt';
		    case 'toolset_page_mncf-edit-tax':
			    return 'taxonomy';
		    case 'toolset_page_mncf-edit':
		    case 'toolset_page_mncf-edit-usermeta':
		    case 'toolset_page_mncf-termmeta-edit':
			    return 'fields';
	    }

	    return false;
    }

    public function get_options()
    {
        return $this->options;
    }

    public function get_option_name()
    {
        return $this->option_name;
    }

    public function get_option_disiable_value()
    {
        return get_option($this->option_disable, 0);
    }

    public function get_option_disiable_name()
    {
        return $this->option_disable;
    }

}
