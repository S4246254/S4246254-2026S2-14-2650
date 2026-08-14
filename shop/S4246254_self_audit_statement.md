# Self-audit statement — Shopping Cart module

**Student ID:** S4246254
**Module:** Shopping Cart (`shop/catalogue.html`, `product.html`, `cart.html`, `checkout.html`, `order-confirmation.html`)

## What I checked

All five pages use `<html lang="en">`, one `<h1>`, and an unbroken heading sequence.
Structure is carried by landmark elements, and every `<nav>` has its own accessible name
so the primary, breadcrumb, pagination and footer navigations stay distinguishable.

Checkout was the focus. Its twenty-one controls all have a `<label for="…">`, grouped
into `<fieldset>` elements with legends, and every field carrying a constraint explains
it through `aria-describedby` rather than on submission. `autocomplete` is set on all
nine contact and delivery fields — `name`, `email`, `tel`, `address-line1`,
`address-line2`, `address-level2`, `address-level1`, `postal-code`, `country` — for
WCAG 1.3.5 Identify Input Purpose.

The four payment fields are a deliberate exception, carrying `autocomplete="off"` rather
than `cc-name`, `cc-number`, `cc-exp` and `cc-csc`. Those tokens are what prompt a
browser to offer the reader's real stored card, and nobody opening a demonstration store
should be one click from autofilling their own card into it. I judged that more important
than the conformance point, and scoped the loss to those four fields.

On `cart.html` the line-item table has a `<caption>`, `<thead>`, `<tfoot>` and `scope` on
every header cell, inside a scrolling container so the page body never scrolls sideways.
Icon-only remove buttons name their product. Totals were recomputed in integer cents:
4200 + 899 + 3450 = 8549, plus 995 delivery = 9544, GST 9544 ÷ 11 = 868. All match.

## A remaining gap

Every monetary figure is static text, so "Update cart" recalculates nothing. Because HTML
forbids nested forms, the per-line remove buttons share a form with the quantity fields
and would need server-side `name`/`value` handling to identify which was pressed.
Removing the card tokens also reduces rather than eliminates autofill, since browsers
infer card fields from labels too.

*Prose word count: 276, measured excluding headings and the metadata lines above.*
