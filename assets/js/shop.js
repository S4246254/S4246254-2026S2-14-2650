/*
 * Shopping cart module — client-side behaviour.
 *
 * Recomputes line totals and the subtotal as the shopper changes a variant
 * or quantity, formatted for the active locale. This is a preview only: the
 * server recalculates every figure from its own product data when the form
 * is submitted, and never accepts a price from the browser.
 */
(function () {
  'use strict';

  function unitCents(row, select) {
    var base = parseInt(row.getAttribute('data-base-cents') || '0', 10);
    var opt = select && select.options[select.selectedIndex];
    var extra = opt ? parseInt(opt.getAttribute('data-extra-cents') || '0', 10) : 0;
    return base + extra;
  }

  /* Product page: one line preview. */
  function initProductForm() {
    var form = document.getElementById('add-form');
    if (!form) { return; }
    var select = form.querySelector('select[name="variant"]');
    var qty = form.querySelector('input[name="quantity"]');
    var out = document.getElementById('line-total');
    if (!select || !qty || !out) { return; }

    function update() {
      var n = parseInt(qty.value, 10);
      if (!(n > 0)) { n = 0; }
      var cents = unitCents(form, select) * n;
      out.textContent = window.GF.money(cents, 'AUD');
    }
    select.addEventListener('change', update);
    qty.addEventListener('input', update);
  }

  /* Cart page: every row plus the subtotal. */
  function initCartTable() {
    var form = document.getElementById('cart-form');
    if (!form) { return; }
    var rows = form.querySelectorAll('tr[data-line]');
    var subtotalCell = form.querySelector('[data-subtotal]');

    function updateRow(row) {
      var select = row.querySelector('select');
      var qty = row.querySelector('input[inputmode="numeric"]');
      var n = parseInt(qty.value, 10);
      if (!(n > 0)) { n = 0; }
      var unit = unitCents(row, select);
      row.querySelector('[data-unit]').textContent = window.GF.money(unit, 'AUD');
      row.querySelector('[data-line-total]').textContent = window.GF.money(unit * n, 'AUD');
      return unit * n;
    }

    function updateAll() {
      var sum = 0;
      for (var i = 0; i < rows.length; i++) { sum += updateRow(rows[i]); }
      if (subtotalCell) { subtotalCell.textContent = window.GF.money(sum, 'AUD'); }
    }

    form.addEventListener('change', updateAll);
    form.addEventListener('input', updateAll);
  }

  /* Checkout: swap the delivery line as the method changes. */
  function initCheckout() {
    var radios = document.querySelectorAll('input[name="shipping"][data-cents]');
    var deliveryOut = document.querySelector('[data-delivery]');
    var totalOut = document.querySelector('[data-total]');
    var gstOut = document.querySelector('[data-gst]');
    var goods = document.querySelector('[data-goods-cents]');
    if (!radios.length || !deliveryOut || !totalOut || !goods) { return; }
    var goodsCents = parseInt(goods.getAttribute('data-goods-cents'), 10);
    var freeOver = parseInt(goods.getAttribute('data-free-over') || '0', 10);
    var freeLabel = goods.getAttribute('data-free-label') || 'Free';

    function update() {
      var chosen = null;
      for (var i = 0; i < radios.length; i++) { if (radios[i].checked) { chosen = radios[i]; } }
      if (!chosen) { return; }
      var delivery = parseInt(chosen.getAttribute('data-cents'), 10);
      if (chosen.value === 'standard' && freeOver && goodsCents >= freeOver) { delivery = 0; }
      var total = goodsCents + delivery;
      deliveryOut.textContent = delivery === 0 ? freeLabel : window.GF.money(delivery, 'AUD');
      totalOut.textContent = window.GF.money(total, 'AUD');
      if (gstOut) { gstOut.textContent = window.GF.money(Math.round(total / 11), 'AUD'); }
      var placeLabel = document.querySelector('[data-place-total]');
      if (placeLabel) { placeLabel.textContent = window.GF.money(total, 'AUD'); }
    }
    for (var i = 0; i < radios.length; i++) { radios[i].addEventListener('change', update); }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initProductForm();
    initCartTable();
    initCheckout();
  });
})();
