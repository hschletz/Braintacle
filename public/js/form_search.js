/**
 * Search form.
 */

 // Set options for "operators" element according to selected filter
 const filter = document.querySelector('.form_search [name=filter]')
 filter.addEventListener('change', () => {
    const types = JSON.parse(filter.getAttribute('data-types')) // map of filters to ordinal types (text filters are not present in map)
    const operatorElement = filter.form['operator']
    const operators = JSON.parse(
        operatorElement.getAttribute(
            types.hasOwnProperty(filter.value) ? 'data-operators-ordinal' : 'data-operators-text'
        )
    )

    while (operatorElement.options.length) {
        operatorElement.remove(0)
    }
    for (const key of Object.keys(operators)) {
        operatorElement.add(new Option(operators[key], key))
    }
})
