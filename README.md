# Grimdark Forge — COSC3046 Assessment Task 2

A dynamic PHP + JavaScript prototype of a miniature wargaming store and community
site, evolved from the Assessment Task 1 static site. Every module is fully
functional: data is created, read, updated and deleted through PHP, held in one
server-wide in-memory store shared by every visitor (no database), and validated in
both the browser and on the server.

## Team members, modules and hosting

Site entry point: <https://titan.csit.rmit.edu.au/~s4246254/welcome.php>

| Name | Student ID | Module responsible for | Hosted at |
|---|---|---|---|
| Augustus Zibell-Barnes | S4246254 | Discussion Forum | <https://titan.csit.rmit.edu.au/~s4246254/forum/boards.php> |
| Augustus Zibell-Barnes | S4246254 | Shopping Cart | <https://titan.csit.rmit.edu.au/~s4246254/shop/catalogue.php> |
| Augustus Zibell-Barnes | S4246254 | Blog | <https://titan.csit.rmit.edu.au/~s4246254/blog/posts.php> |
| Augustus Zibell-Barnes | S4246254 | Product Review & Rating | <https://titan.csit.rmit.edu.au/~s4246254/reviews/reviews.php> |

All four modules are delivered by a single student. The CoreTeaching servers
(`titan`, `saturn`, `jupiter`) share one home directory, so the same files are
reachable through any of them.

Group process decisions — shell ownership and the locale-switching mechanism, now
implemented — are in [CHARTER.md](CHARTER.md). The summary report for Deliverable 1
is [SUMMARY_REPORT.md](SUMMARY_REPORT.md).

## Test accounts

Log in from **Account** (`account.php`) with a username only — no password, as the
brief specifies. These four accounts own the sample content:

| Username | Display name | Owns (sample content) |
|---|---|---|
| `kaya` | Kaya Ellery | 2 threads, forum replies, 2 blog posts, 2 reviews, a saved cart of 3 items |
| `toma` | Toma Brandt | 2 threads, replies, 1 blog post, 2 reviews |
| `renn` | Renn Vasco | 1 thread, replies, 2 blog posts, 1 review |
| `oksana` | Oksana Reyes | 1 thread, replies, 1 blog post, 2 reviews |

Any other username creates a fresh account with no content. Only the account that
wrote a post, comment, blog post or review can edit or delete it; everyone else sees
no Edit/Delete controls **and** the server answers an edit or delete request for
someone else's content with HTTP 403. Because the store is shared, two markers in
different browsers see each other's posts, replies and reviews in real time. "Reset
demo data" on the account page returns everything to the seed state for everyone.

## Locales

The header toggle switches between **English (Australia) `en-AU`** and
**German `de-DE`**. The choice is stored in the PHP session, sets
`<html lang="…">`, translates every shell and module UI string (747 strings in
`shared/lang/de-DE.php`), and re-formats every date, price and count for the locale
(PHP `intl` when available, with a manual fallback; the browser then re-formats the
same elements with `Intl.NumberFormat` / `Intl.DateTimeFormat`). User-written
content is not translated, as the brief allows.

## How to run locally

Any PHP 7.4+ install; no database, no build step, no dependencies.

```bash
php -S 127.0.0.1:8790 -t .
# open http://127.0.0.1:8790/welcome.php
```

## File structure

```text
welcome.php  categories.php  about.php  account.php     shared root pages
shared/
  bootstrap.php   session, login, locale, formatting, CSRF, helpers
  data.php        hard-coded seed data (associative arrays) that seeds the store
  store.php       the shared in-memory store (APCu, or a locked scratch file in the temp dir)
  header.php      <head>, site header, locale toggle, cart count, login/logout
  nav.php         primary navigation
  footer.php      site footer
  locale.php      POST handler for the locale toggle
  validation.php  server-side validation rules engine and form helpers
  images.php      shared image-attachment fields (library or in-memory upload)
  i18n.php        locale configuration; lang/de-DE.php the German dictionary
forum/    boards.php thread.php new-thread.php edit-post.php delete-post.php  lib.php render.php
shop/     catalogue.php product.php cart.php checkout.php order-confirmation.php  lib.php
blog/     posts.php post.php new-post.php edit-post.php delete-post.php  lib.php form.php
reviews/  reviews.php review.php new-review.php edit-review.php delete-review.php  lib.php form.php
assets/
  css/      base.css layout.css components.css (hand-written, no framework)
  js/       site.js (locale menu, Intl formatting, image previews)
            validate.js (client-side validation from data-* rules)
            shop.js (live cart and checkout totals)
  img/      hand-drawn SVGs and one local photograph
```

