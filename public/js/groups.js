import { replaceDocument } from './functions.js'

const dialog = document.querySelector('#dialog_deleteGroup')
dialog.addEventListener('close', async () => {
    if (dialog.returnValue == 'yes') {
        const url = dialog.getAttribute('data-action')
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

document.querySelector('#button_deleteGroup').addEventListener('click', () => {
    dialog.showModal()
})
