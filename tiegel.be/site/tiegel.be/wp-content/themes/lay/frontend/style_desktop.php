<?php
return
'
.column-wrap._100vh,
.column-wrap.rowcustomheight {
  display: flex;
}

#custom-phone-grid {
  display: none;
}

#footer-custom-phone-grid {
  display: none;
}

.cover-region-phone {
  display: none;
}

.row._100vh,
.row._100vh.empty {
  min-height: 100vh;
  min-height: 100svh;
}
.row._100vh .row-inner,
.row._100vh .column-wrap,
.row._100vh.empty .row-inner,
.row._100vh.empty .column-wrap {
  min-height: 100vh;
  min-height: 100svh;
}

.col.align-top {
  align-self: start;
}

.col.align-onethird {
  align-self: start;
}

.col.align-middle {
  align-self: center;
}

.col.align-twothirds {
  align-self: end;
}

.col.align-bottom {
  align-self: end;
}

.cover-region {
  position: sticky;
  z-index: 1;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  height: 100svh;
}

.lay-sitewide-background-video-mobile {
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

.lay-background-video-mobile {
  display: none;
}

.lay-background-image-mobile {
  display: none;
}

.cover-down-arrow-desktop {
  display: block;
}

.cover-down-arrow-phone {
  display: none;
}

.col.type-vl.absolute-position {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}

/* 

100VH rows 

*/
.column-wrap._100vh > .col.absolute-position:not(.lay-sticky) {
  position: absolute !important;
  margin-left: 0 !important;
}

.column-wrap._100vh > .col.absolute-position.align-top:not(.lay-sticky) {
  top: 0;
}

.column-wrap._100vh > .col.absolute-position.align-bottom:not(.lay-sticky) {
  bottom: 0;
}

._100vh:not(.stack-element) > .type-html {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}

._100vh:not(.stack-element) > .type-html.align-top {
  top: 0;
}

._100vh:not(.stack-element) > .type-html.align-middle {
  top: 50%;
  transform: translateY(-50%);
}

._100vh:not(.stack-element) > .type-html.align-bottom {
  bottom: 0;
}

/* 

CUSTOM ROW HEIGHT 

*/
.column-wrap.rowcustomheight > .col.absolute-position:not(.lay-sticky) {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}

.rowcustomheight:not(.stack-element) > .type-html {
  position: absolute !important;
  margin-left: 0 !important;
  z-index: 1;
}

.rowcustomheight:not(.stack-element) > .type-html.align-top {
  top: 0;
}

.rowcustomheight:not(.stack-element) > .type-html.align-middle {
  top: 50%;
  transform: translateY(-50%);
}

.rowcustomheight:not(.stack-element) > .type-html.align-bottom {
  bottom: 0;
}

.lay-slide-from-top {
  top: 0;
  left: 0;
  right: 0;
  transform: translateY(-100.1%);
}

body.do-page-overlay-transition .lay-slide-from-top {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-slide-from-left {
  top: 0;
  left: 0;
  transform: translateX(-100.1%);
}

body.do-page-overlay-transition .lay-slide-from-left {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-slide-from-right {
  top: 0;
  right: 0;
  transform: translateX(100.1%);
}

body.do-page-overlay-transition .lay-slide-from-right {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-slide-from-bottom {
  left: 0;
  right: 0;
  bottom: 0;
  transform: translateY(100.1%);
}

body.do-page-overlay-transition .lay-slide-from-bottom {
  transition: transform 500ms cubic-bezier(0.075, 0.82, 0.165, 1);
}

.lay-fade {
  opacity: 0;
  top: 0;
  left: 0;
  right: 0;
  transform: translateY(-100.1%);
}

.lay-none {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-none .row .col {
  pointer-events: none;
}

.lay-none-right {
  transform: translate(0, 0);
  top: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-none-right .row .col {
  pointer-events: none;
}

.lay-none-left {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-none-left .row .col {
  pointer-events: none;
}

.lay-none-bottom {
  transform: translate(0, 0);
  bottom: 0;
  left: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-none-bottom .row .col {
  pointer-events: none;
}

.lay-none-top {
  transform: translate(0, 0);
  top: 0;
  left: 0;
  right: 0;
  pointer-events: none;
  opacity: 0;
}
.lay-none-top .row .col {
  pointer-events: none;
}

.lay-show-overlay.lay-slide-from-top {
  transform: translateY(0);
}

.lay-show-overlay.lay-slide-from-left {
  transform: translateX(0);
}

.lay-show-overlay.lay-slide-from-right {
  transform: translateX(0);
}

.lay-show-overlay.lay-slide-from-bottom {
  transform: translateY(0);
}

.lay-show-overlay.lay-fade {
  transform: translateY(0);
  animation: 350ms forwards overlayfadein;
  opacity: 1;
}

.lay-show-overlay.lay-fadeout {
  animation: 350ms forwards overlayfadeout;
}

.lay-show-overlay.lay-none,
.lay-show-overlay.lay-none-right,
.lay-show-overlay.lay-none-left,
.lay-show-overlay.lay-none-top,
.lay-show-overlay.lay-none-bottom {
  pointer-events: auto;
  opacity: 1;
}
.lay-show-overlay.lay-none .row .col,
.lay-show-overlay.lay-none-right .row .col,
.lay-show-overlay.lay-none-left .row .col,
.lay-show-overlay.lay-none-top .row .col,
.lay-show-overlay.lay-none-bottom .row .col {
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
.lay-overlay.lay-desktop-overlay-has-custom-bg > .lay-content,
.lay-overlay.lay-desktop-overlay-has-custom-bg > .lay-content > #grid {
  background-color: transparent;
}

#lay-project-overlay .project-overlay-grid-has-custom-bg.grid {
  background-color: transparent !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
}
#lay-project-overlay .project-overlay-grid-has-custom-bg.grid .grid-inner {
  background-color: transparent !important;
}
';