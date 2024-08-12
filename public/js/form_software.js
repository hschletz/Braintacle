/**
 * Forms on software page.
 */

document.querySelector('form.form_software_filter select').addEventListener('change', event => {
    event.target.form.submit()
})

const form = document.querySelector('form.form_software')

// Check/uncheck all entries via a single checkbox
const toggle = form.querySelector('.checkAll')
toggle.addEventListener('change', () => {
    const checkboxes = form.querySelectorAll('input[type=checkbox][name]')
    for (const checkbox of checkboxes) {
        checkbox.checked = toggle.checked
    }
})

let submitter = null

const dialog = document.querySelector('dialog.dialog_software')
dialog.addEventListener('close', () => {
    if (dialog.returnValue == 'yes') {
        form.requestSubmit(submitter)
    } else {
        submitter = null
    }
})

form.addEventListener('submit', event => {
    if (submitter) {
        return
    }

    event.preventDefault()
    submitter = event.submitter
    const action = submitter.value

    for (const message of dialog.getElementsByClassName('message')) {
        message.style.display = message.classList.contains(action) ? 'block' : 'none'
    }

    const list = dialog.querySelector('ul')
    list.replaceChildren()
    const checkboxes = form.querySelectorAll('input:checked[name]')
    for (const checkbox of checkboxes) {
        const item = document.createElement('li')
        item.textContent = checkbox.value
        list.append(item)
    }

    dialog.showModal()
})
