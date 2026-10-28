# HiddenMenu

[Deutsch](README.de.md) | English

Module for the IP-Symcon tile visualization by Ueli Schweizer / Cassanis Automation.
Prefix: `HIDDM`.

HiddenMenu displays the HTML content of a string variable as a tile. An invisibly
recognized gesture sequence opens a menu with script actions and navigation targets.

> Hiding is a convenience feature, not access control. Anyone who can see the
> tile can also trigger the configured scripts without the gesture.

## Table of contents

1. [Requirements](#1-requirements)
2. [Installation and first test](#2-installation-and-first-test)
3. [Content](#3-content)
4. [Gestures](#4-gestures)
5. [Menu items](#5-menu-items)
6. [Appearance](#6-appearance)
7. [Script parameters](#7-script-parameters)
8. [Support](#8-support)
9. [Changelog](#9-changelog)

## 1. Requirements

- IP-Symcon 8.2 or later (`openObject()` in the HTML SDK).
- HiddenMenu is displayed as a regular tile. From version 9.1, it can also be
  shown in fullscreen mode. Older versions support the tile view only.

## 2. Installation and first test

1. Install the library via the Module Store or Module Control and create a
   HiddenMenu instance.
2. Optionally select a string variable containing HTML under «Content». Without
   a selection, the bundled about page is shown.
3. Under «Menu items», enter a caption and select a target (a script to run, or
   e.g. a variable to display) and apply.
4. Show the instance as a tile in the visualization and quickly tap or click the
   content three times. The menu appears on the third tap.
5. Trigger a menu item.

## 3. Content

The tile shows the value of the string variable selected under «Content»,
typically with the profile `~HTMLBox`. When the value changes, the tile updates
immediately; an open menu and a gesture in progress are preserved.

Without a variable, the bundled about page with copyright and MIT license is
shown (`default.html`).

Notes:

- `<script>` elements in the content are not executed.
- No gestures are recognized over an `<iframe>` in the content, because the
  events go to the embedded document.

## 4. Gestures

The default is a triple tap. The «Gesture sequence to open» table replaces this.
Multiple rows are recognized from top to bottom; the order can be changed by
drag & drop. Example: «Tap 3 ×», then «Swipe down».

| Gesture | Recognition |
|---|---|
| Tap 1 to 5 times | as soon as the configured count is reached |
| Long press | on release, after at least «Long press duration» |
| Swipe (8 directions) | at least «Minimum swipe distance», at most 1800 ms, 45° sectors |

Settings under «Recognition», with defaults:

| Setting | Default |
|---|---|
| Maximum pause between taps | 600 ms |
| Long press duration | 800 ms |
| Time until the next gesture | 2500 ms |
| Minimum swipe distance | 60 px |

Notes:

- A tap or long press tolerates up to 14 px of movement. Moving away and back to
  the starting point does not count as a tap.
- Consecutive tap steps merge: «Tap 2 ×» followed by «Tap 2 ×» is recognized
  the same as «Tap 4 ×».
- Wrong input, multi-touch, loss of focus or cancelled touches discard the
  sequence in progress without visible feedback.
- Links, buttons and input fields in the content remain usable and do not count
  as gestures.
- The gesture area prevents scrolling and text selection, so keep the content
  short.

## 5. Menu items

Each item has a caption, an optional icon, a background color and a target:

- **Script:** is executed on the server.
- **Category:** is opened via `openObject()` on the triggering client.
- **Other objects** (e.g. variables): are shown as a maximized tile via
  `openObject()`.
- **No target** («None», Symcon object ID 1): the item is a heading without click function, displayed like
  the other items.

After a script is triggered, the menu stays open. Script errors appear in the
Symcon message log.

«Back» returns from the menu to the content view. The menu and view
state are local to each client.

An invalid item puts the instance into error state and locks the menu. The
cause is shown in the configuration form.

## 6. Appearance

The view and each menu item can have a background color. With «Transparent»,
the background stays transparent and the text color is inherited. For custom
colors, the text color is chosen as in the ListBox:
brightness = 0.299 × red + 0.587 × green + 0.114 × blue; below 189 white,
otherwise black.

Icons are entered by name as in the TreeView, e.g. `gear`, `house` or
`thermometer`. Symcon's `/icons.js` is used, including kit icons. Empty icons
and `Transparent` take up no space.

## 7. Script parameters

Executed scripts additionally receive:

| Parameter | Content |
|---|---|
| `$_IPS['HIDDM_INSTANCE']` | ID of the HiddenMenu instance |
| `$_IPS['HIDDM_ITEM']` | row index of the menu item, starting at 0 |

The script ID comes exclusively from the server-side configuration; the client
only sends the row index.

## 8. Support

This module is free of charge. Donations to support the author are gratefully
accepted: PayPal to ueli.schweizer@cassanis.fr

## 9. Changelog

**1.0 Build 11:**

- An empty target or content variable is stored as object ID 1, the Symcon
  default for «None». Values 0 and -1 from earlier builds are still read as
  empty.
- Documentation: HiddenMenu is a regular tile; fullscreen is optional from 9.1.

**1.0 Build 10:**

- The displayed content comes from a string variable instead of the HTML field
  in the configuration and updates immediately when it changes.
- Close button, close target and the view after a target call are removed; the
  menu stays open after a script.
- Configuration form, status messages and tile texts in English and German,
  following the IP-Symcon language setting.
- Fullscreen mode can be used from IP-Symcon 9.1.
- **Update from Build 9 and older:** custom HTML content is automatically moved
  into the new string variable «Content» («Inhalt» in German) below the
  instance and selected as
  content. Unchanged bundled about pages are replaced by the current one. A
  configured close target is no longer used.

**1.0 Build 9:** Initial release.
