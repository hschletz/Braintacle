/**
 * AddToGroup form.
 */

const form = document.querySelector('.form_addtogroup form')
const newGroupInput = document.querySelector('.form_addtogroup input[name=name]')
const existingGroupSelect = document.querySelector('.form_addtogroup select[name=group]')
const existingGroups = Array.from(existingGroupSelect.options).map(option => option.value.toLowerCase())

newGroupInput.addEventListener('input', () => {
    if (existingGroups.includes(newGroupInput.value.toLowerCase().trim())) {
        newGroupInput.setCustomValidity(newGroupInput.getAttribute('data-validationmessage'))
    } else {
        newGroupInput.setCustomValidity('')
    }
})

// Show/hide elements according to selected "where" radio button
for (const radio of document.querySelectorAll('.form_addtogroup [name=where]')) {
    radio.addEventListener('change', event => onChange(event.target))
}

// Show/hide elements according to initially selected radio button
onChange(document.querySelector('.form_addtogroup [name=where]:checked'))

function onChange(element) {
    const isNewGroup = (element.value == 'new')
    toggle(newGroupInput, isNewGroup)
    toggle(existingGroupSelect, !isNewGroup)
    toggle(form['description'], isNewGroup)
}

function toggle(element, show) {
    element.parentNode.style.display = show ? 'block' : 'none'
    element.disabled = !show
}
