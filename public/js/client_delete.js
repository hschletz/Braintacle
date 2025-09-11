import { replaceDocument } from './functions.js'

const dialog = document.querySelector('#dialog_deleteClient')
dialog.addEventListener('close', async () => {
    if (dialog.returnValue == 'yes') {
        let url = dialog.getAttribute('data-action')
        if (dialog.querySelector('input[type=checkbox]').checked) {
            url += '?delete_interfaces'
        }
        console.log(url)
        const response = await fetch(url, { method: 'DELETE' })
        if (response.ok) {
            location.assign(dialog.getAttribute('data-redirect'))
        } else {
            const body = await response.text()
            if (response.headers.get('Content-Type').startsWith('text/plain')) {
                const message = document.createElement('p')
                message.classList.add('error')
                message.textContent = body
                dialog.after(message)
            } else {
                replaceDocument(body)
            }
        }
    }
})

document.querySelector('#button_deleteClient').addEventListener('click', () => {
    dialog.showModal()
})
