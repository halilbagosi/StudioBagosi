(function () {
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function renderGalleryCards(data, row) {
    row.innerHTML = data.sets
      .map(function (set) {
        var img =
          set.has_cover_photo && set.cover_image
            ? '<img src="' +
              escapeHtml(set.cover_image) +
              '" class="card-img-top" alt="' +
              escapeHtml(set.name) +
              '">'
            : '';
        return (
          '<div class="col-md-4 mb-4">' +
          '<div class="card gallery-card">' +
          img +
          '<div class="card-body">' +
          '<h5 class="card-title">' +
          escapeHtml(set.name) +
          '</h5>' +
          '<p class="card-text">' +
          escapeHtml(set.description) +
          '</p>' +
          '<p class="card-text"><small class="text-muted">' +
          String(set.image_count) +
          ' imazhe</small></p>' +
          '<a href="gallery.html?id=' +
          encodeURIComponent(String(set.id)) +
          '" class="btn btn-primary"><i class="fas fa-images"></i> Shiko Galerinë</a>' +
          '</div></div></div>'
        );
      })
      .join('');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var row = document.getElementById('gallerySetsRow');
    var api = window.STUDIO_BAGOSI_GALLERY;
    if (!row || !api || typeof api.load !== 'function') return;

    api
      .load()
      .then(function () {
        var data = window.STUDIO_BAGOSI_GALLERY;
        if (!data.sets || !data.sets.length) return;
        renderGalleryCards(data, row);
      })
      .catch(function (err) {
        console.error('Gallery load failed:', err);
        row.innerHTML =
          '<div class="col-12"><p class="text-muted text-center small">Galeria nuk u ngarkua. Hapni faqen përmes një serveri lokal (p.sh. <code>python -m http.server</code>) ose përmes GitHub Pages.</p></div>';
      });
  });
})();
