// operatorsText is defined in template because of label translations.
// filterTypes is defined in template because of generated values.
const operatorsOrdinal = {
    eq: '=',
    ne: '!=',
    lt: '<',
    le: '<=',
    ge: '>=',
    gt: '>',
}

const searchForm = document.querySelector('.form_search')
const filterElement = searchForm['filter']

filterElement.addEventListener('change', () => {
    const searchElement = searchForm['search']
    const oldType = searchElement.type
    const newType = filterTypes[filterElement.value] ?? 'text'

    if (newType != oldType) {
        searchElement.value = null // existing value may be incompatible with new type
        searchElement.type = newType
        searchElement.required = (newType != 'text')

        // Rebuild operators if type has changed from text to ordinal (number/date) or vice versa.
        if (oldType == 'text' || newType == 'text') {
            const operatorElement = searchForm['operator']
            const operators = filterTypes.hasOwnProperty(filterElement.value) ? operatorsOrdinal : operatorsText
            operatorElement.options.length = 0
            for (const [value, text] of Object.entries(operators)) {
                operatorElement.add(new Option(text, value))
            }
        }
    }
})
