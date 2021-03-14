/**
 * ShowDuplicates form.
 */

 // Check/uncheck all entries via a single checkbox
const toggle = document.querySelector('.form_showduplicates .checkAll')
toggle.addEventListener('change', () => {
    const checkboxes = toggle.closest('table').querySelectorAll('input[type=checkbox][name]')
    for (const checkbox of checkboxes) {
        checkbox.checked = toggle.checked
    }
})
