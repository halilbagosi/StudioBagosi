/**
 * Loads gallery data from data/gallery.json (strict JSON — no PHP-style syntax).
 * Exposes window.STUDIO_BAGOSI_GALLERY with { sets } and getById(id).
 */
(function () {
  function getById(sets, id) {
    var n = Number(id);
    for (var i = 0; i < sets.length; i++) {
      if (sets[i].id === n) return sets[i];
    }
    return null;
  }

  /** Set image_count from images.length so the UI stays in sync with the JSON list. */
  function normalizeSets(sets) {
    for (var i = 0; i < sets.length; i++) {
      var set = sets[i];
      var n = Array.isArray(set.images) ? set.images.length : 0;
      set.image_count = n;
    }
    return sets;
  }

  window.STUDIO_BAGOSI_GALLERY = { sets: [], getById: function () {} };

  window.STUDIO_BAGOSI_GALLERY.load = function () {
    var url = new URL('data/gallery.json', window.location.href);
    return fetch(url, { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) throw new Error('Gallery data HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        if (!data || !Array.isArray(data.sets)) throw new Error('Invalid gallery JSON');
        window.STUDIO_BAGOSI_GALLERY.sets = normalizeSets(data.sets);
        window.STUDIO_BAGOSI_GALLERY.getById = function (id) {
          return getById(window.STUDIO_BAGOSI_GALLERY.sets, id);
        };
        return window.STUDIO_BAGOSI_GALLERY;
      });
  };
})();
