// Run form initializations like hiding elements in a load handler instead of a
// .ready() handler where hidden elements would be briefly visible, causing a
// noticeable flicker.
$(window).on('load', function() {
    // Show/hide elements according to selected "Where" radio button
    $('.form_addtogroup [name=Where]').change(function() {

        var element = $(this);
        var isNewGroup = (element.val() == 'new');
        $('[name=NewGroup]').parent().toggle(isNewGroup);
        $('[name=Description]').parent().toggle(isNewGroup);
        $('[name=ExistingGroup]').parent().toggle(!isNewGroup);
        element.closest('form').find('ul').toggle(isNewGroup);

    }).filter(':checked').change();

    // Show/hide all rows following the triggering checkbox within the same fieldset
    $('.form_clientconfig .toggle').change(function() {

        var element = $(this);
        var checked = element.prop('checked');
        element.parent().nextAll().each(function() {
            $(this).toggle(checked);
        });

    }).change();

    // Set options for "operators" element according to selected filter
    $('.form_search [name=filter]').change(function() {

        var types = $(this).data('types'); // map of filters to ordinal types (text filters are not present in map)
        var operatorElement = this.form['operator'];
        var operators = $(operatorElement).data(
            types.hasOwnProperty(this.value) ? 'operatorsOrdinal' : 'operatorsText'
        );

        while (operatorElement.options.length) {
            operatorElement.remove(0);
        }
        for (var value in operators) {
            operatorElement.add(new Option(operators[value], value));
        }

    });

    // Check/uncheck all checkboxes within the same table
    $('.form_showduplicates .checkAll, .form_software .checkAll').change(function() {
        $('input[type=checkbox][name]', $(this).closest('table')).prop('checked', this.checked);
    });
});
