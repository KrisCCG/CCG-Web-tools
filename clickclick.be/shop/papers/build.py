#!/usr/bin/env python3
"""
Builds papers/index.html (EN), papers/fr.html and papers/nl.html.

THE WORDS ARE NOT IN THIS FILE. They live in text/en.txt, text/fr.txt and
text/nl.txt, one plain-text file per language, so the copy can be rewritten
without touching any code. Edit those, then run:

    python3 build.py

The LOOK lives in ../lib/ccg_page.py, shared with /legal/ and any other
standalone page. See the "Plain-text content files" section at the bottom of
that module for the (short) format: `key: value` fields at the top, then
`[id]  Heading` to open each section, with the body written as HTML.

To add a paper: add a `[slug]  Heading` block to all three text files, drop the
two photographs into img/, and re-run. No code change — a section's layout
follows from how many `{photos ...}` pairs it holds.

Upload after building: the three HTML files, plus fonts/ and img/, to /papers/.
"""
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "lib"))
import ccg_page as kit

HERE = os.path.dirname(os.path.abspath(__file__))

if __name__ == "__main__":
    kit.build_from_text(os.path.join(HERE, "text"), HERE)
