<?php

define('MNTOOLSET_FORMS_VERSION', '0.1.1');
define('MNTOOLSET_FORMS_ABSPATH', dirname(__FILE__));

require_once MNTOOLSET_FORMS_ABSPATH . '/api.php';

/**
 * check we are as a embedded?
 */
if (defined('MNCF_RUNNING_EMBEDDED') && MNCF_RUNNING_EMBEDDED) {
    define('MNTOOLSET_FORMS_RELPATH', mncf_get_file_url(__FILE__, false));
}
/**
 * setup MNTOOLSET_FORMS_RELPATH for plugin
 */
if (!defined('MNTOOLSET_FORMS_RELPATH')) {
    define('MNTOOLSET_FORMS_RELPATH', plugins_url('', __FILE__));
}
if (!defined('MNTOOLSET_COMMON_PATH')) {
    define('MNTOOLSET_COMMON_PATH', plugin_dir_path(__FILE__));
}

class MNToolset_Forms_Bootstrap {

    private $__forms;

    public final function __construct() {
        // Custom conditinal AJAX check
        add_action('mn_ajax_mntoolset_custom_conditional', array($this, 'ajaxCustomConditional'));

        // Date conditinal AJAX check
        add_action('mn_ajax_mntoolset_conditional', array($this, 'ajaxConditional'));

        // Date extended localization AJAX callback
        add_action('mn_ajax_mnt_localize_extended_date', array($this, 'mnt_localize_extended_date'));
        add_action('mn_ajax_nopriv_mnt_localize_extended_date', array($this, 'mnt_localize_extended_date'));

        // Taxonomy term suggest AJAX callback
        add_action('mn_ajax_mnt_suggest_taxonomy_term', array($this, 'mnt_suggest_taxonomy_term'));
        add_action('mn_ajax_nopriv_mnt_suggest_taxonomy_term', array($this, 'mnt_suggest_taxonomy_term'));

        add_filter('sanitize_file_name', array($this, 'sanitize_file_name'));

        add_filter('mntoolset_filter_mntoolset_repdrag_image', array($this, 'set_default_repdrag_image'), 10, 1);
        /**
         * common class for calendar
         */
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.date.scripts.php';
        new MNToolset_Field_Date_Scripts();

        add_action('pre_get_posts', array($this, 'pre_get_posts'));
    }

    // returns HTML
    public function field($form_id, $config, $value) {
        $form = $this->form($form_id, array());
        return $form->metaform($config, $config['name'], $value);
    }

    // returns HTML
    //    public function fieldEdit($form_id, $config) {
    //        $form = $this->form( $form_id, array() );
    //        return $form->editform( $config );
    //    }

    public function form($form_id, $config = array()) {
        if (isset($this->__forms[$form_id])) {
            return $this->__forms[$form_id];
        }
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.form_factory.php';
        return $this->__forms[$form_id] = new FormFactory($form_id, $config);
    }

    public function validate_field($form_id, $config, $value) {
        if (empty($config['validation'])) {
            return true;
        }
        $form = $this->form($form_id, array());
        return $form->validateField($config, $value);
    }

    public function ajaxCustomConditional() {
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';
        MNToolset_Forms_Conditional::ajaxCustomConditional();
    }

    public function checkConditional($config) {
        if (empty($config['conditional'])) {
            return true;
        }
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';
        return MNToolset_Forms_Conditional::evaluate($config['conditional']);
    }

    public function addConditional($form_id, $config) {
        $this->form($form_id)->addConditional($config);
    }

    public function ajaxConditional() {
        $data = $_POST['conditions'];
        $data['values'] = $_POST['values'];
        echo $this->checkConditional(array('conditional' => $data));
        die();
    }

