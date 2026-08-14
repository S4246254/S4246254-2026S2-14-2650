# Self-audit statement — Product Review and Rating module

**Student ID:** S4246254
**Module:** Reviews (`reviews/reviews.html`, `review.html`, `new-review.html`, `edit-review.html`, `delete-review.html`)

## What I checked

All five pages declare `<html lang="en">`, carry one `<h1>`, and step through heading
levels without skipping. The filter form on `reviews.html` covers the four axes the brief
requires — product, rating, reviewer and date — and every control is bound to a
`<label for="…">`.

Star ratings are text rather than images throughout. The visual bar is two stacked spans of star characters
marked `aria-hidden="true"`, and the value beside it is ordinary readable text — "4.5 out
of 5, from 64 reviews". Nothing about a rating is conveyed by the graphic alone, and
nothing depends on colour: filled stars are dark and empty stars are grey, but the number
is always stated.

The rating input on `new-review.html` and `edit-review.html` is a radio group inside a
`<fieldset>` with a `<legend>`, exactly as the brief specifies. Each option has a real
text label ("4 stars — good"), so it can be read aloud and selected with the keyboard
alone, with no clickable-star widget that would need scripting.

I also checked the numbers rather than inventing them. The breakdown on `reviews.html` —
600, 400, 180, 70 and 34 — sums to the 1,284 reviews stated above it, and the weighted
average of those counts is 4.14, which is what the displayed 4.1 rounds to. The bar
widths are derived from those proportions rather than borrowed from the ten-per-cent star
classes, which were too coarse to be honest.

## A remaining gap

Ownership is demonstrated but not enforced. `edit-review.html` and `delete-review.html`
are reachable by typing their addresses, because a static site has nothing to check who
is asking. A real site would verify the signed-in user against the review's author server-side;
here the rule exists only in which controls each page displays.

*Prose word count: 283, measured excluding headings and the metadata lines above.*
