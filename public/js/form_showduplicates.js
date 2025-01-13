import { replaceDocument } from './functions.js'

const form = document.querySelector('form.form_showduplicates')

// Check/uncheck all entries via a single checkbox
const toggle = form.querySelector('.form_showduplicates .checkAll')
toggle.addEventListener('change', () => {
    const checkboxes = toggle.closest('table').querySelectorAll('input[type=checkbox][name]')
    for (const checkbox of checkboxes) {
        checkbox.checked = toggle.checked
    }
})

form.addEventListener('submit', async event => {
    event.preventDefault()

    const response = await fetch(form.getAttribute('data-action'), {
        method: 'POST',
        body: new FormData(form)
    })
    if (response.ok) {
        location.assign(form.getAttribute('data-redirect'))
    } else if (response.status == 400) {
        // validation error
        const ul = document.querySelector('ul.error')
        ul.replaceChildren() // Clear list in case of previous errors

        const messages = await response.json()
        for (const message of messages) {
            const li = document.createElement('li')
            li.textContent = message
            ul.appendChild(li)
        }
    } else {
        // other error
        replaceDocument(await response.text())
    }
})