No page is named `index` or `home`. Paths are relative and computed from the file
system, so the site runs unchanged in any web root.

## How the requirements are met

- **Shared shell server-side.** Header, nav and footer are PHP includes pulled in by
  every page with `require`; nothing is copy-pasted.
- **Login / logout** in the shared header on every page; username only.
- **In-memory storage, shared by all visitors.** `shared/data.php` provides the
  hard-coded associative arrays; `shared/store.php` keeps that structure alive between
  requests (APCu shared memory when the server has it, otherwise one serialised scratch
  file in the system temp directory under an exclusive lock). Every create/update/
  delete edits that structure, so a post made in one browser is visible in another,
  and the whole thing is discarded on a server restart or *Reset demo data*. No SQL,
  nothing written under the web root. Only per-visitor state (who is logged in, the
  chosen locale, a guest's cart) lives in the PHP session. Forum deletions are soft
  (`deleted => true`) so the post is removed from view but retained for auditing.
- **Secure input handling.** `filter_input` for all request data, CSRF tokens on every
  POST, `htmlspecialchars` on all output, ownership checked server-side.
- **Validation twice.** `assets/js/validate.js` reads `data-*` rules and blocks bad
  submissions with inline messages and a summary; `shared/validation.php` applies the
  same rules again in PHP and re-renders the form with the errors. **No HTML
  validation attributes** (`required`, `pattern`, `min`, `max`, `maxlength`) are used
  anywhere.
- **Forum:** threads with subject and opening post, threaded replies, edit all
  components of own posts (title, text, image, thread title/board), soft delete,
  sort/filter by title, post text, author, board, newest activity, oldest post, replies.
- **Shop:** product list with filters (title, category, colour, size, price, stock)
  and sorting; add to cart with variant, quantity and note; cart saved per user and
  merged from a guest cart on login; update variant/quantity/note per line; remove;
  discount code; sort/filter cart lines by title, quantity, price; checkout with
  delivery and card details (Luhn-checked, never stored beyond the last four digits);
  totals computed server-side in integer cents; stock re-checked and decremented at
  order time; locale-formatted confirmation page.
- **Blog:** posts with title, author, date, tags, summary, body and image; list with
  previews and full view with comments (create/edit/delete own comments); edit and
  delete own posts; search by title, text, author, tag and date range.
- **Reviews:** title, description, star rating, reviewer, date, image; list previews and
  full view; edit/delete own; filter by product, title, text, minimum rating,
  reviewer and date range; product averages are computed live from the reviews.
- **Accessibility carried forward:** one `h1` per page, landmarks, labelled controls,
  `aria-invalid` / `aria-describedby` on failed fields, focus moved to the error
  summary, visible focus ring, text alternatives on every image (required whenever a
  member attaches one), no information by colour alone.

## Verification performed

- Every page × both locales rendered through PHP and checked for: correct
  `<html lang>`, exactly one `h1`, no heading jumps, no HTML validation attributes,
  no inline styles, no duplicate ids, `alt` on every image, a label for every control,
  every `aria-describedby` target present, every internal link resolving, no PHP
  warnings.
- Each module exercised end to end over HTTP: create, retrieve, update, delete,
  validation failures re-rendering with messages, and edit/delete of another user's
  content refused with 403.
- In a real browser: client-side validation blocks submission and shows messages in
  the active language; the locale menu opens/closes and switches the site; cart line
  totals and subtotal update live in the locale's format.
- `php -l` on every file; no PHP 8-only syntax so it runs on PHP 7.4+.

## Image credits

`assets/img/hero-tabletop.jpg` — photograph by Robert Coelho via
[Unsplash](https://unsplash.com/photos/six-assorted-color-dice-laNNTAth9vs), Unsplash
Licence, stored locally. All other images are hand-written SVG with no text baked in.

The `S4246254_self_audit_statement.md` files in each module folder are the
Assessment Task 1 accessibility self-audits and are kept for reference.
