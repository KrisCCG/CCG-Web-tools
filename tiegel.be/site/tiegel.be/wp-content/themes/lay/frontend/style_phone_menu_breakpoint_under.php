<?php
return
'
body.mobile-menu-style_1.mobile-menu-has-animation.mobile-menu-animation-possible.animate-mobile-menu nav.mobile-nav {
  transition: transform 300ms cubic-bezier(0.52, 0.16, 0.24, 1);
}

.mobile-title.text {
  line-height: 1 !important;
}

body.mobile-menu-style_1 nav.mobile-nav::-webkit-scrollbar {
  display: none;
}
body.mobile-menu-style_1 nav.mobile-nav {
  scrollbar-width: none;
}
body.mobile-menu-style_1 nav.mobile-nav {
  transform: translateY(-99999px);
  overflow-y: scroll;
  -webkit-overflow-scrolling: touch;
  white-space: normal;
  width: 100%;
  top: 0;
  left: 0;
  bottom: auto;
}
body.mobile-menu-style_1 nav.mobile-nav .current-menu-item {
  opacity: 1;
}
body.mobile-menu-style_1 nav.mobile-nav li {
  display: block;
  margin-right: 0;
  margin-bottom: 0;
  padding: 0;
}
body.mobile-menu-style_1 nav.mobile-nav li a {
  display: block;
  opacity: 1;
  border-bottom-style: solid;
  border-bottom-width: 1px;
  transition: background-color 200ms ease;
  margin: 0;
}
body.mobile-menu-style_1 nav.mobile-nav li a:hover {
  opacity: 1;
}
body.mobile-menu-style_1 nav.mobile-nav li a .span-wrap {
  border-bottom: none;
}
body.mobile-menu-style_1 nav.mobile-nav li a:hover .span-wrap {
  border-bottom: none;
}

body.mobile-menu-style_2.mobile-menu-has-animation.mobile-menu-animation-possible nav.mobile-nav {
  transition: transform 500ms cubic-bezier(0.52, 0.16, 0.24, 1);
}

body.mobile-menu-style_2 .laynav .burger-wrap {
  position: absolute;
  right: 0;
  top: 0;
}
body.mobile-menu-style_2 nav.mobile-nav.active {
  transform: translateX(0);
}
body.mobile-menu-style_2 nav.mobile-nav::-webkit-scrollbar {
  display: none;
}
body.mobile-menu-style_2 nav.mobile-nav {
  scrollbar-width: none;
}
body.mobile-menu-style_2 nav.mobile-nav {
  box-sizing: border-box;
  z-index: 35;
  top: 0;
  height: 100vh;
  overflow-y: scroll;
  -webkit-overflow-scrolling: touch;
  white-space: normal;
  width: 100%;
  transform: translateX(100%);
}
body.mobile-menu-style_2 nav.mobile-nav li a {
  display: block;
  margin: 0;
  box-sizing: border-box;
  width: 100%;
}

body.mobile-menu-style_3.mobile-menu-has-animation.mobile-menu-animation-possible .mobile-nav ul {
  opacity: 0;
  transition: opacity 300ms cubic-bezier(0.52, 0.16, 0.24, 1) 200ms;
}

body.mobile-menu-style_3.mobile-menu-has-animation.mobile-menu-animation-possible.mobile-menu-open .mobile-nav ul {
  opacity: 1;
}

body.mobile-menu-style_3.mobile-menu-has-animation.mobile-menu-animation-possible nav.mobile-nav {
  transition: height 500ms cubic-bezier(0.52, 0.16, 0.24, 1);
}

body.mobile-menu-style_3 nav.mobile-nav.active {
  transform: translateX(0);
}
body.mobile-menu-style_3 nav.mobile-nav::-webkit-scrollbar {
  display: none;
}
body.mobile-menu-style_3 nav.mobile-nav {
  scrollbar-width: none;
}
body.mobile-menu-style_3 nav.mobile-nav {
  width: 100%;
  height: 0;
  box-sizing: border-box;
  z-index: 33;
  overflow-y: scroll;
  -webkit-overflow-scrolling: touch;
  white-space: normal;
  width: 100%;
}
body.mobile-menu-style_3 nav.mobile-nav li a {
  display: block;
  margin: 0;
  box-sizing: border-box;
  width: 100%;
}

