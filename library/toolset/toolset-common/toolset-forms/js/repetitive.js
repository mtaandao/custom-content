/*
 * Repetitive JS.
 *
 *
 */
var mntRep = (function ($) {
    var count = {};
    function init() {
        // Reorder label and description for repetitive
		// Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
        $('.js-mnt-repetitive').each(function () {
            var $this = $(this),
                    $parent;
            if ($('body').hasClass('admin')) {
                var title = $('label:not(.js-mnt-auxiliar-label)', $this).first().clone();
                var description = $('.description:not(.js-mnt-auxiliar-description)', $this).first().clone();
                $('.js-mnt-field-item', $this).each(function () {
                    $('label:not(.js-mnt-auxiliar-label)', $this).remove();
                    $('.description:not(.js-mnt-auxiliar-description)', $this).remove();
                });
                $this.prepend(description).prepend(title);
            }
            if ($this.hasClass('js-mnt-field-items')) {// This happens on the frontent
                $parent = $this;
            } else {// This happens on the backend
                $parent = $this.find('.js-mnt-field-items');
            }
            _toggleCtl($parent);
        });
        $('.js-mnt-field-items').each(function () {
            if ($(this).find('.js-mnt-repdelete').length > 1) {
                $(this).find('.js-mnt-repdelete').show();
            } else if ($(this).find('.js-mnt-repdelete').length == 1) {
                $(this).find('.js-mnt-repdelete').hide();
            }
        });
        // Add field
        $(document).off('click','.js-mnt-repadd', null);
        $(document).on('click','.js-mnt-repadd', function (e) {
            e.preventDefault();
            var $this = $(this),
                    parent,
                    tpl;
            $parent = $this.closest('.js-mnt-field-items');
            if (1 > $parent.length) {
                return;
            }
            if ($('body').hasClass('admin')) {
                // Get template from the footer templates by mnt-id data attribute
                tpl = $('<div>' + $('#tpl-mnt-field-' + $this.data('mnt-id')).html() + '</div>');
                // Remove label and descriptions from the template
				// Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
                $('label:not(.js-mnt-auxiliar-label)', tpl).first().remove();
                $('.description:not(.js-mnt-auxiliar-description)', tpl).first().remove();
                // Adjust ids and labels where needed for the template content
                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('mnt-form-el');
                    tpl.find('label[for="' + $this.attr('id') + '"]').attr('for', uniqueId);
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('mnt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }
                // Insert the template before the button
                $this.before(tpl.html().replace(/\[%%(\d+)%%\]/g, '[' + _count + ']'));
            } else {
                /**
                 * template
                 */
                tpl = $('<div>' + $('#tpl-mnt-field-' + $this.data('mnt-id')).html() + '</div>');

                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('mnt-form-el');
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('mnt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }
                // Insert the template before the button
                $this.before(tpl.html().replace(/\[%%(\d+)%%\]/g, '[' + _count + ']'));

            }
            mntCallbacks.addRepetitive.fire($parent);
            _toggleCtl($parent);
            $this.trigger('blur');// To prevent it from staying on the active state
            return false;
        });
        // Delete field
        $(document).off('click', '.js-mnt-repdelete', null);
        $(document).on('click', '.js-mnt-repdelete', function (e) {
        //$('.js-mnt-field-items').on('click', '.js-mnt-repdelete', function (e) {
            e.preventDefault();
            $parent = $(this).closest('.js-mnt-field-items');
            if ($('body').hasClass('admin')) {
                var $this = $(this),
                        value;
                // Allow deleting if more than one field item
                if ($('.js-mnt-field-item', $parent).length > 1) {
                    var formID = $this.parents('form').attr('id');
                    $this.parents('.js-mnt-field-item').remove();
                    mntCallbacks.removeRepetitive.fire(formID);
                }
                /**
                 * if image, try delete images
                 * TODO check this, I do not like using parent() for this kind of things
                 */
                if ('image' == $this.data('mnt-type')) {
                    value = $this.parent().parent().find('input').val();
                    $parent.parent().append(
                            '<input type="hidden" name="mncf[delete-image][]" value="'
                            + value
                            + '"/>'
                            );
                }
            } else {
                if ($('.mnt-repctl', $parent).length > 1) {
                    $(this).closest('.mnt-repctl').remove();
                    mntCallbacks.removeRepetitive.fire(formID);
                }
            }
            _toggleCtl($parent);
            return false;
        });
    }
    function _toggleCtl($sortable) {
        var sorting_count;
        if ($('body').hasClass('admin')) {
            sorting_count = $('.js-mnt-field-item', $sortable).length;
        } else {
            sorting_count = $('.mnt-repctl', $sortable).length;
        }
        if (sorting_count > 1) {
            $('.js-mnt-repdelete', $sortable).prop('disabled', false).show();
            $('.js-mnt-repdrag', $sortable).css({opacity: 1, cursor: 'move'}).show();
            if (!$sortable.hasClass('ui-sortable')) {
                $sortable.sortable({
                    handle: '.js-mnt-repdrag',
                    axis: 'y',
                    stop: function (event, ui) {
                        $sortable.find('.js-mnt-repadd').detach().appendTo($sortable);
                    }
                });
            }
        } else {
            $('.js-mnt-repdelete', $sortable).prop('disabled', true).hide();
            $('.js-mnt-repdrag', $sortable).css({opacity: 0.5, cursor: 'default'}).hide();
            if ($sortable.hasClass('ui-sortable')) {
                $sortable.sortable('destroy');
            }
        }
    }
    function _countIt(_count, id) {
        if (typeof count[id] == 'undefined') {
            count[id] = _count;
            return _count;
        }
        return ++count[id];
    }
    return {
        init: init
    };
})(jQuery);

jQuery(document).ready(mntRep.init);
