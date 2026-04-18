(function () {
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function renderGalleryPage(data) {
    if (!data.sets || !data.sets.length) return;

    var params = new URLSearchParams(window.location.search);
    var rawId = params.get('id');
    var id = rawId !== null && rawId !== '' ? parseInt(rawId, 10) : NaN;
    if (Number.isNaN(id)) id = data.sets[0].id;

    var selected = data.getById ? data.getById(id) : null;
    if (!selected) selected = data.sets[0];

    document.title = 'Studio Bagosi - ' + selected.name;

    var bc = document.getElementById('galleryBreadcrumbCurrent');
    if (bc) bc.textContent = selected.name;

    var h1 = document.getElementById('galleryHeaderTitle');
    if (h1) h1.textContent = selected.name;

    var lead = document.getElementById('galleryHeaderDescription');
    if (lead) lead.textContent = selected.description;

    var navWrap = document.getElementById('gallerySetNav');
    if (navWrap) {
      navWrap.innerHTML = data.sets
        .map(function (set) {
          var active = set.id === selected.id;
          var cls = active ? 'btn btn-primary' : 'btn btn-outline-primary';
          return (
            '<a href="gallery.html?id=' +
            encodeURIComponent(String(set.id)) +
            '" class="' +
            cls +
            '">' +
            escapeHtml(set.name) +
            '</a>'
          );
        })
        .join('');
    }

    var grid = document.getElementById('galleryImageGrid');
    if (grid && selected.images) {
      var coverFallback = selected.cover_image || '';
      grid.innerHTML = selected.images
        .map(function (image, idx) {
          var src = image.src || image.file || coverFallback;
          var alt = escapeHtml(image.title);
          var loading = idx < 2 ? 'eager' : 'lazy';
          var fetchPriority = idx === 0 ? ' fetchpriority="high"' : '';
          return (
            '<div class="gallery-item gallery-item--tile">' +
            '<img src="' +
            escapeHtml(src) +
            '" alt="' +
            alt +
            '" class="gallery-item-photo" loading="' +
            loading +
            '" decoding="async"' +
            fetchPriority +
            '>' +
            '<div class="gallery-item-overlay">' +
            '<div class="gallery-item-overlay-inner">' +
            '<h5>' +
            escapeHtml(image.title) +
            '</h5>' +
            '<p class="mb-0">' +
            escapeHtml(image.description) +
            '</p></div></div></div>'
          );
        })
        .join('');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var api = window.STUDIO_BAGOSI_GALLERY;
    if (!api || typeof api.load !== 'function') return;

    api
      .load()
      .then(function () {
        renderGalleryPage(window.STUDIO_BAGOSI_GALLERY);
      })
      .catch(function (err) {
        console.error('Gallery load failed:', err);
        var grid = document.getElementById('galleryImageGrid');
        if (grid) {
          grid.innerHTML =
            '<p class="text-muted text-center">Galeria nuk u ngarkua. Përdorni një server lokal ose GitHub Pages.</p>';
        }
      });
  });
})();
