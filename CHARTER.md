# Group charter — Grimdark Forge (COSC3046 Assessment Task 1)

| | |
|---|---|
| Project | Grimdark Forge — miniature wargaming store and community |
| Student | Augustus Zibell-Barnes (S4246254) |
| Modules | Discussion Forum, Shopping Cart, Blog, Product Review & Rating |

## 1. Roles and module ownership

The brief allocates one module per team member. This site is delivered by a single
student, so all four module leads are held by the same person. The modules are still
kept as separate concerns — separate folders, separate page sets, and a separate
accessibility self-audit statement each — so that each one can be assessed against the
module criteria on its own terms.

| Module | Folder | Lead |
|---|---|---|
| Discussion Forum | `forum/` | Augustus Zibell-Barnes |
| Shopping Cart | `shop/` | Augustus Zibell-Barnes |
| Blog | `blog/` | Augustus Zibell-Barnes |
| Product Review & Rating | `reviews/` | Augustus Zibell-Barnes |

## 2. Ownership of the shared site shell

**Decision.** The site shell — the skip link, `<header>` with the wordmark, the primary
`<nav>`, the header utility area (cart link, account link, locale toggle), and the
`<footer>` with its footer `<nav>` — is a single shared artefact. It is identical on all
25 pages apart from relative path depth (`assets/…` at the repository root, `../assets/…`
inside a module folder). It is **not** owned by any one module.

**Responsibility.** Whoever changes the shell is responsible for making the change on
every page in the same commit, and for the shell continuing to meet these accessibility
requirements on every page regardless of which module the page belongs to:

- **Landmarks.** `<header>`, `<nav>`, `<main id="main">` and `<footer>` are present on
  every page and are real landmark elements, not `<div>`s. Where a page carries more than
  one `<nav>`, each has a distinct accessible name via `aria-label` (`Primary`,
  `Breadcrumb`, `Footer`, and pagination names such as `Thread list pages`).
- **Heading structure.** The shell contributes no headings, so each page keeps exactly
  one `<h1>` in `<main>` and an unbroken heading sequence beneath it.
- **Focus visibility.** The focus ring is defined once in `assets/css/base.css` and is
  never removed. No rule in any stylesheet sets `outline: none` or `outline: 0`. The skip
  link is the first focusable element on every page and becomes visible on first Tab.
- **Language declaration.** Every page opens with `<html lang="en">`.

**Verification.** Before any shell change is committed, the same scripted checks are run
across all 25 pages: one `<h1>` per page, no skipped heading levels, a `<label for="…">`
for every form control, an `alt` attribute on every `<img>`, no duplicate `id` values
within a page, no `outline: none` in CSS, and every internal link and image source
resolving to a file that exists.

**Currently signed-in user.** The shell also carries the demonstration of user identity:
the header utility area names the signed-in account (Kaya Ellery) with a link to
`account.html`, rather than a generic "Login" link, so the current user is identifiable
from every page. Content by that account shows Edit and Delete controls; everyone else's
shows Report. This convention is shared by all four modules and changing it is a shell
change, not a module change.

## 3. Locale-switching mechanism (planning decision only)

Not implemented in Assessment Task 1. Agreed approach for the later assessment:

- **Control.** A single locale toggle lives in the shared header utility area, to the
  left of the cart link, in the same position on every page. In this assessment it is a
  visual placeholder only.
- **Form.** A `<button>` showing a globe icon and the current locale's short name (for
  example "English"), carrying an `aria-label` written as an ordinary sentence rather than
  a code-like string, because the icon alone gives no accessible name. When it becomes
  functional it expands to a list of locales; the selected one is marked with
  `aria-current="true"`.
- **Scope.** The toggle switches the whole site, not one module. Choosing a locale on a
  forum page keeps that locale when the reader moves to the shop.
- **Persistence.** Once server-side code exists, the choice is stored against the session
  and reflected by the `<html lang="…">` attribute on every page, so assistive technology
  announces the correct language.
- **Consequences accepted now.** So that the switch is possible later, this assessment
  already avoids baking any meaningful text into images (all SVGs are text-free), writes
  `alt`, `aria-label` and `aria-describedby` values as ordinary translatable sentences,
  and keeps dates in `<time datetime="…">` and prices as marked-up text so their
  presentation can be re-formatted per locale rather than being hardcoded.

**Status.** The placeholder control is present in the header of all 25 pages, in the
position described above. It is a real, focusable `<button>` with an accessible name, but
it opens nothing — the switching behaviour arrives with the later assessment task.

## 4. Development and production plan

1. Agree the theme, module split and page inventory; write the wireframes.
2. Build the shell and the three stylesheets (`base.css` design tokens, `layout.css`
   regions, `components.css` reusable pieces) before any module page, so every module
   inherits the same structure and no module needs its own stylesheet.
3. Build each module's five pages against the wireframes, reusing existing components in
   preference to adding new ones.
4. Run the scripted accessibility and link checks in section 2 after each module.
5. Write each module's self-audit statement, naming a genuine remaining gap rather than a
   generic one.
6. Deploy to the RMIT core teaching servers and re-check every page there, since all
   marking is carried out from that environment.

## 5. Working agreements

- Commit to `main` regularly across the assessment period rather than in one final push,
  so progress is reviewable.
- No JavaScript, no PHP, no database, and no third-party CSS or JS framework in this
  assessment. No inline `style` attributes — all presentation lives in the three
  stylesheets.
- No page is named `index` or `home`.
- All paths are relative, so the site can be moved between servers unchanged.
- No remote requests: fonts fall through to the platform UI stack and all images are
  stored locally, so the site renders identically with the network disconnected.
