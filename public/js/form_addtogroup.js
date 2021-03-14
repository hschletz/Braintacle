/**
 * AddToGroup form.
 */

// Show/hide elements according to selected "Where" radio button
for (const radio of document.querySelectorAll('.form_addtogroup [name=Where]')) {
    radio.addEventListener('change', event => {
        onChange(event.target)
    })
}

// Show/hide elements according to initially selected radio button
onChange(document.querySelector('.form_addtogroup [name=Where]:checked'))

function onChange(element)
{
    const isNewGroup = (element.value == 'new');
    toggle(element.form['NewGroup'], isNewGroup)
    toggle(element.form['Description'], isNewGroup)
    toggle(element.form['ExistingGroup'], !isNewGroup)
    for (const error of element.form.querySelectorAll('.errors')) {
        error.style.display = isNewGroup ? 'block' : 'none'
    }
}

function toggle(element, show)
{
    element.parentNode.style.display = show ? 'block' : 'none'
}
