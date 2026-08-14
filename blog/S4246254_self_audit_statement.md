# Self-audit statement — Blog module

**Student ID:** S4246254
**Module:** Blog (`blog/posts.html`, `post.html`, `new-post.html`, `edit-post.html`, `delete-post.html`)

## What I checked

All five pages declare `<html lang="en">`, carry one `<h1>`, and step through heading
levels without skipping. The post is an `<article>` with the author box in an `<aside>`,
so main and supporting content are distinguishable rather than two styled `<div>`
elements.

The search form on `posts.html` covers the four axes the brief asks for — title, author,
tag and date — plus a sort order, and every control has a `<label for="…">` bound by
`id`. Publication dates use `<time datetime="…">` with readable text beside the machine
value.

`new-post.html` and `edit-post.html` both require an **image description** field
alongside the file input, explained through `aria-describedby`. Asking the author for alt
text at upload is the only way a real version of this site could keep its images
described.

Ownership follows the same pattern as the forum. On `post.html` the comment written by
the signed-in account carries a "Your comment" badge with Edit and Delete; the other two
comments show Report. The badge means ownership is never conveyed by border colour alone.

## A remaining gap

The module is capped at five pages, so `edit-post.html` and `delete-post.html` are
written around editing and deleting a blog **post**, yet the comment-level Edit and
Delete controls on `post.html` also link to them. A reader following the Edit control on
their own comment lands on a page about their post instead. A real implementation needs
separate comment routes; I chose to keep the page count the brief specifies and record
the mismatch here rather than quietly adding a sixth page.

*Prose word count: 249, measured excluding headings and the metadata lines above.*