    public function mnt_localize_extended_date() {
        if (!isset($_POST['date'])) {
            die();
        }
        $date = $_POST['date'];
        $date_format = '';
        if (isset($_POST['date-format'])) {
            $date_format = $_POST['date-format'];
        }
        if ($date_format == '') {
            $date_format = get_option('date_format');
        }
        $date = adodb_mktime(0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), substr($date, 4, 4));
        $date_format = str_replace('\\\\', '\\', $date_format);
        echo json_encode(array('display' => adodb_date($date_format, $date), 'timestamp' => $date));
        die();
    }

    /**
     * mnt_suggest_taxonomy_term
     *
     * Renders the suggestions when adding new flat taxonomy terms on a CRED form
     *
     * Needs a non-empty q attribute and can take an optional non-empty taxonomy attribute on the $_REQUEST
     *
     * @since 1.5.0
     */
    public function mnt_suggest_taxonomy_term() {

        if (
                !isset($_REQUEST['q']) || $_REQUEST['q'] == ''
        ) {
            die();
        }
        global $mndb;
        $_q = $_REQUEST['q'];
        $values_to_prepare = array();
        if (function_exists("mnv_esc_like")) {
            $term_name = '%' . mnv_esc_like($_q) . '%';
        } else {
            if (function_exists("cred_wrap_esc_like")) {
                $term_name = '%' . cred_wrap_esc_like($_q) . '%';
            }
        }

        $values_to_prepare[] = $term_name;

        $tax_join = "";
        $tax_where = "";
        if (
                isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] != ''
        ) {
            $tax_join = " JOIN {$mndb->term_taxonomy} tt ON t.term_id = tt.term_id  ";
            $tax_where = " AND tt.taxonomy = %s ";
            $values_to_prepare[] = sanitize_text_field( $_REQUEST['taxonomy'] );

            global $sitepress;
            if (isset($sitepress)) {
                if (isset($_REQUEST['source_lang'])) {
                    $src_lang = sanitize_text_field( $_REQUEST['source_lang'] );
                } else {
                    $src_lang = $sitepress->get_current_language();
                }
                if (isset($_REQUEST['lang'])) {
                    $lang = sanitize_text_field( $_REQUEST['lang'] );
                } else {
                    $lang = $src_lang;
                }
                $tax_where .= " AND t.term_id in (SELECT element_id from {$mndb->prefix}icl_translations WHERE element_type = %s AND language_code = %s  ) ";
                $values_to_prepare[] = sanitize_text_field( "tax_" . $_REQUEST['taxonomy'] );
                $values_to_prepare[] = $lang;
            }
        }

        $results = $mndb->get_results(
                $mndb->prepare(
                        "SELECT name FROM {$mndb->terms} t {$tax_join}
				WHERE t.name LIKE %s 
				{$tax_where}
				ORDER BY name DESC 
				LIMIT 5", $values_to_prepare
                )
        );

        foreach ($results as $row) {
            echo $row->name . "\n";
        }

        die();
    }

    public function filterTypesField($field, $post_id = null, $_post_mncf = array()) {
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';
        return MNToolset_Types::filterField($field, $post_id, $_post_mncf);
    }

    public function addFieldFilters($type) {
        if ($class = $this->form('generic')->loadFieldClass($type)) {
            call_user_func(array($class, 'addFilters'));
            call_user_func(array($class, 'addActions'));
        }
    }

    public function getConditionalData($form_id) {
        return $this->form($form_id)->getConditionalClass()->getData();
    }

    public function strtotime($date, $format = null) {
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.date.php';
        return MNToolset_Field_Date::strtotime($date, $format);
    }

    public function timetodate($timestamp, $format = null) {
        require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.date.php';
        return MNToolset_Field_Date::timetodate($timestamp, $format);
    }

    public function sanitize_file_name($filename) {
        /**
         * replace german special characters
         */
        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding($filename);

            if (strpos(strtolower($encoding), 'utf') !== false) {
                return $filename;
            }
        }


        $de_from = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü');
        $de_to = array('ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue');
        $filename = str_replace($de_from, $de_to, $filename);
        /**
         * replace polish special characters
         */
        $pl_from = array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż');
        $pl_to = array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z');
        $filename = str_replace($pl_from, $pl_to, $filename);
        /**
         * remove special characters
         */
        $filename = preg_replace('/[^A-Za-z0-9\._@]/', '-', $filename);
        $filename = preg_replace('/%20/', '-', $filename);
        return $filename;
    }

    public function set_default_repdrag_image($image) {
        return MNTOOLSET_FORMS_RELPATH . '/images/move.png';
    }

    /**
     * add custom post type to query when they use category or tags taxonomy.
     */
    public function pre_get_posts($query) {
        if (is_admin()) {
            return;
        }
        /**
         * do that only for main query
         */
        if (!$query->is_main_query()) {
            return;
        }
        $types_cpt = get_option('mncf-custom-types');
        if (!is_array($types_cpt) || empty($types_cpt)) {
            return;
        }
        $cpt_to_add = array();
        /**
         * check category
         */
        if (is_category()) {
            foreach ($types_cpt as $cpt_slug => $cpt) {
                if (array_key_exists('taxonomies', $cpt) && is_array($cpt['taxonomies'])) {
                    foreach ($cpt['taxonomies'] as $tax_slug => $value) {
                        if ('category' == $tax_slug && $value) {
                            $cpt_to_add[] = $cpt_slug;
                        }
                    }
                }
            }
        }
        /**
         * check tags
         */
        if (is_tag()) {
            foreach ($types_cpt as $cpt_slug => $cpt) {
                if (array_key_exists('taxonomies', $cpt) && is_array($cpt['taxonomies'])) {
                    foreach ($cpt['taxonomies'] as $tax_slug => $value) {
                        if ('post_tag' == $tax_slug && $value) {
                            $cpt_to_add[] = $cpt_slug;
                        }
                    }
                }
            }
        }
        /**
         * change query if some CPT use this
         */
        if (!empty($cpt_to_add)) {
            /**
             * remeber if is empty, then is post
             */
            $current_types = $query->get('post_type');
            if (empty($current_types)) {
                $cpt_to_add[] = 'post';
            } elseif (is_array($current_types)) {
                $cpt_to_add = array_merge($current_types, $cpt_to_add);
            } elseif (is_string($current_types)) {
                $cpt_to_add[] = $current_types;
            }
            $query->set('post_type', $cpt_to_add);
        }
        return;
    }

}

$GLOBALS['mntoolset_forms'] = new MNToolset_Forms_Bootstrap();
