# Grimdark Forge — COSC3046 Assessment Task 1

A static HTML and CSS storefront and community site for a fictional miniature wargaming
shop, built as an individual submission covering all four assessment modules.

## Team members, modules and hosting

Site entry point: <https://titan.csit.rmit.edu.au/~s4246254/welcome.html>

| Name | Student ID | Module responsible for | Hosted at |
|---|---|---|---|
| Augustus Zibell-Barnes | S4246254 | Discussion Forum | <https://titan.csit.rmit.edu.au/~s4246254/forum/boards.html> |
| Augustus Zibell-Barnes | S4246254 | Shopping Cart | <https://titan.csit.rmit.edu.au/~s4246254/shop/catalogue.html> |
| Augustus Zibell-Barnes | S4246254 | Blog | <https://titan.csit.rmit.edu.au/~s4246254/blog/posts.html> |
| Augustus Zibell-Barnes | S4246254 | Product Review & Rating | <https://titan.csit.rmit.edu.au/~s4246254/reviews/reviews.html> |

All four modules are delivered by a single student. The four CoreTeaching servers
(`titan`, `saturn`, `jupiter`, `coreteaching04`) share one home directory, so the same
files are reachable through any of them; `titan` is named here for consistency.

Group process decisions required by the brief — ownership of the shared site shell,
and the agreed locale-switching mechanism — are documented in [CHARTER.md](CHARTER.md).

## Modules

| Module | Folder | Pages | Self-audit statement |
|---|---|---|---|
| Discussion Forum | `forum/` | `boards.html`, `thread.html`, `new-thread.html`, `edit-post.html`, `delete-post.html` | `forum/S4246254_self_audit_statement.md` |
| Shopping Cart | `shop/` | `catalogue.html`, `product.html`, `cart.html`, `checkout.html`, `order-confirmation.html` | `shop/S4246254_self_audit_statement.md` |
| Blog | `blog/` | `posts.html`, `post.html`, `new-post.html`, `edit-post.html`, `delete-post.html` | `blog/S4246254_self_audit_statement.md` |
| Product Review & Rating | `reviews/` | `reviews.html`, `review.html`, `new-review.html`, `edit-review.html`, `delete-review.html` | `reviews/S4246254_self_audit_statement.md` |

Shared pages at the repository root: `welcome.html` (landing), `categories.html`,
`about.html`, `account.html` (sign-in and demo account switcher) and `sitemap.html`.
**25 pages in total.** There is no `index.html` or `home.html`, as the brief requires.

## File structure

```text
/
  README.md             CHARTER.md
  welcome.html          categories.html      about.html
  account.html          sitemap.html
  assets/
    css/base.css        design tokens, reset, typography, focus ring
    css/layout.css      header, navigation, page grids, footer
    css/components.css  buttons, cards, forms, tables, ratings, threads
    img/*.svg           28 hand-written SVG files
    img/hero-tabletop.jpg  hero photograph, stored locally (see Image credits)
  forum/    5 pages + self-audit statement
  shop/     5 pages + self-audit statement
  blog/     5 pages + self-audit statement
  reviews/  5 pages + self-audit statement
```

## Constraints observed

- **No JavaScript.** No `<script>` element appears anywhere in the project.
- **No third-party frameworks.** Every CSS rule was written by hand; there is no
  Bootstrap, Tailwind or other library.
- **No inline `style` attributes.** All presentation lives in the three stylesheets.
- **No external font request.** The font stack names Inter and Roboto first and falls
  through to the platform UI font, so nothing is fetched over the network.
- **No remote requests at all.** Every stylesheet, image and font resolves locally, so
  the site renders identically with the network disconnected. Verified by searching all
  25 pages for `http://` and `https://`: the only matches are the SVG namespace
  identifier (`www.w3.org/2000/svg`, which is never fetched) and one credit URL inside
  an HTML comment.

## Image credits