body.mobile-menu-style_4.mobile-menu-has-animation.mobile-menu-animation-possible.animate-mobile-menu nav.mobile-nav {
  transition: transform 300ms cubic-bezier(0.52, 0.16, 0.24, 1);
}

body.mobile-menu-style_4 nav.mobile-nav::-webkit-scrollbar {
  display: none;
}
body.mobile-menu-style_4 nav.mobile-nav {
  scrollbar-width: none;
}
body.mobile-menu-style_4 nav.mobile-nav {
  width: 100%;
  height: 0;
  box-sizing: border-box;
  z-index: 29;
  overflow-y: scroll;
  -webkit-overflow-scrolling: touch;
  white-space: normal;
  width: 100%;
}
body.mobile-menu-style_4 nav.mobile-nav li a {
  display: block;
  margin: 0;
  box-sizing: border-box;
  width: 100%;
}

/**
 * Toggle Switch Globals
 *
 * All switches should take on the class `c-hamburger` as well as their
 * variant that will give them unique properties. This class is an overview
 * class that acts as a reset for all versions of the icon.
 */
.mobile-menu-style_1 .burger-wrap,
.mobile-menu-style_3 .burger-wrap,
.mobile-menu-style_4 .burger-wrap {
  z-index: 33;
}

.lay-mobile-icons-wrap {
  z-index: 33;
  top: 0;
  vertical-align: top;
}

.burger-wrap.right {
  padding-left: 10px;
}

.burger-wrap.left {
  padding-right: 10px;
}

.burger-wrap {
  font-size: 0;
  box-sizing: border-box;
  display: inline-block;
  cursor: pointer;
  vertical-align: top;
}

.burger-inner {
  position: relative;
}

.burger-default {
  border-radius: 0;
  overflow: hidden;
  margin: 0;
  padding: 0;
  width: 25px;
  height: 20px;
  font-size: 0;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  box-shadow: none;
  border-radius: none;
  border: none;
  cursor: pointer;
  background-color: transparent;
}

.burger-default:focus {
  outline: none;
}

.burger-default span {
  display: block;
  position: absolute;
  left: 0;
  right: 0;
  background-color: #000;
}

.default .burger-default span {
  height: 2px;
  top: 9px;
}
.default .burger-default span::before,
.default .burger-default span::after {
  height: 2px;
}
.default .burger-default span::before {
  top: -9px;
}
.default .burger-default span::after {
  bottom: -9px;
}

.default_thin .burger-default span {
  height: 1px;
  top: 9px;
}
.default_thin .burger-default span::before,
.default_thin .burger-default span::after {
  height: 1px;
}
.default_thin .burger-default span::before {
  top: -6px;
}
.default_thin .burger-default span::after {
  bottom: -6px;
}

.burger-default span::before,
.burger-default span::after {
  position: absolute;
  display: block;
  left: 0;
  width: 100%;
  background-color: #000;
  content: "";
}

/**
 * Style 2
 *
 * Hamburger to "x" (htx). Takes on a hamburger shape, bars slide
 * down to center and transform into an "x".
 */
.burger-has-animation .burger-default {
  transition: background 0.2s;
}
.burger-has-animation .burger-default span {
  transition: background-color 0.2s 0s;
}
.burger-has-animation .burger-default span::before,
.burger-has-animation .burger-default span::after {
  transition-timing-function: cubic-bezier(0.04, 0.04, 0.12, 0.96);
  transition-duration: 0.2s, 0.2s;
  transition-delay: 0.2s, 0s;
}
.burger-has-animation .burger-default span::before {
  transition-property: top, transform;
  -webkit-transition-property: top, -webkit-transform;
}
.burger-has-animation .burger-default span::after {
  transition-property: bottom, transform;
  -webkit-transition-property: bottom, -webkit-transform;
}
.burger-has-animation .burger-default.active span::before,
.burger-has-animation .burger-default.active span::after {
  transition-delay: 0s, 0.2s;
}

/* active state, i.e. menu open */
.burger-default.active span {
  background-color: transparent !important;
}

