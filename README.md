# HiddenMenu

IP-Symcon-Bibliothek von Ueli Schweizer / Cassanis Automation.

| Modul | Beschreibung |
|---|---|
| [HiddenMenu](HiddenMenu/README.md) | HTML-Kachel mit einem Menü, das sich über eine unsichtbare Gestenfolge öffnet |

## Voraussetzungen

IP-Symcon ab Version 8.2. Ab 9.1 wird die Kachel im Vollbild-Modus angezeigt.

## Installation

Über den Module Store oder im Module Control mit der URL
`https://github.com/wowbagger60/HiddenMenu`.

## Entwicklung

Die Gesten- und Eingabelogik ist mit Node-Tests abgedeckt:

```
node tests/gestures.test.js
node tests/input.test.js
node tests/layout.test.js
node tests/presentation.test.js
```

Die Tests simulieren Ereignisse und ersetzen keinen Test in der echten
Symcon-Visualisierung.

## Unterstützung

Dieses Modul ist kostenlos. Schenkungen als Unterstützung für den Autor werden
gerne angenommen: PayPal an ueli.schweizer@cassanis.fr

## Lizenz

MIT, siehe [LICENSE](LICENSE).
