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
    var urls = selected.photo_urls || [];
    if (grid) {
      if (!urls.length) {
        grid.innerHTML =
          '<p class="text-muted text-center">Nuk ka foto në këtë galeri. Shtoni skedarë në dosjen e caktuar ose përditësoni <code>files</code> / <code>manifest.json</code>.</p>';
      } else {
        var altBase = selected.name || 'Gallery';
        grid.innerHTML = urls
          .map(function (src, idx) {
            var loading = idx < 2 ? 'eager' : 'lazy';
            var fetchPriority = idx === 0 ? ' fetchpriority="high"' : '';
            var alt = escapeHtml(altBase + ' — ' + (idx + 1));
            return (
              '<div class="gallery-item gallery-item--tile gallery-item--photo-only">' +
              '<img src="' +
              escapeHtml(src) +
              '" alt="' +
              alt +
              '" class="gallery-item-photo" loading="' +
              loading +
              '" decoding="async"' +
              fetchPriority +
              '></div>'
            );
          })
          .join('');
      }
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
