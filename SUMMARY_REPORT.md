# Grimdark Forge — Assessment Task 2 Summary Report

| | |
|---|---|
| Course | COSC3046 Web Programming Studio |
| Assessment | Task 2 — Dynamic Web Application (Prototype) |
| Student | Augustus Zibell-Barnes, S4246254 |
| Repository | Same GitHub repository as Assessment Task 1, branch `main` |
| Hosted at | <https://titan.csit.rmit.edu.au/~s4246254/welcome.php> |
| Modules | Discussion Forum · Shopping Cart · Blog · Product Review & Rating |

## 1. Updated group charter

The full charter is `CHARTER.md` in the repository. The changes since Assessment Task 1:

- **Shared site shell is now built server-side.** The header, navigation and footer are
  `shared/header.php`, `shared/nav.php` and `shared/footer.php`, pulled into every page
  with `require`. The path prefix for module folders is computed, not typed, so no page
  carries a copy of the shell.
- **Login/logout lives in the shell.** A username-only login (no password) on
  `account.php`; the header shows *Log in* or the account name plus *Log out* on every
  page. The username is kept in the PHP session and every module uses the same
  `current_user()` / `owns()` helpers for ownership.
- **Locale-switching mechanism confirmed as implemented.** The mechanism agreed in
  Assessment Task 1 — a header `<button>` that expands a list of locales, whole-site
  scope, stored against the session, reflected in `<html lang>` — was built exactly as
  described. Two locales are supported, `en-AU` and `de-DE`.
- **Working agreements updated** for the new constraints: raw PHP/JS only, no HTML
  validation attributes, no inline styles, rendered-page checks run in both locales
  before committing.

## 2. Test accounts

Log in from the **Account** link (or the *Log in* button in the header) by typing a
username. No password is required.

| Username | Display name | Sample content owned |
|---|---|---|
| `kaya` | Kaya Ellery | 2 forum threads and replies, 2 blog posts, 2 reviews, a saved 3-item cart |
| `toma` | Toma Brandt | 2 forum threads and replies, 1 blog post, 2 reviews |
| `renn` | Renn Vasco | 1 forum thread and replies, 2 blog posts, 1 review |
| `oksana` | Oksana Reyes | 1 forum thread and replies, 1 blog post, 2 reviews |

Any other username (3–20 letters, digits or underscores) creates a new empty account.

**Testing ownership.** Log in as `kaya`, open the forum thread *Is the Sentinel actually
worth its points at 1500?* — Kaya's reply carries *Your post* with Edit and Delete;
Toma's opening post does not. Then log out and log in as `toma`: the controls move to
Toma's post. Typing `forum/edit-post.php?id=1` while logged in as `kaya` returns a
403 page ("You cannot edit this post"), as do the equivalent blog, review and comment
requests, so ownership is enforced by the server and not just hidden in the UI. The
same pattern applies to blog posts and comments, reviews, cart lines and order
confirmations (an order can only be viewed by the account that placed it).

**Shared data.** The store is server-wide: a thread started in one browser appears
immediately in another, so two markers can interact (one posts, the other replies) and
test ownership against each other. Guest carts are the only per-browser data.

**Reset.** *Reset demo data* on the account page returns every module to the seed
content for all visitors. A web-server restart does the same.

## 3. Locales and how to test the toggle

Supported locales: **English (Australia) — `en-AU`** (default) and **German —
`de-DE`**.

1. On any page, activate the globe button in the header (it reads *English* or
   *Deutsch*). It expands a list of the two locales; the current one is marked. The
   list also works with JavaScript disabled.
2. Choose *Deutsch (Deutschland)*. The page reloads with `<html lang="de-DE">`; the
   navigation, buttons ("Anmelden"/"Abmelden"), footer, headings, form labels, hints,
   validation messages and status messages are in German.
3. Check the formatting: prices read `42,00 AU$` rather than `$42.00`, dates read
   `13. August 2026 um 21:40` rather than `13 August 2026 at 9:40 pm`, and counts use
   German separators. The checkout and order-confirmation pages format every figure
   this way.
4. Navigate to another module — the locale is kept for the whole session.
5. Submit a form with an error (for example an empty username): the JavaScript
   messages are German too, and so are the PHP messages if JavaScript is disabled.
6. Choose *English (Australia)* to switch back.

User-written content (post bodies, review text, product descriptions) is not
translated, as the brief allows at this stage.

## 4. Known issues and limitations

- **How the in-memory store survives between requests.** PHP forgets everything at
  the end of a request, so `shared/store.php` parks the associative arrays in APCu
  shared memory when the server has it, and otherwise in one serialised scratch file
  in the system temp directory (outside the web root, under an exclusive lock). The
  file backend is technically a file, but it holds nothing but the in-memory
  structure, is never queried, and is discarded on reset or clean-up — there is no
  database until Assessment Task 3.
- **Uploaded images are held in the store as data URIs**, capped at 1 MB each. Many
  large uploads would make every request load a larger store; library images avoid
  this.
- **PHP `intl` extension.** With `intl` present the server formats dates and prices
  with ICU; without it a built-in table is used for the two locales. In either case the
  browser re-formats with `Intl`, so the visible result is the same; only the no-JS
  fallback differs slightly (e.g. `08:22` with a leading zero).
- **Reply threading after deletion.** A deleted forum post is hidden and its replies are
  re-attached to the nearest visible ancestor, so a conversation can read oddly. The
  delete page warns about this before confirming.
- **Deleted forum posts are retained in memory** (as the brief requires) but there is no
  moderator view of the audit record yet; the record is only inspectable in the store.
- **No pagination controls appear** while the seed data fits on one page. Pagination is
  implemented (6 threads / 8 products / 6 posts / 6 reviews per page) and the controls
  appear once enough content is created.
- **The W3C validator and Lighthouse** have not been run against the hosted copy at the
  time of writing; the scripted checks cover the structural half of what they report.

## 5. Verification performed

- Every page (23 distinct pages) rendered through PHP in both locales and checked for
  the correct `<html lang>`, one `h1`, no heading jumps, no HTML validation attributes,
  no inline styles, no duplicate ids, `alt` on every image, a label for every control,
  every `aria-describedby` target present, every internal link resolving and no PHP
  warnings — all pass.
- Each module exercised end to end over HTTP: create, retrieve, update and delete;
  validation failures re-rendering the form with inline messages and a summary; and
  edit/delete requests for another account's content refused with HTTP 403.
- In a real browser: JavaScript validation blocks submission and shows messages in the
  active language; the locale menu opens, closes and switches the site; cart line totals
  and the subtotal update live in the locale's number format.
- Checkout arithmetic checked by hand: with `FORGE10` applied, 208.50 − 20.85 discount
  = 187.65 goods, free standard delivery over 120.00, GST = total ÷ 11.
- `php -l` on every file; no PHP 8-only syntax, so the site runs on PHP 7.4 and later.
