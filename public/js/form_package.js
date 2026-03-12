/**
 * Package builder form.
 */

const form = document.querySelector('form.form_package')
const platform = form['platform']
const action = form['action']
const actionParam = form['actionParam']
const file = form['file']
const warn = form['warn']
const warnMessage = form['warnMessage']
const warnCountdown = form['warnCountdown']
const warnAllowAbort = form['warnAllowAbort']
const warnAllowDelay = form['warnAllowDelay']
const postInstMessage = form['postInstMessage']

platform.addEventListener('change', changePlatform)
action.addEventListener('change', changeAction)
warn.addEventListener('change', toggleWarn)

changePlatform()
toggleWarn()

/**
 * Hide/display notification elements which have no effect on non-Windows
 * platforms.
 */
function changePlatform() {
    if (platform.value == 'windows') {
        display(warn, true)
        display(postInstMessage, true)
        toggleWarn()
    } else {
        display(warn, false)
        display(warnMessage, false)
        display(warnCountdown, false)
        display(warnAllowAbort, false)
        display(warnAllowDelay, false)
        display(postInstMessage, false)
    }
}

function changeAction() {
    // actionParamLabels is defined in template.
    const label = actionParamLabels[action.value == 'store' ? 'store' : 'launchExecute']
    const element = actionParam.parentNode.querySelector('span')
    element.textContent = label

    file.required = (action.value != 'execute')
}

/**
 * Hide or display warn* fields according to checked state of Warn element.
 */
function toggleWarn() {
    const checked = warn.checked && platform.value == 'windows'
    display(warnMessage, checked)
    display(warnCountdown, checked)
    display(warnAllowAbort, checked)
    display(warnAllowDelay, checked)
}

/**
 * Hide or display a block element.
 */
function display(element, display) {
    if (element instanceof RadioNodeList) {
        element = element[1]
    }
    element.parentNode.style.display = display ? 'contents' : 'none'
}
