(function () {
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function renderGalleryCards(data, row) {
    row.innerHTML = data.sets
      .map(function (set, idx) {
        var cover =
          set.has_cover_photo && set.cover_image ? escapeHtml(set.cover_image) : '';
        var alt = escapeHtml(set.name);
        var loading = idx < 2 ? 'eager' : 'lazy';
        var fetchPriority = idx === 0 ? ' fetchpriority="high"' : '';
        var img = cover
          ? '<img src="' +
            cover +
            '" class="gallery-card-img" alt="' +
            alt +
            '" loading="' +
            loading +
            '" decoding="async"' +
            fetchPriority +
            '>'
          : '<div class="gallery-card-placeholder" role="img" aria-label="' +
            alt +
            '"><i class="fas fa-images fa-3x"></i></div>';
        return (
          '<div class="col-md-4 mb-4">' +
          '<a href="gallery.html?id=' +
          encodeURIComponent(String(set.id)) +
          '" class="gallery-card-link text-decoration-none">' +
          '<article class="gallery-card gallery-card--preview">' +
          '<div class="gallery-card-media">' +
          img +
          '</div>' +
          '<div class="gallery-card-overlay">' +
          '<div class="gallery-card-overlay-inner">' +
          '<h3 class="gallery-card-title">' +
          escapeHtml(set.name) +
          '</h3>' +
          '<p class="gallery-card-desc">' +
          escapeHtml(set.description) +
          '</p>' +
          '<p class="gallery-card-meta"><span>' +
          String(set.image_count) +
          ' imazhe</span></p>' +
          '<span class="gallery-card-cta"><i class="fas fa-images me-2"></i>Shiko Galerinë</span>' +
          '</div></div></article></a></div>'
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
