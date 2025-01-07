let packageName
let action

for (const button of document.querySelectorAll('.assignedPackages button')) {
    button.addEventListener('click', () => {
        packageName = button.getAttribute('data-package')
        action = button.getAttribute('data-action') || 'remove'
        const template = document.querySelector(
            action == 'reset' ? '#confirmResetPackage' : '#confirmRemovePackage'
        ).content.textContent
        const dialogMessage = dialog.querySelector('p')
        dialogMessage.textContent = template.replace('{}', packageName)
        dialog.showModal()
    })
}

const dialog = document.querySelector('dialog.confirmPackageAction')
dialog.addEventListener('close', async () => {
    if (dialog.returnValue == 'yes') {
        const url = new URL(location.href)
        url.searchParams.set('package', packageName)
        const response = await fetch(url, { method: action == 'reset' ? 'PUT' : 'DELETE' })
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
