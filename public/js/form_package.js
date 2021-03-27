/**
 * Package builder form.
 */

 // Checkboxes are accompanied by a hidden input field with the same name. The
 // form DOM has a RadioNodeList containing both. The checkbox is at index 1.

for (const form of document.querySelectorAll('form.form_package')) {
    form.Platform.addEventListener('change', event => { changePlatform(event.target) })
    form.DeployAction.addEventListener('change', event => { changeParam(event.target) })
    form.Warn[1].addEventListener('change', event => { toggleWarn(event.target) })

    changePlatform(form.Platform)
    toggleWarn(form.Warn[1])
}

/**
 * Hide/display notification elements which have no effect on non-Windows
 * platforms.
 */
function changePlatform(platform)
{
    const form = platform.form
    if (platform.value == 'windows') {
        display(form.Warn, true)
        display(form.PostInstMessage, true)
        toggleWarn(form.Warn[1])
    } else {
        display(form.Warn, false)
        display(form.WarnMessage, false)
        display(form.WarnCountdown, false)
        display(form.WarnAllowAbort, false)
        display(form.WarnAllowDelay, false)
        display(form.PostInstMessage, false)
    }
}

/**
 * Change label of parameter input field according to selected action.
 */
function changeParam(deployAction)
{
    const actionParam = deployAction.form.ActionParam
    const labels = JSON.parse(actionParam.getAttribute('data-labels'))
    const label = labels[deployAction.value]
    const element = actionParam.parentNode.querySelector('span')
    element.textContent = label;
}

/**
 * Hide or display Warn* fields according to checked state of Warn element.
 */
function toggleWarn(warn)
{
    const form = warn.form
    const checked = warn.checked && form.Platform.value == 'windows'
    display(form.WarnMessage, checked)
    display(form.WarnCountdown, checked)
    display(form.WarnAllowAbort, checked)
    display(form.WarnAllowDelay, checked)
}

/**
 * Hide or display a block element.
 */
function display(element, display)
{
    if (element instanceof RadioNodeList) {
        element = element[1]
    }
    element.parentNode.style.display = display ? 'contents' : 'none';
}
