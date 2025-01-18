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

const dialog = document.querySelector('dialog')
dialog.addEventListener('close', async () => {
    if (dialog.returnValue == 'yes') {
        const href = dialog.getAttribute('data-allow-href')
        const formData = new FormData()
        formData.set('criterion', criterion)
        formData.set('value', value)
        const response = await fetch(href, { method: 'POST', body: formData })
        if (response.ok) {
            location.assign(form.getAttribute('data-redirect'))
        } else {
            replaceDocument(await response.text())
        }
    }
})

let criterion
let value

for (const button of form.querySelectorAll('table button')) {
    button.addEventListener('click', () => {
        criterion = button.getAttribute('data-criterion')
        value = button.textContent.trim()

        let templateId
        switch (criterion) {
            case 'mac_address':
                templateId = 'message_mac_address'
                break
            case 'serial':
                templateId = 'message_serial'
                break
            case 'asset_tag':
                templateId = 'message_asset_tag'
                break
        }
        const messageTemplate = document.getElementById(templateId).content.textContent
        dialog.querySelector('p').textContent = messageTemplate.replace('{}', value)
        dialog.showModal()
    })
}