.burger-default.active span::before {
  transform: rotate(45deg);
  top: 0;
}

.burger-default.active span::after {
  transform: rotate(-45deg);
  bottom: 0;
}

.mobile-menu-icon {
  z-index: 31;
}

.mobile-menu-icon {
  cursor: pointer;
}

.burger-custom-wrap-close {
  display: none;
}

body.mobile-menu-style_2 .mobile-nav .burger-custom-wrap-close {
  display: inline-block;
}
body.mobile-menu-style_2 .burger-custom-wrap-open {
  display: inline-block;
}

body.mobile-menu-open.mobile-menu-style_3 .burger-custom-wrap-close,
body.mobile-menu-open.mobile-menu-style_4 .burger-custom-wrap-close,
body.mobile-menu-open.mobile-menu-style_1 .burger-custom-wrap-close {
  display: inline-block;
}
body.mobile-menu-open.mobile-menu-style_3 .burger-custom-wrap-open,
body.mobile-menu-open.mobile-menu-style_4 .burger-custom-wrap-open,
body.mobile-menu-open.mobile-menu-style_1 .burger-custom-wrap-open {
  display: none;
}

/**
 * Toggle Switch Globals
 *
 * All switches should take on the class `c-hamburger` as well as their
 * variant that will give them unique properties. This class is an overview
 * class that acts as a reset for all versions of the icon.
 */
body.mobile_menu_bar_not_hidden .lay-mobile-icons-wrap.contains-cart-icon .burger-wrap-new {
  padding-top: 6px;
}

.burger-wrap-new.burger-wrap {
  padding-left: 5px;
  padding-right: 5px;
}

.lay-mobile-icons-wrap.contains-cart-icon.custom-burger .lay-cart-icon-wrap {
  padding-top: 0;
}

.burger-new {
  border-radius: 0;
  overflow: hidden;
  margin: 0;
  padding: 0;
  width: 30px;
  height: 30px;
  font-size: 0;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  box-shadow: none;
  border-radius: none;
  border: none;
  cursor: pointer;
  background-color: transparent;
}

.burger-new:focus {
  outline: none;
}

.burger-new .bread-top,
.burger-new .bread-bottom {
  transform: none;
  z-index: 4;
  position: absolute;
  z-index: 3;
  top: 0;
  left: 0;
  width: 30px;
  height: 30px;
}

.burger-has-animation .bread-top,
.burger-has-animation .bread-bottom {
  transition: transform 0.1806s cubic-bezier(0.04, 0.04, 0.12, 0.96);
}
.burger-has-animation .bread-crust-bottom,
.burger-has-animation .bread-crust-top {
  transition: transform 0.1596s cubic-bezier(0.52, 0.16, 0.52, 0.84) 0.1008s;
}
.burger-has-animation .burger-new.active .bread-top,
.burger-has-animation .burger-new.active .bread-bottom {
  transition: transform 0.3192s cubic-bezier(0.04, 0.04, 0.12, 0.96) 0.1008s;
}
.burger-has-animation .burger-new.active .bread-crust-bottom,
.burger-has-animation .burger-new.active .bread-crust-top {
  transition: transform 0.1806s cubic-bezier(0.04, 0.04, 0.12, 0.96);
}

.burger-new .bread-crust-top,
.burger-new .bread-crust-bottom {
  display: block;
  width: 17px;
  height: 1px;
  background: #000;
  position: absolute;
  left: 7px;
  z-index: 1;
}

.bread-crust-top {
  top: 14px;
  transform: translateY(-3px);
}

.bread-crust-bottom {
  bottom: 14px;
  transform: translateY(3px);
}

.burger-new.active .bread-top {
  transform: rotate(45deg);
}
.burger-new.active .bread-crust-bottom {
  transform: none;
}
.burger-new.active .bread-bottom {
  transform: rotate(-45deg);
}
.burger-new.active .bread-crust-top {
  transform: none;
}

body.mobile-menu-pill_menu_style .burger-wrap,
body.mobile-menu-pill_menu_style .mobile-menu-close-custom {
  display: none;
}
body.mobile-menu-pill_menu_style nav.mobile-nav {
  z-index: 35;
  line-height: 1;
  white-space: nowrap;
}

