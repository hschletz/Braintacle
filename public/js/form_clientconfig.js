for (const checkbox of document.querySelectorAll('.form_clientconfig .toggle')) {
    checkbox.addEventListener('change', () => toggle(checkbox))
    toggle(checkbox)
}

function toggle(checkbox) {
    const display = checkbox.checked ? 'contents' : 'none'
    // Toggle all <label> elements except for the first (which contains the checkbox itself)
    for (const row of checkbox.closest('fieldset').querySelectorAll('label:nth-of-type(n+2)')) {
        row.style.display = display
    }
}
