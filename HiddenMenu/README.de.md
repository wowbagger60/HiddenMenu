# HiddenMenu

Deutsch | [English](README.md)

Modul für die IP-Symcon Kachel-Visualisierung von Ueli Schweizer / Cassanis Automation.
Präfix: `HIDDM`.

HiddenMenu zeigt den HTML-Inhalt einer String-Variable als Kachel. Eine unsichtbar
erkannte Gestenfolge öffnet ein Menü mit Scriptaktionen und Navigationszielen.

> Das Verstecken ist eine Komfortfunktion, keine Zugriffskontrolle. Wer die
> Kachel sehen kann, kann die konfigurierten Scripts auch ohne Geste auslösen.

## Inhaltsverzeichnis

1. [Voraussetzungen](#1-voraussetzungen)
2. [Installation und erster Test](#2-installation-und-erster-test)
3. [Inhalt](#3-inhalt)
4. [Gesten](#4-gesten)
5. [Menüeinträge](#5-menüeinträge)
6. [Darstellung](#6-darstellung)
7. [Scriptparameter](#7-scriptparameter)
8. [Unterstützung](#8-unterstützung)
9. [Änderungen](#9-änderungen)

## 1. Voraussetzungen

- IP-Symcon ab Version 8.2 (`openObject()` im HTML-SDK).
- HiddenMenu wird als normale Kachel angezeigt. Ab Version 9.1 kann es
  zusätzlich im Vollbild-Modus angezeigt werden. Ältere Versionen unterstützen
  nur die Kachelansicht.

## 2. Installation und erster Test

1. Bibliothek über den Module Store oder das Module Control installieren und eine
   HiddenMenu-Instanz anlegen.
2. Optional unter «Inhalt» eine String-Variable mit HTML auswählen. Ohne Auswahl
   wird die mitgelieferte About-Seite angezeigt.
3. Unter «Menüeinträge» eine Beschriftung und ein Target auswählen (zum Ausführen
   ein Script, zur Anzeige beispielsweise eine Variable) und übernehmen.
4. Instanz in der Visualisierung als Kachel anzeigen und dreimal zügig auf den
   HTML-Inhalt tippen oder klicken. Mit dem dritten Tipp erscheint das Menü.
5. Menüeintrag auslösen.

## 3. Inhalt

Angezeigt wird der Wert der unter «Inhalt» gewählten String-Variable, typischerweise
mit dem Profil `~HTMLBox`. Ändert sich der Wert, aktualisiert sich die Kachel
sofort; ein geöffnetes Menü und eine laufende Gestenfolge bleiben dabei erhalten.

Ohne Variable wird die mitgelieferte About-Seite mit Copyright und MIT-Lizenz
angezeigt (`default.html`).

Hinweise:

- `<script>`-Elemente im Inhalt werden nicht ausgeführt.
- Über einem `<iframe>` im Inhalt werden keine Gesten erkannt, da die Ereignisse
  an das eingebettete Dokument gehen.

## 4. Gesten

Standard ist dreimal Tippen. Die Tabelle «Gestenfolge» ersetzt diesen Zugang.
Mehrere Zeilen werden von oben nach unten erkannt; die Reihenfolge lässt sich per
Drag & Drop ändern. Beispiel: «3 × Tippen», danach «Streichen nach unten».

| Geste | Erkennung |
|---|---|
| 1 × bis 5 × Tippen | sobald die konfigurierte Anzahl erreicht ist |
| Langes Drücken | beim Loslassen, nach mindestens «Dauer für langes Drücken» |
| Streichen (8 Richtungen) | mindestens «Mindeststrecke», höchstens 1800 ms, Sektoren von 45° |

Einstellungen unter «Erkennung» (Standardwerte):

| Einstellung | Standard |
|---|---|
| Maximale Pause zwischen Tipps | 600 ms |
| Dauer für langes Drücken | 800 ms |
| Wartezeit bis zur nächsten Geste | 2500 ms |
| Mindeststrecke für Streichen | 60 px |

Hinweise:

- Ein Tipp oder langes Drücken toleriert bis 14 px Bewegung. Wegwischen und
  zurück zum Ausgangspunkt zählt nicht als Tipp.
- Aufeinanderfolgende Tipp-Schritte verschmelzen: «2 × Tippen» gefolgt von
  «2 × Tippen» wird genauso erkannt wie «4 × Tippen».
- Falsche Eingaben, Mehrfachberührungen, Fokusverlust oder abgebrochene
  Berührungen verwerfen die laufende Folge ohne sichtbare Rückmeldung.
- Links, Buttons und Eingabefelder im HTML-Inhalt bleiben bedienbar und zählen
  nicht als Geste.
- Die Gestenfläche unterbindet Scrollen und Textauswahl; den HTML-Inhalt daher
  kurz halten.

## 5. Menüeinträge

Jeder Eintrag hat eine Beschriftung, ein optionales Icon, eine Hintergrundfarbe
und ein Target:

- **Script:** wird auf dem Server ausgeführt.
- **Kategorie:** wird per `openObject()` auf dem auslösenden Client geöffnet.
- **Andere Objekte** (z. B. Variablen): werden per `openObject()` als maximierte
  Kachel angezeigt.
- **Kein Target** («Kein(e)», Symcon-Objekt-ID 1): der Eintrag ist eine Zwischenüberschrift ohne Klickfunktion,
  mit derselben Darstellung wie die übrigen Einträge.

Nach dem Auslösen eines Scripts bleibt das Menü geöffnet. Scriptfehler erscheinen
im Symcon-Meldungsfenster.

«Zurück» führt vom Menü zur Inhaltsansicht. Menü und Ansichtszustand sind lokal
pro Client.

Ein ungültiger Eintrag setzt die Instanz in den Fehlerstatus und sperrt das Menü.
Die Ursache steht im Konfigurationsformular.

## 6. Darstellung

Die Ansicht und jeder Menüeintrag können eine Hintergrundfarbe erhalten. Bei
«Transparent» bleibt der Hintergrund transparent und die Textfarbe wird geerbt.
Bei eigenen Farben wird die Textfarbe wie bei der ListBox gewählt:
Helligkeit = 0,299 × Rot + 0,587 × Grün + 0,114 × Blau; unter 189 weiss,
sonst schwarz.

Icons werden wie beim TreeView als Name eingetragen, z. B. `gear`, `house` oder
`thermometer`. Verwendet wird Symcons `/icons.js`, einschliesslich Kit-Icons.
Leere Icons und `Transparent` reservieren keinen Platz.

## 7. Scriptparameter

Ausgeführte Scripts erhalten zusätzlich:

| Parameter | Inhalt |
|---|---|
| `$_IPS['HIDDM_INSTANCE']` | ID der HiddenMenu-Instanz |
| `$_IPS['HIDDM_ITEM']` | Zeilenindex des Menüeintrags, ab 0 |

Die Script-ID stammt ausschliesslich aus der Konfiguration auf dem Server; der
Client übermittelt nur den Zeilenindex.

## 8. Unterstützung

Dieses Modul ist kostenlos. Schenkungen als Unterstützung für den Autor werden
gerne angenommen: PayPal an ueli.schweizer@cassanis.fr

## 9. Änderungen

**1.0 Build 10:**

- Der angezeigte Inhalt stammt aus einer String-Variable statt aus dem HTML-Feld
  der Konfiguration und aktualisiert sich bei Änderungen sofort.
- Schliessen-Button, Schliessen-Ziel und «Ansicht nach Targetaufruf» entfallen;
  nach einem Script bleibt das Menü geöffnet.
- Ab IP-Symcon 9.1 ist zusätzlich die Vollbild-Anzeige möglich.
- Ein leeres Target wird als Objekt-ID 1 gespeichert, dem Symcon-Standard für «Kein(e)».
- Konfigurationsformular, Statusmeldungen und Kacheltexte auf Deutsch und
  Englisch, abhängig von der Spracheinstellung von IP-Symcon.
- **Update von Build 9 und älter:** Ein eigener HTML-Inhalt wird automatisch in
  die neue String-Variable «Inhalt» («Content» auf Englisch) unterhalb der
  Instanz übernommen und als
  Inhalt eingetragen. Unveränderte mitgelieferte About-Seiten werden durch die
  aktuelle ersetzt. Ein eingestelltes Schliessen-Ziel wird nicht mehr verwendet.

**1.0 Build 9:** Erstveröffentlichung.
