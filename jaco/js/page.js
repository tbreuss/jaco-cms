const replace_internal_links = function () {
  document.querySelectorAll('a').forEach((anchor) => {
    const href = anchor.getAttribute('href');
    const str = '?action=page&file=';
    if (/:\/\//.test(href) === false) {
      anchor.setAttribute('href', str + href);
    }
  });
}

window.addEventListener('load', function () {
  replace_internal_links();
});
