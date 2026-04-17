(function () {
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var data = window.STUDIO_BAGOSI_GALLERY;
    var row = document.getElementById('gallerySetsRow');
    if (!data || !row || !data.sets) return;

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
  });
})();
