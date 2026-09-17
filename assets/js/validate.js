/*
 * Client-side form validation.
 *
 * Every form with data-validate is checked before it is submitted. The rules
 * live on the controls as data-* attributes (no HTML validation attributes
 * such as required, pattern, min or maxlength are used anywhere), and the
 * same rules are enforced again by PHP in shared/validation.php — the
 * server is authoritative, this layer is for immediate feedback.
 *
 * Supported attributes on a control:
 *   data-required            value must not be empty (radio: one in the group checked)
 *   data-checked             checkbox must be ticked
 *   data-required-group      at least one checkbox with this name must be ticked
 *   data-minlength / data-maxlength
 *   data-type                int | email | date | card | expiry | csc | postcode | phone | username
 *   data-min / data-max      numeric bounds for data-type="int"
 *   data-same="otherName"    must equal the value of another control
 *   data-required-if-file    required once the named file input has a file
 *   data-max-bytes           (file inputs) maximum size
 *   data-accept              (file inputs) comma-separated MIME types
 *   data-label               name used in messages (defaults to the <label> text)
 *
 * Messages are shown inline in the control's #<name>-error element and in
 * the #form-errors summary, both rendered by the server so the layout is the
 * same whether JavaScript or PHP found the problem.
 */
(function () {
  'use strict';

  var MESSAGES = {
    'en-AU': {
      required: '%s is required.',
      checked: 'You need to tick "%s" to continue.',
      minlength: '%s must be at least %d characters.',
      maxlength: '%s must be no more than %d characters.',
      int: '%s must be a whole number.',
      min: '%s must be at least %d.',
      max: '%s must be no more than %d.',
      email: 'Enter a valid email address, for example name@example.com.',
      date: '%s must be a date in the form YYYY-MM-DD.',
      username: 'A username is 3 to 20 letters, digits or underscores and starts with a letter.',
      postcode: 'A postcode is four digits, for example 3000.',
      phone: 'Enter a phone number using digits, spaces and an optional leading +.',
      card: 'Enter a valid card number. The test number 4111 1111 1111 1111 works.',
      expiry: 'Expiry must be MM/YY, for example 04/29.',
      expired: 'This card has expired.',
      csc: 'The security code is the three or four digits on the card.',
      same: '%s does not match.',
      filesize: 'Images must be %s MB or smaller.',
      filetype: 'Attach a JPEG, PNG, GIF or WebP image.',
      summary: 'Please fix the following before continuing'
    },
    'de-DE': {
      required: '%s ist erforderlich.',
      checked: 'Bitte „%s“ ankreuzen, um fortzufahren.',
      minlength: '%s muss mindestens %d Zeichen lang sein.',
      maxlength: '%s darf höchstens %d Zeichen lang sein.',
      int: '%s muss eine ganze Zahl sein.',
      min: '%s muss mindestens %d sein.',
      max: '%s darf höchstens %d sein.',
      email: 'Geben Sie eine gültige E-Mail-Adresse ein, z. B. name@example.com.',
      date: '%s muss ein Datum im Format JJJJ-MM-TT sein.',
      username: 'Ein Benutzername besteht aus 3 bis 20 Buchstaben, Ziffern oder Unterstrichen und beginnt mit einem Buchstaben.',
      postcode: 'Eine Postleitzahl besteht aus vier Ziffern, z. B. 3000.',
      phone: 'Geben Sie eine Telefonnummer mit Ziffern, Leerzeichen und optionalem führendem + ein.',
      card: 'Geben Sie eine gültige Kartennummer ein. Die Testnummer 4111 1111 1111 1111 funktioniert.',
      expiry: 'Das Ablaufdatum muss MM/JJ sein, z. B. 04/29.',
      expired: 'Diese Karte ist abgelaufen.',
      csc: 'Die Prüfnummer sind die drei oder vier Ziffern auf der Karte.',
      same: '%s stimmt nicht überein.',
      filesize: 'Bilder dürfen höchstens %s MB groß sein.',
      filetype: 'Hängen Sie ein JPEG-, PNG-, GIF- oder WebP-Bild an.',
      summary: 'Bitte beheben Sie Folgendes, bevor Sie fortfahren'
    }
  };

  var locale = document.documentElement.lang || 'en-AU';
  var M = MESSAGES[locale] || MESSAGES['en-AU'];

  function msg(key, a, b) {
    var out = M[key] || MESSAGES['en-AU'][key] || key;
    return out.replace('%s', a).replace('%d', b);
  }

  /* ------------------------------------------------------------------ */

  function labelFor(control) {
    if (control.getAttribute('data-label')) { return control.getAttribute('data-label'); }
    var label = control.id ? document.querySelector('label[for="' + control.id + '"]') : null;
    if (!label && control.type === 'radio') {
      var fs = control.closest('fieldset');
      label = fs ? fs.querySelector('legend') : null;
    }
    if (!label) { return control.name; }
    // Strip the visual asterisk and whitespace.
    return label.textContent.replace('*', '').replace(/\s+/g, ' ').trim();
  }

  function luhn(digits) {
    var sum = 0, alt = false;
    for (var i = digits.length - 1; i >= 0; i--) {
      var n = parseInt(digits.charAt(i), 10);
      if (alt) { n *= 2; if (n > 9) { n -= 9; } }
      sum += n;
      alt = !alt;
    }
    return sum % 10 === 0;
  }

  /* Validate one control. Returns a message or ''. */
  function check(control, form) {
    var label = labelFor(control);
    var value;

    if (control.type === 'checkbox') {
      if (control.hasAttribute('data-checked') && !control.checked) { return msg('checked', label); }
      // A group of checkboxes (name ending in []) where at least one is needed.
      if (control.hasAttribute('data-required-group')) {
        var boxes = form.querySelectorAll('input[type="checkbox"][name="' + control.name + '"]');
        for (var b = 0; b < boxes.length; b++) { if (boxes[b].checked) { return ''; } }
        return msg('required', control.getAttribute('data-label') || label);
      }
      return '';
    }

    if (control.type === 'radio') {
      if (control.hasAttribute('data-required')) {
        var group = form.querySelectorAll('input[type="radio"][name="' + control.name + '"]');
        for (var g = 0; g < group.length; g++) { if (group[g].checked) { return ''; } }
        return msg('required', label);
      }
      return '';
    }

    if (control.type === 'file') {
      var file = control.files && control.files[0];
      if (!file) { return control.hasAttribute('data-required') ? msg('required', label) : ''; }
      var maxBytes = parseInt(control.getAttribute('data-max-bytes') || '0', 10);
      if (maxBytes && file.size > maxBytes) { return msg('filesize', (maxBytes / 1048576).toFixed(1)); }
      var accept = control.getAttribute('data-accept');
      if (accept && accept.split(',').indexOf(file.type) === -1) { return msg('filetype'); }
      return '';
    }

    value = control.value.trim();

    if (value === '') {
      if (control.hasAttribute('data-required')) { return msg('required', label); }
      // Conditionally required: e.g. an image description once a file is chosen.
      var dep = control.getAttribute('data-required-if-file');
      if (dep) {
        var fileInput = form.querySelector('[name="' + dep + '"]');
        if (fileInput && fileInput.files && fileInput.files.length) { return msg('required', label); }
      }
      return '';
    }

    var minlength = parseInt(control.getAttribute('data-minlength') || '0', 10);
    var maxlength = parseInt(control.getAttribute('data-maxlength') || '0', 10);
    if (minlength && value.length < minlength) { return msg('minlength', label, minlength); }
    if (maxlength && value.length > maxlength) { return msg('maxlength', label, maxlength); }

    var same = control.getAttribute('data-same');
    if (same) {
      var other = form.querySelector('[name="' + same + '"]');
      if (other && other.value.trim() !== value) { return msg('same', label); }
    }

    switch (control.getAttribute('data-type')) {
      case 'int':
        if (!/^-?\d+$/.test(value)) { return msg('int', label); }
        var n = parseInt(value, 10);
        if (control.hasAttribute('data-min') && n < parseInt(control.getAttribute('data-min'), 10)) {
          return msg('min', label, control.getAttribute('data-min'));
        }
        if (control.hasAttribute('data-max') && n > parseInt(control.getAttribute('data-max'), 10)) {
          return msg('max', label, control.getAttribute('data-max'));
        }
        break;
      case 'email':
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { return msg('email'); }
        break;
      case 'date':
        var dm = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!dm) { return msg('date', label); }
        var d = new Date(+dm[1], +dm[2] - 1, +dm[3]);
        if (d.getMonth() !== +dm[2] - 1 || d.getDate() !== +dm[3]) { return msg('date', label); }
        break;
      case 'username':
        if (!/^[a-z][a-z0-9_]{2,19}$/i.test(value)) { return msg('username'); }
        break;
      case 'postcode':
        if (!/^\d{4}$/.test(value)) { return msg('postcode'); }
        break;
      case 'phone':
        if (!/^\+?[\d\s()-]{8,20}$/.test(value)) { return msg('phone'); }
        break;
      case 'card':
        var digits = value.replace(/\D/g, '');
        if (digits.length < 13 || digits.length > 19 || !luhn(digits)) { return msg('card'); }
        break;
      case 'expiry':
        var em = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(value);
        if (!em) { return msg('expiry'); }
        var expiry = new Date(2000 + +em[2], +em[1], 1);
        if (expiry < new Date()) { return msg('expired'); }
        break;
      case 'csc':
        if (!/^\d{3,4}$/.test(value)) { return msg('csc'); }
        break;
    }
    return '';
  }

  /* ------------------------------------------------------------------ */

  // qty[12] -> qty-12, matching the ids PHP renders for its error elements.
  function errorKey(control) {
    return control.name.replace(/\[\]$/, '').replace(/\[/g, '-').replace(/\]/g, '');
  }

  function errorElement(control) {
    return document.getElementById(errorKey(control) + '-error');
  }

  function showError(control, text) {
    var el = errorElement(control);
    if (el) {
      el.textContent = text;
      el.hidden = text === '';
    }
    var targets = (control.type === 'radio' || control.type === 'checkbox')
      ? control.form.querySelectorAll('input[name="' + control.name + '"]')
      : [control];
    for (var i = 0; i < targets.length; i++) {
      if (text) {
        targets[i].setAttribute('aria-invalid', 'true');
        var ids = (targets[i].getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (el && ids.indexOf(el.id) === -1) { ids.unshift(el.id); }
        if (ids.length) { targets[i].setAttribute('aria-describedby', ids.join(' ')); }
      } else {
        targets[i].removeAttribute('aria-invalid');
      }
    }
  }

  function renderSummary(form, errors) {
    var box = form.querySelector('.form-errors') || document.getElementById('form-errors');
    if (!box) { return; }
    var list = box.querySelector('.form-errors__list');
    list.innerHTML = '';
    var names = Object.keys(errors);
    if (!names.length) {
      box.hidden = true;
      return;
    }
    for (var i = 0; i < names.length; i++) {
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#' + names[i];
      a.textContent = errors[names[i]];
      li.appendChild(a);
      list.appendChild(li);
    }
    box.hidden = false;
    box.focus();
  }

  function controlsOf(form) {
    var all = form.querySelectorAll('input, select, textarea');
    var seen = {};
    var out = [];
    for (var i = 0; i < all.length; i++) {
      var c = all[i];
      if (!c.name || c.type === 'hidden' || c.type === 'submit' || c.type === 'button') { continue; }
      if ((c.type === 'radio' || c.type === 'checkbox') && seen[c.name]) { continue; }
      seen[c.name] = true;
      out.push(c);
    }
    return out;
  }

  function validateForm(form) {
    var errors = {};
    var controls = controlsOf(form);
    for (var i = 0; i < controls.length; i++) {
      var text = check(controls[i], form);
      showError(controls[i], text);
      if (text) { errors[controls[i].id || errorKey(controls[i])] = text; }
    }
    renderSummary(form, errors);
    return Object.keys(errors).length === 0;
  }

  function initForm(form) {
    form.setAttribute('novalidate', '');
    var attempted = false;

    form.addEventListener('submit', function (event) {
      attempted = true;
      if (!validateForm(form)) {
        event.preventDefault();
        return;
      }
      // Guard against double submission (e.g. placing an order twice).
      var buttons = form.querySelectorAll('button[type="submit"]');
      for (var i = 0; i < buttons.length; i++) { buttons[i].disabled = true; }
    });

    // After the first attempt, re-check a field as soon as it is corrected.
    form.addEventListener('input', function (event) {
      if (attempted && event.target.name) { showError(event.target, check(event.target, form)); }
    });
    form.addEventListener('change', function (event) {
      if (attempted && event.target.name) { showError(event.target, check(event.target, form)); }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-validate]');
    for (var i = 0; i < forms.length; i++) { initForm(forms[i]); }
  });

  window.GF = window.GF || {};
  window.GF.validateForm = validateForm;
})();