| Asset | Source |
|---|---|
| `assets/img/hero-tabletop.jpg` | Photograph by **Robert Coelho**, via [Unsplash](https://unsplash.com/photos/six-assorted-color-dice-laNNTAth9vs), used under the Unsplash Licence. Downloaded and stored locally at 1200 × 900. |
| `assets/img/*.svg` (28 files) | Written by hand for this assignment. No text is baked into any of them. |

The credit is repeated in a comment above the `<img>` in `welcome.html` and in the
"About this site" section of `about.html`.

## Accessibility approach

- One `<h1>` per page, with no skipped heading levels, and every page's first heading at
  level one.
- Landmarks (`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>`) rather
  than styled containers. Where a page has several `<nav>` elements they each carry a
  distinct accessible name.
- A `<label for="…">` bound to every form control, with constraints explained through
  `aria-describedby` rather than discovered on submission.
- A visible focus ring defined once in `base.css` and never removed; a skip link that
  appears on the first Tab press.
- Descriptive `alt` on every meaningful image and empty `alt` on decorative ones.
- No information carried by colour alone — ownership badges accompany coloured borders,
  and star ratings always state their value as text.
- Dates as `<time datetime="…">`; prices as marked-up text, never baked into an image.
- `autocomplete` on contact and delivery fields. The four payment fields on
  `shop/checkout.html` deliberately use `autocomplete="off"` instead — see
  `shop/S4246254_self_audit_statement.md` for the reasoning.

## How the ownership requirement is demonstrated

The site is static, so there is no real authentication. One of four accounts —
Kaya Ellery — is treated as signed in throughout. The shared header names that account
and links to `account.html`, so the current user is identifiable from every page without
opening it. Content written by that account carries a "Your post" or "Your review" badge
together with **Edit** and **Delete** controls; everyone else's content shows **Report**
instead. `account.html` explains this and lets a marker switch between the four demo
profiles.

This is a display convention, not enforcement: the edit and delete pages can still be
reached by typing their addresses, because nothing server-side exists to check the
request. Each module's self-audit statement says so explicitly.

## What does not work, and why

Nothing on the site submits or persists anything. Forms are fully marked up and labelled
but carry no `action`, and the primary controls are links, so no data is ever sent —
which also means no card number can end up in a URL or a server log. Cart totals,
stock levels and review counts are fixed text; "Update cart" cannot recalculate because
arithmetic needs scripting the brief excludes.

Each module's self-audit statement names a different genuine gap:

- **Forum** — the image preview beside the file input is a fixed placeholder whose `alt`
  text claims to describe the selected file.
- **Shop** — every monetary figure is static, and the per-line remove buttons share a
  form with the quantity fields because HTML forbids nesting forms.
- **Blog** — comment-level Edit and Delete link to the post-level pages, because the
  module is capped at five pages.
- **Reviews** — ownership is displayed but not enforced.

## Verification performed

Checked with scripted greps across all 25 pages:

- No `<script>`, framework reference, or inline `style` attribute.
- Exactly one `<h1>` per page; every page's first heading at level one.
- Every form control bound to a matching `<label for="…">`.
- Every `<img>` carries an `alt` attribute.
- No duplicate `id` values within a page.
- Every internal link and image source resolves to a file that exists.
- Every `aria-describedby` and `aria-labelledby` points at an `id` that exists on the
  same page.
- Every class used in the HTML is defined in one of the three stylesheets.
- Tag nesting parses cleanly on all 25 pages, with no unclosed and no stray end tags.
- No positive `tabindex` anywhere, so focus order follows document order; the skip link
  is the first focusable element on every page; no focusable element is hidden behind
  `aria-hidden="true"`.
- Cart arithmetic recomputed in integer cents: 4200 + 899 + 3450 = 8549 subtotal, plus
  995 delivery = 9544 total, GST 9544 ÷ 11 = 868. All match the figures shown.
- Review breakdown recomputed: 600 + 400 + 180 + 70 + 34 = 1284, weighted average 4.14.
- Each self-audit statement is within the required 200–300 words and its declared word
  count matches the file: forum 295, blog 298, reviews 283, shop 276.
- Pages rendered in headless Chrome at 1280 px and the screenshots inspected, since the
  static checks cannot see layout.

**Not yet performed:** the W3C markup validator and a Lighthouse accessibility pass. The
nesting and focus-order checks above approximate the structural half of what the
validator reports, but neither substitutes for it. Both should be run against the hosted
copy before submission.
