document.addEventListener('click', function(event) {
    const button = event.target.closest('.password-toggle');
    if (!button) {
        return;
    }

    const wrapper = button.closest('.password-wrap');
    const input = wrapper ? wrapper.querySelector('input') : null;
    if (!input) {
        return;
    }

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    button.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
    button.classList.toggle('is-visible', isHidden);
});
