<?php
return
'
nav.mobile-nav {
  display: none;
}

.lay-mobile-icons-wrap {
  display: none;
  white-space: nowrap;
}

.mobile-title {
  display: none;
}

nav.laynav li {
  display: inline-block;
}

nav.laynav {
  white-space: nowrap;
}

.navbar {
  position: fixed;
  z-index: 10;
  width: 100%;
  transform: translateZ(0);
}

.sitetitle.txt .sitetitle-txt-inner span,
nav.laynav span {
  text-decoration-style: underline;
}

.sitetitle.txt .sitetitle-txt-inner {
  margin-top: 0;
  margin-bottom: 0;
}

nav.laynav {
  z-index: 20;
  line-height: 1;
}
nav.laynav .span-wrap {
  display: inline-block;
}
nav.laynav ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  font-size: 0;
  display: flex;
}
nav.laynav a {
  text-decoration: none;
}
nav.laynav li {
  vertical-align: top;
}
nav.laynav li:last-child {
  margin-right: 0 !important;
  margin-bottom: 0 !important;
}

.nav-style-button ul {
  align-items: first baseline;
}

.nav-pill {
  position: absolute;
  z-index: -1;
  box-sizing: content-box;
  opacity: 0;
}

.nav-pill.nav-opacity-pill-transition {
  transition: opacity 0.7s ease-in-out;
}

.nav-pill.nav-pill-transition {
  transition: transform 0.7s cubic-bezier(0.93, 0, 0.4, 1.17), width 0.7s cubic-bezier(0.93, 0, 0.4, 1.17), opacity 0.7s ease-in-out;
}
';