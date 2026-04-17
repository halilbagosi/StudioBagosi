(function () {
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var data = window.STUDIO_BAGOSI_GALLERY;
    if (!data || !data.sets || !data.sets.length) return;

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
      grid.innerHTML = selected.images
        .map(function (image) {
          return (
            '<div class="gallery-item">' +
            '<div class="placeholder-image">' +
            '<div class="text-center">' +
            '<i class="fas fa-image fa-3x mb-2"></i><br>' +
            escapeHtml(image.title) +
            '</div></div>' +
            '<div class="gallery-item-overlay">' +
            '<h5>' +
            escapeHtml(image.title) +
            '</h5>' +
            '<p class="mb-0">' +
            escapeHtml(image.description) +
            '</p></div></div>'
          );
        })
        .join('');
    }
  });
})();
