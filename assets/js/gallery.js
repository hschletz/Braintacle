---
---

let pswpElement = document.querySelector('.pswp')

let items = [
{% for image in site.data.gallery %}
    {
        src: 'assets/screenshots/{{ image.filename }}',
        title: '{{ image.title | escape }}',
        w: {{ image.width }},
        h: {{ image.height }},
    },
{% endfor %}
]

for (const link of document.querySelectorAll('.gallery a')) {
    link.addEventListener('click', event => {
        event.preventDefault()

        let figure = event.target.closest('figure')
        let options = {
            index: Array.from(figure.parentNode.children).indexOf(figure),
            closeOnScroll: false,
        }
    
        let gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options)
        gallery.init()
    })
}
