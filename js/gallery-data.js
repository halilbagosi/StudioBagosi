/**
 * Loads gallery data from data/gallery.json (strict JSON — no PHP-style syntax).
 * Each set can use folder + files[], or folder + manifest.json, or legacy images[].
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

  function joinFolderFile(folder, file) {
    var f = (folder || '').replace(/\/+$/, '');
    var name = (file || '').replace(/^\//, '');
    if (!f) return name;
    if (!name) return f;
    return f + '/' + name;
  }

  /** Legacy or explicit list → array of URL paths relative to site root. */
  function urlsFromSetBeforeManifest(set) {
    if (set.folder && Array.isArray(set.files) && set.files.length) {
      return set.files.map(function (file) {
        return joinFolderFile(set.folder, file);
      });
    }
    if (!Array.isArray(set.images) || !set.images.length) return [];

    var first = set.images[0];
    if (typeof first === 'string') {
      return set.images.slice();
    }
    if (first && typeof first === 'object') {
      return set.images
        .map(function (im) {
          return im && (im.src || im.file) ? im.src || im.file : '';
        })
        .filter(Boolean);
    }
    return [];
  }

  function parseManifestJson(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.files)) return data.files;
    return [];
  }

  function fetchManifestFileList(folder) {
    var path = joinFolderFile(folder, 'manifest.json');
    var url = new URL(path, window.location.href);
    return fetch(url, { credentials: 'same-origin' }).then(function (res) {
      if (!res.ok) throw new Error('manifest ' + res.status);
      return res.json();
    });
  }

  function resolveSetPhotos(set) {
    var direct = urlsFromSetBeforeManifest(set);
    if (direct.length) {
      set.photo_urls = direct;
      return Promise.resolve(set);
    }

    if (set.folder) {
      return fetchManifestFileList(set.folder)
        .then(parseManifestJson)
        .then(function (files) {
          set.photo_urls = files.map(function (file) {
            return joinFolderFile(set.folder, file);
          });
          return set;
        })
        .catch(function () {
          set.photo_urls = [];
          return set;
        });
    }

    set.photo_urls = [];
    return Promise.resolve(set);
  }

  function normalizeImageCount(sets) {
    for (var i = 0; i < sets.length; i++) {
      var set = sets[i];
      var urls = set.photo_urls;
      set.image_count = Array.isArray(urls) ? urls.length : 0;
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
        return Promise.all(data.sets.map(resolveSetPhotos)).then(function (sets) {
          window.STUDIO_BAGOSI_GALLERY.sets = normalizeImageCount(sets);
          window.STUDIO_BAGOSI_GALLERY.getById = function (id) {
            return getById(window.STUDIO_BAGOSI_GALLERY.sets, id);
          };
          return window.STUDIO_BAGOSI_GALLERY;
        });
      });
  };
})();
