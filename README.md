# HiddenMenu

[Deutsch](README.de.md) | English

IP-Symcon library by Ueli Schweizer / Cassanis Automation.

| Module | Description |
|---|---|
| [HiddenMenu](HiddenMenu/README.md) | HTML tile with a menu that opens via an invisible gesture sequence |

## Requirements

IP-Symcon 8.2 or later. From 9.1, the tile can also be shown in fullscreen mode.

## Installation

Via the Module Store, or in Module Control with the URL
`https://github.com/wowbagger60/HiddenMenu`.

## Development

The gesture and input logic is covered by Node tests:

```
node tests/gestures.test.js
node tests/input.test.js
node tests/layout.test.js
node tests/presentation.test.js
```

The tests simulate events and do not replace a test in the real Symcon
visualization.

## Support

This module is free of charge. Donations to support the author are gratefully
accepted: PayPal to ueli.schweizer@cassanis.fr

## License

MIT, see [LICENSE](LICENSE).
