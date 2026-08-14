# Self-audit statement — Discussion Forum module

**Student ID:** S4246254
**Module:** Discussion Forum (`forum/boards.html`, `thread.html`, `new-thread.html`, `edit-post.html`, `delete-post.html`)

## What I checked

Every page in this module uses `<html lang="en">`, exactly one `<h1>`, and a heading
sequence that never skips a level. The page furniture is built from real landmarks —
`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>` — rather than `<div>`
elements standing in for structural roles. Each `<nav>` on a page carries a distinct
accessible name (`Primary`, `Breadcrumb`, `Footer`, plus `Thread list pages` on the
pagination), because more than one `<nav>` per page is otherwise ambiguous to a screen
reader.

Every form control across the five pages has a `<label for="…">` bound to it by `id`, and
every hint or constraint is wired with `aria-describedby` rather than left as loose text
near the field. No rule anywhere removes an outline; the visible `:focus-visible` ring
defined once in `base.css` applies throughout, and the skip link appears on first Tab.

Ownership is demonstrated without scripting: posts written by the signed-in account carry
a "Your post" badge alongside Edit and Delete controls, while other members' posts show
Report instead. The badge means ownership is never signalled by border colour alone.
Dates use `<time datetime="…">` with human-readable text, and no meaningful text is baked
into any image.

## A remaining gap

The image attachment fields on `thread.html`, `new-thread.html` and `edit-post.html`
render a thumbnail beside the file input, described in `alt` text as the currently
selected image. Because this is a static site with no JavaScript, that preview is a fixed
placeholder and cannot change when a real file is chosen, so the description would be
inaccurate for anyone actually using the control. I kept the component to show the
intended layout and recorded the problem here rather than quietly deleting it. In a
scripted implementation the preview would be populated from the selected file and its
`alt` text taken from a user-supplied description field.

*Prose word count: 295, measured excluding headings and the metadata lines above.*
