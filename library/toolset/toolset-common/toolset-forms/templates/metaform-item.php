<?php
/**
 *
 *
 */
if (is_admin()) {
    ?>
    <div class="js-mnt-field-item mnt-field-item">
        <?php echo $out; ?>
        <?php if (@$cfg['repetitive']): ?>
            <div class="mnt-repctl">
                <div class="js-mnt-repdrag mnt-repdrag">&nbsp;</div>
                <a class="js-mnt-repdelete button button-small" data-mnt-type="<?php echo $cfg['type']; ?>" data-mnt-id="<?php echo $cfg['id']; ?>"><?php apply_filters('toolset_button_delete_repetition_text', printf(__('Delete %s', 'mnv-views'), strtolower($cfg['title'])), $cfg); ?></a>
            </div>
        <?php endif; ?>
    </div>
    <?php
} else {
    $toolset_repdrag_image = '';
    $button_extra_classnames = '';
    if ($cfg['repetitive']) {
        $toolset_repdrag_image = apply_filters('mntoolset_filter_mntoolset_repdrag_image', $toolset_repdrag_image);
        echo '<div class="mnt-repctl">';
        echo '<span class="js-mnt-repdrag mnt-repdrag"><img class="mnv-repdrag-image" src="' . $toolset_repdrag_image . '" /></span>';
    }
    echo $out;
    if ($cfg['repetitive']) {
        if (array_key_exists('use_bootstrap', $cfg) && $cfg['use_bootstrap']) {
            $button_extra_classnames = ' btn btn-default btn-sm';
        }
        $str = sprintf(__('%s repetition', 'mnv-views'), $cfg['title']);
        echo '<input type="button" href="#" class="js-mnt-repdelete mnt-repdelete' . $button_extra_classnames . '" value="';
        echo apply_filters('toolset_button_delete_repetition_text', esc_attr(__('Delete', 'mnv-views')) . " " . esc_attr($str), $cfg);
        echo '" />';
        echo '</div>';
    }
}
