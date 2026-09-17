/*
 * Grimdark Forge — shared client-side behaviour.
 *
 * 1. Locale toggle: the header button expands the list of locales.
 * 2. Locale-aware formatting: every price, count and date the server renders
 *    is re-formatted with Intl for the active <html lang>, so the browser and
 *    the server agree and the format follows the locale, not a hard-coded
 *    English/Australian pattern.
 * 3. Image previews for file inputs.
 *
 * Plain JavaScript, no libraries.
 */
(function () {
  'use strict';

  var locale = document.documentElement.lang || 'en-AU';

  /* ---------------------------------------------------------------------
     Formatting helpers, exposed for the module scripts.
     ------------------------------------------------------------------ */
  var GF = window.GF = window.GF || {};
  GF.locale = locale;

  GF.money = function (cents, currency) {
    try {
      return new Intl.NumberFormat(locale, { style: 'currency', currency: currency || 'AUD' }).format(cents / 100);
    } catch (err) {
      return (cents / 100).toFixed(2);
    }
  };

  GF.number = function (value, decimals) {
    try {
      return new Intl.NumberFormat(locale, {
        minimumFractionDigits: decimals || 0,
        maximumFractionDigits: decimals || 0
      }).format(value);
    } catch (err) {
      return String(value);
    }
  };

  GF.date = function (iso, style) {
    var d = parseIso(iso);
    if (!d) { return iso; }
    var opts = { day: 'numeric', month: 'long', year: 'numeric' };
    if (style === 'datetime') {
      opts.hour = 'numeric';
      opts.minute = '2-digit';
    }
    try {
      return new Intl.DateTimeFormat(locale, opts).format(d);
    } catch (err) {
      return iso;
    }
  };

  // Timestamps are stored without a zone and mean "site local time", so they
  // are parsed as local rather than UTC to avoid a day shift.
  function parseIso(iso) {
    var m = /^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2})(?::(\d{2}))?)?$/.exec(iso);
    if (!m) { return null; }
    return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
  }

  /* Re-format everything the server marked up. */
  GF.formatAll = function (root) {
    root = root || document;
    var i, els;

    els = root.querySelectorAll('[data-money]');
    for (i = 0; i < els.length; i++) {
      els[i].textContent = GF.money(parseInt(els[i].getAttribute('data-money'), 10), els[i].getAttribute('data-currency'));
    }

    els = root.querySelectorAll('[data-number]');
    for (i = 0; i < els.length; i++) {
      els[i].textContent = GF.number(parseFloat(els[i].getAttribute('data-number')), parseInt(els[i].getAttribute('data-decimals') || '0', 10));
    }

    els = root.querySelectorAll('time[datetime][data-format]');
    for (i = 0; i < els.length; i++) {
      els[i].textContent = GF.date(els[i].getAttribute('datetime'), els[i].getAttribute('data-format'));
    }
  };

  /* ---------------------------------------------------------------------
     Locale toggle
     ------------------------------------------------------------------ */
  function initLocaleToggle() {
    var toggle = document.getElementById('locale-toggle');
    var menu = document.getElementById('locale-menu');
    if (!toggle || !menu) { return; }

    var form = toggle.closest('form');
    form.classList.add('locale-switch--js');

    function setOpen(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      form.classList.toggle('is-open', open);
      if (open) {
        var first = menu.querySelector('button');
        if (first) { first.focus(); }
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('click', function (event) {
      if (!form.contains(event.target)) { setOpen(false); }
    });

    form.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        setOpen(false);
        toggle.focus();
      }
    });
  }

  /* ---------------------------------------------------------------------
     File input previews. Any <input type="file" data-preview="#id"> shows the
     chosen image in that <img> and reports the file name beside it.
     ------------------------------------------------------------------ */
  function initFilePreviews() {
    var inputs = document.querySelectorAll('input[type="file"][data-preview]');
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].addEventListener('change', onFileChange);
    }
  }

  function onFileChange(event) {
    var input = event.target;
    var img = document.querySelector(input.getAttribute('data-preview'));
    var file = input.files && input.files[0];
    if (!img) { return; }
    if (!file || !/^image\//.test(file.type)) {
      return;
    }
    var reader = new FileReader();
    reader.onload = function () {
      img.src = reader.result;
      img.alt = input.getAttribute('data-preview-alt') || '';
    };
    reader.readAsDataURL(file);
  }

  /* ---------------------------------------------------------------------
     Focus the flash message / error summary when a page loads with one, so
     screen-reader users hear the outcome of what they just did.
     ------------------------------------------------------------------ */
  function focusStatus() {
    var errors = document.querySelector('.form-errors:not([hidden])');
    if (errors) { errors.focus(); }
  }

  document.addEventListener('DOMContentLoaded', function () {
    GF.formatAll();
    initLocaleToggle();
    initFilePreviews();
    focusStatus();
  });
})();
