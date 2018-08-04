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
});
