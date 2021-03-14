/**
 * ClientConfig form.
 */

// Show/hide all rows following the triggering checkbox within the same fieldset
for (const checkbox of document.querySelectorAll('.form_clientconfig .toggle')) {
    function onChange()
    {
        const display = checkbox.checked ? 'block' : 'none'
        let node = checkbox.parentNode
        while (node = node.nextSibling) {
            node.style.display = display
        }
    }
    checkbox.addEventListener('change', onChange)

    // Show/hide rows according to initial checkbox state
    onChange()
}
