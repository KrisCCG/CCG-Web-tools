<?php
return
'
.hascustomphonegrid > #grid {
  display: none;
}
.hascustomphonegrid .column-wrap._100vh,
.hascustomphonegrid .column-wrap.rowcustomheight {
  display: flex;
}

.hascustomphonegrid .cover-region-desktop {
  display: none;
}
.hascustomphonegrid .cover-content-desktop {
  display: none;
}

.nocustomphonegrid .type-vl {
  display: none;
}

.footer-hascustomphonegrid #footer {
  display: none;
}

body {
  box-sizing: border-box;
}

.fp-section.row._100vh,
.fp-section.row._100vh.empty {
  min-height: 0;
}
.fp-section.row._100vh .row-inner,
.fp-section.row._100vh.empty .row-inner {
  min-height: 0 !important;
}

.lay-content.nocustomphonegrid #grid .col.lay-sticky,
.lay-content.footer-nocustomphonegrid #footer .col.lay-sticky,
.lay-content .cover-region-desktop .col.lay-sticky {
  position: relative !important;
  top: auto !important;
}

.lay-content.nocustomphonegrid #grid .col:not(.below, .in-view),
.lay-content.footer-nocustomphonegrid #footer .col:not(.below, .in-view),
.lay-content .cover-region-desktop .col:not(.below, .in-view) {
  transform: none !important;
}

.lay-content.nocustomphonegrid #grid .col,
.lay-content.footer-nocustomphonegrid #footer .col,
.lay-content .cover-region-desktop .col {
  width: 100%;
}

html.flexbox .lay-content .row._100vh.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-top,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-top,
html.flexbox .lay-content .row._100vh.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-top,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-top {
  top: 0;
  position: absolute;
  margin-bottom: 0;
}
html.flexbox .lay-content .row._100vh.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-middle,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-middle,
html.flexbox .lay-content .row._100vh.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-middle,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-middle {
  top: 50%;
  transform: translate(0, -50%) !important;
  position: absolute;
  margin-bottom: 0;
}
html.flexbox .lay-content .row._100vh.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-bottom,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-2.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-bottom,
html.flexbox .lay-content .row._100vh.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-bottom,
html.flexbox .lay-content .row.rowcustomheight.row-col-amt-3.absolute-positioning-vertically-in-apl > .row-inner > .column-wrap > .col.align-bottom {
  bottom: 0;
  position: absolute;
  margin-bottom: 0;
}

