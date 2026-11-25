<?php
return
'
body.intro-loading .lay-content {
  visibility: hidden;
}

.intro.animatehide {
  pointer-events: none;
}

#intro-region.hide {
  display: none;
}

.intro {
  opacity: 1;
  transform: scale(1);
  cursor: pointer;
  display: block;
}

.intro .mediawrap {
  height: 100vh;
  width: 100%;
}

.intro img.lazyloading,
.intro img.lazyload {
  opacity: 0;
}

.intro img.lazyloaded,
.intro img.loaded {
  opacity: 1;
  transition: opacity 400ms;
}

.intro {
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  position: fixed;
  overflow: hidden;
  z-index: 50;
}

.intro .media {
  transform: translate(-50%, -50%);
  position: fixed;
  left: 50%;
  top: 50%;
}

.intro .mediawrap.followmovement .media {
  transform: translate(-50%, -50%) scale(1.15);
}

.intro .mediawrap.w100 .media {
  width: 100%;
  height: auto;
}

.intro .mediawrap.h100 .media {
  width: auto;
  height: 100%;
}

.intro-svg-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 60;
}

.intro-svg-overlay,
.intro_text {
  pointer-events: none;
}

.intro_text {
  position: absolute;
  z-index: 65;
  white-space: pre-line;
}

@media (orientation: landscape) {
  .intro-portrait-media {
    display: none;
  }
}
@media (orientation: portrait) {
  .intro-landscape-media {
    display: none;
  }
}
';