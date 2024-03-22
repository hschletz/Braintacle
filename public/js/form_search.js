/**
 * Search form.
 */

/**
 * Set search element type according to selected filter.
 */
function setSearchValueType() {
    searchForm['search'].type = (types[filterElement.value] == 'date') ? 'date' : 'text'
}

const searchForm = document.querySelector('.form_search')
const filterElement = searchForm['filter']
const types = JSON.parse(filterElement.getAttribute('data-types')) // map of filters to ordinal types (text filters are not present in map)

// Initialize search value element according to filter.
setSearchValueType()

filterElement.addEventListener('change', () => {
    // Update elements according to selected filter.
    setSearchValueType()

    const operatorElement = searchForm['operator']
    const operators = JSON.parse(
        operatorElement.getAttribute(
            types.hasOwnProperty(filterElement.value) ? 'data-operators-ordinal' : 'data-operators-text'
        )
    )

    while (operatorElement.options.length) {
        operatorElement.remove(0)
    }
    for (const key of Object.keys(operators)) {
        operatorElement.add(new Option(operators[key], key))
    }
})