.mobile-menu-style-pill_menu_style ul {
  display: flex;
}
.mobile-menu-style-pill_menu_style ul span {
  white-space: nowrap;
}

.mobile-menu-pill {
  position: absolute;
  z-index: -1;
  box-sizing: content-box;
  opacity: 0;
}

.mobile-menu-pill.mobile-menu-opacity-pill-transition {
  transition: opacity 0.7s ease-in-out;
}

.mobile-menu-pill.mobile-menu-pill-transition {
  transition: transform 0.7s cubic-bezier(0.93, 0, 0.4, 1.17), width 0.7s cubic-bezier(0.93, 0, 0.4, 1.17), opacity 0.7s ease-in-out;
}

.lay-fixed-header-hide-on-phone {
  display: none;
}

.tagline {
  display: none;
}

.title a,
.title {
  opacity: 1;
}

.sitetitle {
  display: none;
}

.navbar {
  display: block;
  top: 0;
  left: 0;
  bottom: auto;
  right: auto;
  width: 100%;
  z-index: 30;
  border-bottom-style: solid;
  border-bottom-width: 1px;
}

.mobile-title.image {
  font-size: 0;
}

.mobile-title.text {
  line-height: 1;
  display: inline-flex;
}
.mobile-title.text > span {
  align-self: center;
}

.mobile-title {
  z-index: 31;
  display: inline-block;
  box-sizing: border-box;
}
.mobile-title img {
  box-sizing: border-box;
  height: 100%;
}

nav.primary,
nav.second_menu,
nav.third_menu,
nav.fourth_menu {
  display: none;
}

body.mobile-menu-style_desktop_menu .burger-wrap,
body.mobile-menu-style_desktop_menu .mobile-menu-close-custom {
  display: none;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav {
  z-index: 35;
  line-height: 1;
  white-space: nowrap;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav li {
  vertical-align: top;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav li:last-child {
  margin-right: 0 !important;
  margin-bottom: 0 !important;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  font-size: 0;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav a {
  text-decoration: none;
}
body.mobile-menu-style_desktop_menu nav.mobile-nav span {
  border-bottom-style: solid;
  border-bottom-width: 0;
}

body.mobile-menu-button_menu_style .burger-wrap,
body.mobile-menu-button_menu_style .mobile-menu-close-custom {
  display: none;
}
body.mobile-menu-button_menu_style nav.mobile-nav {
  z-index: 35;
  line-height: 1;
  white-space: nowrap;
}
body.mobile-menu-button_menu_style nav.mobile-nav li {
  vertical-align: top;
}
body.mobile-menu-button_menu_style nav.mobile-nav li:last-child {
  margin-right: 0 !important;
  margin-bottom: 0 !important;
}
body.mobile-menu-button_menu_style nav.mobile-nav ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  font-size: 0;
}
body.mobile-menu-button_menu_style nav.mobile-nav a {
  text-decoration: none;
}
body.mobile-menu-button_menu_style nav.mobile-nav span {
  border-bottom-style: solid;
  border-bottom-width: 0;
}

.mobile_sitetitle_fade_out_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_sitetitle_move_away_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menu_fade_out_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menu_move_away_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menubar_move_away_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menubar_fade_out_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menuicons_fade_out_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

.mobile_menuicons_move_away_when_scrolling_down {
  transition: all ease-in-out 300ms;
}

body.lay-hide-phone-menu .mobile_sitetitle_fade_out_when_scrolling_down {
  opacity: 0;
  pointer-events: none;
}
body.lay-hide-phone-menu .mobile_menu_fade_out_when_scrolling_down {
  opacity: 0;
  pointer-events: none;
}
body.lay-hide-phone-menu .mobile_menubar_move_away_when_scrolling_down {
  transform: translateY(-100%);
}
body.lay-hide-phone-menu .mobile_menubar_fade_out_when_scrolling_down {
  opacity: 0 !important;
  pointer-events: none;
}
body.lay-hide-phone-menu .mobile_menuicons_fade_out_when_scrolling_down {
  opacity: 0 !important;
  pointer-events: none;
}
body.lay-hide-phone-menu .mobile_menuicons_move_away_when_scrolling_down {
  transform: translateY(-100%);
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
';