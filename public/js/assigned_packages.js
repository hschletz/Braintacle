let packageName

for (const button of document.querySelectorAll('.assignedPackages button')) {
    button.addEventListener('click', () => {
        packageName = button.getAttribute('data-package')
        const dialogMessage = dialog.querySelector('[data-template]')
        const template = dialogMessage.getAttribute('data-template')
        dialogMessage.textContent = template.replace('{}', packageName)
        dialog.showModal()
    })
}

const dialog = document.querySelector('dialog.confirmRemovePackage')
dialog.addEventListener('close', async () => {
    if (dialog.returnValue == 'yes') {
        const url = new URL(location.href)
        url.searchParams.set('package', packageName)
        const response = await fetch(url, { method: 'DELETE' })
        if (response.ok) {
            location.reload()
        } else {
            const newDocument = Document.parseHTMLUnsafe(await response.text())
            document.replaceChild(
                document.adoptNode(newDocument.documentElement, true),
                document.documentElement
            )
        }
    }
})
