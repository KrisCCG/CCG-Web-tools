PHOTOS FOR /papers/
===================

NOTE (19 Oct 2026): the paper guide now lives in WordPress (/en/paper-guide/,
/fr/guide-des-papiers/, /nl/papiergids/). Put photos in the WordPress media
library and pick them in the empty photo slots on those pages. This folder is
only for the old static page at clickclick.be/papers/.


Two shots per paper. Drop the JPGs straight in this folder — the filenames are
fixed, the page picks them up with no code change. Until a file exists the page
shows a labelled cream box instead of a broken image, so it is safe to upload
them one at a time.

FILENAMES (exact, lowercase)
----------------------------
  multiart-silk-blank.jpg    multiart-silk-print.jpg
  biotop-blank.jpg           biotop-print.jpg
  recystar-blank.jpg         recystar-print.jpg
  colorplan-blank.jpg        colorplan-print.jpg
  fedrigoni-blank.jpg        fedrigoni-print.jpg
  color-1802-blank.jpg       color-1802-print.jpg

-blank = the bare sheet, nothing printed on it.
-print = the SAME stock with a real job printed on it.

FORMAT
------
  4:3 landscape, 1600 × 1200 px, JPEG quality ~80, under 400 kB each.
  The page crops to 4:3 and scales down, so anything wider gets cut at the
  sides — frame with a little room to spare.

HOW TO SHOOT THEM
-----------------
Consistency matters more than production value. Twelve photos that match beat
twelve beautiful photos that do not.

  · One light source, indirect daylight. A window to the side, no flash, no
    ceiling light mixed in — mixed light shifts the paper colour, which is the
    one thing these photos exist to show.
  · Same background for all twelve. A mid-grey or dark surface works; do not
    shoot white paper on a white table.
  · Shoot at a slight angle, not flat on. A raking light across the sheet is
    what makes the tooth of Biotop and the flecks in RecyStar visible at all.
  · Include an edge or a lifted corner. It shows the thickness, and on
    Colorplan it shows the colour running through the core.
  · Fill the frame with the paper. This is a texture photo, not a still life.
  · Same distance and same angle for every sheet, so a customer can compare
    the three shades by scrolling.

FOR THE -print SHOTS
--------------------
  · Use a job with a photograph, a large flat colour AND some small text if you
    can — that is exactly what the page describes in words.
  · Where possible, print the SAME artwork on all three stocks. Three photos of
    one design on three papers is the single most useful image on the page.
  · Do not colour-correct them afterwards. The point is to show the difference
    between the papers; editing each one to look its best erases it.

REPLACING A PHOTO LATER
-----------------------
The photos are loaded as CSS backgrounds and /papers/.htaccess caches JPEGs for
30 days. If you overwrite a file with a new shot under the SAME name, anyone who
already visited keeps seeing the old one until their cache expires. To force it
through immediately, save the new shot under a new name — biotop-blank-2.jpg —
and change the slug in the {photos …} line of text/en.txt, fr.txt and nl.txt,
then re-run build.py.