.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl {
  display: flex;
  flex-direction: column;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-default,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-default {
  order: 51;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-0,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-0 {
  order: 0;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-1,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-1 {
  order: 1;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-2,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-2 {
  order: 2;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-3,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-3 {
  order: 3;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-4,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-4 {
  order: 4;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-5,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-5 {
  order: 5;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-6,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-6 {
  order: 6;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-7,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-7 {
  order: 7;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-8,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-8 {
  order: 8;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-9,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-9 {
  order: 9;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-10,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-10 {
  order: 10;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-11,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-11 {
  order: 11;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-12,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-12 {
  order: 12;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-13,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-13 {
  order: 13;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-14,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-14 {
  order: 14;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-15,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-15 {
  order: 15;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-16,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-16 {
  order: 16;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-17,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-17 {
  order: 17;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-18,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-18 {
  order: 18;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-19,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-19 {
  order: 19;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-20,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-20 {
  order: 20;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-21,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-21 {
  order: 21;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-22,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-22 {
  order: 22;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-23,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-23 {
  order: 23;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-24,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-24 {
  order: 24;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-25,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-25 {
  order: 25;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-26,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-26 {
  order: 26;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-27,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-27 {
  order: 27;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-28,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-28 {
  order: 28;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-29,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-29 {
  order: 29;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-30,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-30 {
  order: 30;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-31,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-31 {
  order: 31;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-32,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-32 {
  order: 32;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-33,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-33 {
  order: 33;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-34,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-34 {
  order: 34;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-35,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-35 {
  order: 35;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-36,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-36 {
  order: 36;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-37,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-37 {
  order: 37;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-38,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-38 {
  order: 38;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-39,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-39 {
  order: 39;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-40,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-40 {
  order: 40;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-41,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-41 {
  order: 41;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-42,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-42 {
  order: 42;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-43,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-43 {
  order: 43;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-44,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-44 {
  order: 44;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-45,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-45 {
  order: 45;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-46,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-46 {
  order: 46;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-47,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-47 {
  order: 47;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-48,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-48 {
  order: 48;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-49,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-49 {
  order: 49;
}
.lay-content.nocustomphonegrid #grid .column-wrap.custom-order-in-apl .col.apl-order-50,
.lay-content.footer-nocustomphonegrid #footer .column-wrap.custom-order-in-apl .col.apl-order-50 {
  order: 50;
}

#lay-project-overlay .project-overlay-grid-has-custom-bg.grid {
  background-color: transparent !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
}
#lay-project-overlay .project-overlay-grid-has-custom-bg.grid .grid-inner {
  background-color: transparent !important;
}

html.flexbox .lay-content .row._100vh.one-col-row .column-wrap,
html.flexbox .lay-content .row.rowcustomheight.one-col-row .column-wrap {
  display: flex;
}
html.flexbox .lay-content .row._100vh.one-col-row .col.align-top,
html.flexbox .lay-content .row.rowcustomheight.one-col-row .col.align-top {
  align-self: start;
  margin-bottom: 0;
}
html.flexbox .lay-content .row._100vh.one-col-row .col.align-middle,
html.flexbox .lay-content .row.rowcustomheight.one-col-row .col.align-middle {
  align-self: center;
  margin-bottom: 0;
}
html.flexbox .lay-content .row._100vh.one-col-row .col.align-bottom,
html.flexbox .lay-content .row.rowcustomheight.one-col-row .col.align-bottom {
  align-self: end;
  margin-bottom: 0;
}

.lay-content .row {
  box-sizing: border-box;
  display: block;
}

.lay-content .row:last-child > .row-inner > .column-wrap:not(.custom-order-in-apl) > .col:last-child {
  margin-bottom: 0 !important;
}

html.flexbox #custom-phone-grid .column-wrap._100vh,
html.flexbox #custom-phone-grid .column-wrap.rowcustomheight,
html.flexbox .cover-region-phone .column-wrap._100vh,
html.flexbox .cover-region-phone .column-wrap.rowcustomheight,
html.flexbox #footer-custom-phone-grid .column-wrap._100vh,
html.flexbox #footer-custom-phone-grid .column-wrap.rowcustomheight {
  display: flex;
}
html.flexbox #custom-phone-grid .col.align-top,
html.flexbox .cover-region-phone .col.align-top,
html.flexbox #footer-custom-phone-grid .col.align-top {
  align-self: start;
}
html.flexbox #custom-phone-grid .col.align-middle,
html.flexbox .cover-region-phone .col.align-middle,
html.flexbox #footer-custom-phone-grid .col.align-middle {
  align-self: center;
}
html.flexbox #custom-phone-grid .col.align-bottom,
html.flexbox .cover-region-phone .col.align-bottom,
html.flexbox #footer-custom-phone-grid .col.align-bottom {
  align-self: end;
}

.row-inner {
  box-sizing: border-box;
}

.html5video .html5video-customplayicon {
  max-width: 100px;
}

.cover-enabled-on-phone .cover-region {
  position: sticky;
  z-index: 1;
  top: 0;
  left: 0;
  width: 100%;
  min-height: 100vh;
}

.lay-sitewide-background-video {
  display: none;
}

.lay-background-video {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  max-height: 100vh;
  height: 100%;
  -o-object-fit: cover;
  object-fit: cover;
  -o-object-position: center;
  object-position: center;
  pointer-events: none;
  z-index: -1;
}

.lay-background-image {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  max-height: 100vh;
  height: 100%;
  -o-object-fit: cover;
  object-fit: cover;
  -o-object-position: center;
  object-position: center;
  pointer-events: none;
  z-index: -1;
}

.lay-background-video-desktop {
  display: none;
}

.lay-background-video-mobile {
  display: block;
}

.lay-background-image-desktop {
  display: none;
}

.lay-background-image-mobile {
  display: block;
}

.cover-down-arrow-desktop {
  display: none;
}

.cover-down-arrow-phone {
  display: block;
}

.lay-content.nocustomphonegrid #grid .row.only-marquee-row {
  padding-left: 0;
  padding-right: 0;
}

.col.absolute-position.type-vl {
  position: absolute;
  margin-left: 0 !important;
  z-index: 1;
}

#custom-phone-grid .column-wrap._100vh > .col.absolute-position:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap._100vh > .col.absolute-position:not(.lay-sticky),
.cover-region-phone .column-wrap._100vh > .col.absolute-position:not(.lay-sticky) {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}
#custom-phone-grid .column-wrap._100vh > .col.absolute-position.align-top:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap._100vh > .col.absolute-position.align-top:not(.lay-sticky),
.cover-region-phone .column-wrap._100vh > .col.absolute-position.align-top:not(.lay-sticky) {
  top: 0;
}
#custom-phone-grid .column-wrap._100vh > .col.absolute-position.align-bottom:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap._100vh > .col.absolute-position.align-bottom:not(.lay-sticky),
.cover-region-phone .column-wrap._100vh > .col.absolute-position.align-bottom:not(.lay-sticky) {
  bottom: 0;
}

#custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position:not(.lay-sticky),
.cover-region-phone .column-wrap.rowcustomheight > .col.absolute-position:not(.lay-sticky) {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}
#custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position.align-top:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position.align-top:not(.lay-sticky),
.cover-region-phone .column-wrap.rowcustomheight > .col.absolute-position.align-top:not(.lay-sticky) {
  top: 0;
}
#custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position.align-bottom:not(.lay-sticky),
#footer-custom-phone-grid .column-wrap.rowcustomheight > .col.absolute-position.align-bottom:not(.lay-sticky),
.cover-region-phone .column-wrap.rowcustomheight > .col.absolute-position.align-bottom:not(.lay-sticky) {
  bottom: 0;
}

.cover-disabled-on-phone .cover-region-desktop._100vh._100vh-not-set-by-user {
  min-height: 0 !important;
}
.cover-disabled-on-phone .cover-region-desktop._100vh._100vh-not-set-by-user .cover-inner._100vh {
  min-height: 0 !important;
}
.cover-disabled-on-phone .cover-region-desktop._100vh._100vh-not-set-by-user .row._100vh {
  min-height: 0 !important;
}
.cover-disabled-on-phone .cover-region-desktop._100vh._100vh-not-set-by-user .row-inner._100vh {
  min-height: 0 !important;
}
.cover-disabled-on-phone .cover-region-desktop._100vh._100vh-not-set-by-user .column-wrap._100vh {
  min-height: 0 !important;
}

.lay-thumbnailgrid-tagfilter.mobile-one-line,
.lay-productsgrid-tagfilter.mobile-one-line {
  white-space: nowrap;
  overflow-x: scroll;
  box-sizing: border-box;
  -webkit-overflow-scrolling: touch;
}

.lay-thumbnailgrid-tagfilter::-webkit-scrollbar,
.lay-productsgrid-tagfilter::-webkit-scrollbar {
  display: none;
}

.lay-thumbnailgrid-tagfilter,
.lay-productsgrid-tagfilter {
  scrollbar-width: none;
}

.lay-thumbnailgrid-filter.mobile-one-line,
.lay-productsgrid-filter.mobile-one-line {
  white-space: nowrap;
  overflow-x: scroll;
  box-sizing: border-box;
  -webkit-overflow-scrolling: touch;
}

.lay-thumbnailgrid-filter::-webkit-scrollbar,
.lay-productsgrid-filter::-webkit-scrollbar {
  display: none;
}

.lay-thumbnailgrid-filter,
.lay-productsgrid-filter {
  scrollbar-width: none;
}

.lay-thumbnailgrid-tagfilter.mobile-one-line .tag-bubble:first-child {
  margin-left: 0 !important;
}
.lay-thumbnailgrid-tagfilter.mobile-one-line .tag-bubble:last-child {
  margin-right: 0 !important;
}

.lay-productsgrid-tagfilter.mobile-one-line .ptag-bubble:first-child {
  margin-left: 0 !important;
}
.lay-productsgrid-tagfilter.mobile-one-line .ptag-bubble:last-child {
  margin-right: 0 !important;
}

.lay-phone-slide-from-top {
  top: 0;
  left: 0;
  right: 0;
  transform: translateY(-100.1%);
}

body.do-page-overlay-transition .lay-phone-slide-from-top {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-phone-slide-from-left {
  top: 0;
  left: 0;
  bottom: 0;
  transform: translateX(-100.1%);
}

body.do-page-overlay-transition .lay-phone-slide-from-left {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-phone-slide-from-right {
  top: 0;
  right: 0;
  transform: translateX(100%);
}

body.do-page-overlay-transition .lay-phone-slide-from-right {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-phone-slide-from-bottom {
  left: 0;
  right: 0;
  bottom: 0;
  transform: translateY(100.1%);
}

body.do-page-overlay-transition .lay-phone-slide-from-bottom {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-phone-fade {
  opacity: 0;
  top: 0;
  left: 0;
  right: 0;
  transform: translateY(-100.1%);
}

.lay-phone-none {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-phone-none .row .col {
  pointer-events: none;
}

.lay-phone-none-right {
  transform: translate(0, 0);
  top: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-phone-none-right .row .col {
  pointer-events: none;
}

.lay-phone-none-left {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-phone-none-left .row .col {
  pointer-events: none;
}

.lay-phone-none-bottom {
  transform: translate(0, 0);
  bottom: 0;
  left: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-phone-none-bottom .row .col {
  pointer-events: none;
}

.lay-phone-none-top {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-phone-none-top .row .col {
  pointer-events: none;
}

.lay-show-overlay.lay-phone-slide-from-top {
  transform: translateY(0);
}

.lay-show-overlay.lay-phone-slide-from-left {
  transform: translateX(0);
}

.lay-show-overlay.lay-phone-slide-from-right {
  transform: translateX(0);
}

.lay-show-overlay.lay-phone-slide-from-bottom {
  transform: translateY(0);
}

.lay-show-overlay.lay-phone-fade {
  transform: translateY(0);
  animation: 350ms forwards overlayfadein;
  opacity: 1;
}

.lay-show-overlay.lay-fadeout {
  animation: 350ms forwards overlayfadeout;
}

.lay-show-overlay.lay-phone-none,
.lay-show-overlay.lay-phone-none-right,
.lay-show-overlay.lay-phone-none-left,
.lay-show-overlay.lay-phone-none-top,
.lay-show-overlay.lay-phone-none-bottom {
  pointer-events: auto;
  opacity: 1;
}
.lay-show-overlay.lay-phone-none .row .col,
.lay-show-overlay.lay-phone-none-right .row .col,
.lay-show-overlay.lay-phone-none-left .row .col,
.lay-show-overlay.lay-phone-none-top .row .col,
.lay-show-overlay.lay-phone-none-bottom .row .col {
  pointer-events: auto;
}

@keyframes overlayfadein {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}
@keyframes overlayfadeout {
  0% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}
.lay-overlay.lay-phone-overlay-has-custom-bg > .lay-content {
  background-color: transparent;
}

html.no-flexbox #footer-custom-phone-grid .col.align-bottom {
  vertical-align: bottom;
}
';