export function replaceDocument(html) {
    const newDocument = Document.parseHTMLUnsafe(html)
    document.replaceChild(
        document.adoptNode(newDocument.documentElement, true),
        document.documentElement
    )
}
