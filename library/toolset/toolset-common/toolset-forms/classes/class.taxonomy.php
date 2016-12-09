<?php
/**
 *
 *
 */
require_once 'class.textfield.php';

class MNToolset_Field_Taxonomy extends MNToolset_Field_Textfield {

    public $values = "";
    public $objValues;

    public function init() {
        $this->objValues = array();

        // Compatibility with CRED 1.8.4 and above
        if( class_exists( 'CRED_Form_Rendering' ) ) {
            $current_post_id = CRED_Form_Rendering::$current_postid;
        } else {
            // CRED <= 1.8.3
            $current_post_id = CredForm::$current_postid;
        }

        $terms = apply_filters('toolset_filter_taxonomy_terms', mn_get_post_terms( $current_post_id, $this->getName(), array("fields" => "all")));
        $i = 0;
        foreach ($terms as $n => $term) {
            $this->values .= ($i == 0) ? $term->name : "," . $term->name;
            $this->objValues[$term->slug] = $term;
            $i++;
        }

        mn_register_script('mntoolset-taxonomy-field', MNTOOLSET_FORMS_RELPATH . '/js/taxonomy.js', array('mntoolset-forms'), MNTOOLSET_FORMS_VERSION, true);

        mn_localize_script('mntoolset-taxonomy-field', 'mntoolset_taxonomy_settings', array(
            'ajaxurl' => admin_url('admin-ajax.php', null),
            'values' => $this->values,
            'name' => $this->getName(),
            'form' => MNTOOLSET_FORMS_RELPATH,
            'field' => $this->_nameField,
        ));

        mn_enqueue_script('mntoolset-taxonomy-field');

        //add_action('mn_footer', array($this, 'javascript_autocompleter'));
    }

    /**
     * function used when ajax is on in order to init taxonomies
     * @return type
     */
    public function initTaxonomyFunction() {
        return '<script type="text/javascript">
                    function initCurrentTaxonomy() {
                            initTaxonomies("' . $this->values . '", "' . $this->getName() . '", "' . MNTOOLSET_FORMS_RELPATH . '", "' . $this->_nameField . '");
                    }
            </script>';
    }

    /**
     * metaform
     * @return type
     */
    public function metaform() {
        $use_bootstrap = array_key_exists('use_bootstrap', $this->_data) && $this->_data['use_bootstrap'];
        $attributes = $this->getAttr();
        $taxonomy = $this->getName();

        $metaform = array();
        $metaform[] = array(
            '#type' => 'hidden',
            '#title' => '',
            '#description' => '',
            '#name' => $taxonomy,
            '#value' => $this->values,
            '#attributes' => array(
            ),
            '#validate' => $this->getValidationData(),
        );
        $metaform[] = array(
            '#type' => 'textfield',
            '#title' => '',
            '#description' => '',
            '#name' => "tmp_" . $taxonomy,
            '#value' => $this->getValue(),
            '#attributes' => array(
                'data-taxonomy' => $taxonomy,
                'data-taxtype' => 'flat',
                'class' => $use_bootstrap ? 'inline mnt-new-taxonomy-title js-mnt-new-taxonomy-title' : 'mnt-new-taxonomy-title js-mnt-new-taxonomy-title',
            ),
            '#validate' => $this->getValidationData(),
            '#before' => $use_bootstrap ? '<div class="form-group">' : '',
        );

        /**
         * add button
         */
        $metaform[] = array(
            '#type' => 'button',
            '#title' => '',
            '#description' => '',
            '#name' => "new_tax_button_" . $taxonomy,
            '#value' => apply_filters('toolset_button_add_text', esc_attr($attributes['add_text'])),
            '#attributes' => array(
                'class' => $use_bootstrap ? 'btn btn-default mnt-taxonomy-add-new js-mnt-taxonomy-add-new' : 'mnt-taxonomy-add-new js-mnt-taxonomy-add-new',
                'data-taxonomy' => $taxonomy,
            ),
            '#validate' => $this->getValidationData(),
            '#after' => $use_bootstrap ? '</div>' : '',
        );

        $before = sprintf(
                '<div class="tagchecklist tagchecklist-%s"></div>', $this->getName()
        );
        $after = $this->getMostPopularTerms();
        if ($use_bootstrap) {
            $before = '<div class="form-group">' . $before;
            $after .= '</div>';
        }
        $show = isset($attributes['show_popular']) && $attributes['show_popular'] == 'true';
        /**
         * show popular button
         */
        $metaform[] = array(
            '#type' => 'button',
            '#title' => '',
            '#description' => '',
            '#name' => "sh_" . $taxonomy,
            '#value' => apply_filters('toolset_button_show_popular_text', esc_attr($attributes['show_popular_text'])),
            '#attributes' => array(
                'class' => $use_bootstrap ? 'btn btn-default popular mnt-taxonomy-popular-show-hide js-mnt-taxonomy-popular-show-hide' : 'popular mnt-taxonomy-popular-show-hide js-mnt-taxonomy-popular-show-hide',
                'data-taxonomy' => $this->getName(),
                'data-show-popular-text' => apply_filters('toolset_button_show_popular_text', esc_attr($attributes['show_popular_text'])),
                'data-hide-popular-text' => apply_filters('toolset_button_hide_popular_text', esc_attr($attributes['hide_popular_text'])),
                'data-after-selector' => 'js-show-popular-after',
                'style' => $show ? '' : 'display:none;'
            ),
            '#before' => $before,
            '#after' => $after . $this->initTaxonomyFunction(),
        );

        $this->set_metaform($metaform);
        return $metaform;
    }

