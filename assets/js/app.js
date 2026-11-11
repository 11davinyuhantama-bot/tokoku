document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-confirm]');
  if (button && !confirm(button.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?')) {
    event.preventDefault();
  }
});

document.querySelectorAll('form[data-auto-search]').forEach(function (form) {
  const delay = Number(form.dataset.searchDelay || 600);
  let timer;

  form.querySelectorAll('[data-search-input]').forEach(function (input) {
    input.addEventListener('input', function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      }, delay);
    });
  });

  form.querySelectorAll('[data-search-filter]').forEach(function (filter) {
    filter.addEventListener('change', function () {
      window.clearTimeout(timer);
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  });
});