    private function buildTerms($obj_terms) {
        $tax_terms = array();
        foreach ($obj_terms as $term) {
            $tax_terms[] = array(
                'name' => $term->name,
                'count' => $term->count,
                'parent' => $term->parent,
                'term_taxonomy_id' => $term->term_taxonomy_id,
                'term_id' => $term->term_id
            );
        }
        return $tax_terms;
    }

    private function buildCheckboxes($index, &$childs, &$names) {
        if (isset($childs[$index])) {
            foreach ($childs[$index] as $tid) {
                $name = $names[$tid];
                ?>
                <div style='position:relative;line-height:0.9em;margin:2px 0;<?php if ($tid != 0) echo 'margin-left:15px'; ?>' class='myzebra-taxonomy-hierarchical-checkbox'>
                    <label class='myzebra-style-label'><input type='checkbox' name='<?php echo $name; ?>' value='<?php echo $tid; ?>' <?php if (isset($values[$tid])) echo 'checked="checked"'; ?> /><span class="myzebra-checkbox-replace"></span>
                        <span class='myzebra-checkbox-label-span' style='position:relative;font-size:12px;display:inline-block;margin:0;padding:0;margin-left:15px'><?php echo $names[$tid]; ?></span></label>
                        <?php
                        if (isset($childs[$tid]))
                            echo $this->buildCheckboxes($tid, $childs, $names);
                        ?>
                </div>
                <?php
            }
        }
    }

    public function getMostPopularTerms() {
        $use_bootstrap = array_key_exists('use_bootstrap', $this->_data) && $this->_data['use_bootstrap'];

        $term_args = array(
            'number' => 10,
            'orderby' => 'count',
            'order' => 'DESC'
        );
        $terms = get_terms(array($this->getName()), $term_args);
        if (empty($terms)) {
            return '';
        }
        $max = -1;
        $min = PHP_INT_MAX;
        foreach ($terms as $term) {
            if ($term->count < $min) {
                $min = $term->count;
            }
            if ($term->count > $max) {
                $max = $term->count;
            }
        }
        $add_sizes = $max > $min;

        if ($use_bootstrap) {
            $content = sprintf(
                    '<div class="shmpt-%s form-group mnt-taxonomy-show-popular-list js-show-popular-after" style="display:none">', $this->getName()
            );
        } else {
            $content = sprintf(
                    '<div class="shmpt-%s mnt-taxonomy-show-popular-list js-show-popular-after" style="display:none">', $this->getName()
            );
        }

        foreach ($terms as $term) {
            $style = '';
            if ($add_sizes) {
                $font_size = ( ( $term->count - $min ) * 10 ) / ( $max - $min ) + 8;
                $style = sprintf(' style="font-size:%fem;"', $font_size / 10);
            }
            $clases = array('mnt-taxonomy-popular-add', 'js-mnt-taxonomy-popular-add');
            $clases[] = 'tax-' . $term->slug;
            $clases[] = 'taxonomy-' . $this->getName() . '-' . $term->term_id;
            $content .= sprintf(
                    '<a href="#" class="%s" data-slug="%s" data-name="%s" data-taxonomy="%s"%s>%s</a> ', implode(' ', $clases), $term->slug, $term->name, $this->getName(), $style, $term->name
            );
        }
        $content .= "</div>";
        return $content;
    }

}
