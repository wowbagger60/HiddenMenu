# HiddenMenu 1.0 · Build 9

IP-Symcon-Modul von Ueli Schweizer / Cassanis Automation. Präfix: HIDDM.

HiddenMenu zeigt frei konfigurierbaren HTML-Inhalt. Eine unsichtbar erkannte
Gestenfolge öffnet ein Menü mit Scriptaktionen und Navigationszielen. Verstecken ist eine Komfortfunktion,
keine Zugriffskontrolle. Es gibt keine eigene öffentliche Bedien-API.

## Erster Test

1. Modulbibliothek wie üblich installieren und eine HiddenMenu-Instanz anlegen.
2. Unter «Menüeinträge» eine Beschriftung und das gewünschte Target auswählen
   (zum Ausführen ein Testscript, zur Anzeige beispielsweise eine Variable).
3. Optional eine Kategorie als «Schliessen-Ziel» auswählen und übernehmen.
4. Instanz als Kachel anzeigen. Dreimal zügig auf den HTML-Inhalt tippen oder mit
   der linken Maustaste klicken. Mit dem dritten Tipp erscheint das Menü.
5. Menüeintrag auslösen. Das konfigurierte Script wird gestartet.

Standard ist dreimal Tippen. Die Tabelle «Gestenfolge» ersetzt diesen Zugang.
Mehrere Zeilen werden in der angezeigten Reihenfolge erkannt; Reihenfolge per
Drag & Drop ändern. Beispiel: «3 × Tippen», danach «Streichen nach unten».

## Gesten

- Ein- bis fünfmal Tippen: Erkennung unmittelbar beim Erreichen der konfigurierten Anzahl.
  Die maximale Pause zwischen Tipps beträgt bei neuen Instanzen 600 ms. Bei bestehenden
  Instanzen bleibt der eingestellte Wert erhalten; zum Testen auf 600 ms erhöhen.
- Langes Drücken: mindestens 800 ms, Erkennung beim Loslassen.
- Streichen in acht Richtungen: mindestens 60 CSS-Pixel, maximal 1800 ms.
- Richtungen werden in Sektoren von 45 Grad unterschieden.
- Ein Tipp/Drücken toleriert bis 14 CSS-Pixel Bewegung. Zurückwischen zum
  Ausgangspunkt wird nicht als Tipp gewertet.
- Nächsten Schritt standardmässig innerhalb 2500 ms beginnen.
- Falsche Eingaben, Mehrfachberührungen, Fokusverlust oder abgebrochene Pointer-
  Ereignisse verwerfen die laufende Folge ohne sichtbare Rückmeldung.
- Gesten auf Links, Buttons und Eingabefeldern im HTML-Inhalt zählen nicht mit.
- Die Gestenfläche unterbindet normales Scrollen und Textauswahl; HTML kurz halten.

## Darstellung und About-Seite

Die gesamte Ansicht und jeder Menüeintrag können eine Hintergrundfarbe erhalten.
Bei Transparent bleibt der Hintergrund transparent; die Textfarbe wird geerbt. Für eigene
Farben wird die Textfarbe wie bei ListBox gewählt: Helligkeit = 0.299 × Rot +
0.587 × Grün + 0.114 × Blau; unter 189 weiss, ansonsten schwarz.

Icons werden wie bei TreeView als Name eingetragen, beispielsweise `gear`, `house`
oder `thermometer`. Leere Icons und `Transparent` reservieren keinen Platz. Es wird
Symcons `/icons.js` verwendet, einschliesslich der dort verfügbaren Kit-Icons.

Das mitgelieferte HTML ist eine zentrierte About-Seite mit Copyright und vollständiger
MIT-Lizenz. Der Lizenztext hat einen begrenzten Bereich für die Anzeige. Auch dort werden
Gesten erkannt. Textauswahl im HTML-Bereich ist unterbunden; echte Links und
Bedienelemente bleiben von der Erkennung ausgenommen.
Bestehender HTML-Inhalt wird beim Update erhalten. Über «HTML-Ansicht → Mitgeliefertes
HTML einsetzen» lässt er sich durch die neue Vorlage ersetzen; eigene Anpassungen
werden dabei überschrieben. Die Vorlage steht zusätzlich in `HiddenMenu/default.html`.

## Menü und Schliessen

Es gibt keinen zusätzlichen Menütitel. Bei Bedarf lässt sich die erste Menüzeile
mit leerem Target als eigene Überschrift verwenden.

Jeder Eintrag enthält eine Beschriftung und ein einziges Target-Feld. Ist das
gewählte Objekt ein Script, wird es ausgeführt. Variablen und alle anderen
Objekte werden per `openObject()` auf dem auslösenden Client geöffnet; für
Variablen gilt damit die normale Symcon-Darstellung. Kategorien werden geöffnet,
andere Objekte als maximierte Kachel angezeigt.

Ein leeres Target wird als Zwischenüberschrift mit derselben Zeilendarstellung
wie ein Aktionsziel angezeigt, aber ohne Klickfunktion. Beschriftung, Icon und
Hintergrund bleiben nutzbar. 0, -1 und der SelectObject-Leerwert 1 werden intern als leeres Target (0) behandelt. Die
Einstellung «Ansicht nach Targetaufruf» wird bei Zwischenüberschriften ignoriert.
Trennlinien werden nicht erzeugt.

Bei Einträgen ohne TargetID kann eine ältere ScriptID übernommen werden.
Ein vorhandenes TargetID-Feld hat immer Vorrang, auch wenn es leer ist. Beim
Speichern der Konfiguration wird nur die neue Struktur verwendet.

«Ansicht nach Targetaufruf» bestimmt für ausgeführte Scripts die Ansicht nach
Auslösung: Menü, HTML-Ansicht oder Schliessen-Ziel. «Zurück» führt vom Menü zur
HTML-Ansicht. Das Menü und dessen Zustand sind clientlokal.

Der separate Schliessen-Button nutzt `openObject()` mit der konfigurierten Kategorie.
Bei Ziel 0 wird er ausgeblendet. Die Folgeaktion «Schliessen-Ziel» erfordert ein Ziel.
Ein ungültiger Eintrag setzt die Instanz auf Fehlerstatus und sperrt das Menü.

Das Script erhält zusätzliche Parameter `$_IPS['HIDDM_INSTANCE']` (Instanz-ID)
und `$_IPS['HIDDM_ITEM']` (Zeilenindex ab 0). Die Script-ID stammt ausschliesslich
aus der serverseitigen Konfiguration. Das SDK bestätigt das Ende einer Script-
ausführung nicht: Der Ansichtswechsel erfolgt nach dem Absenden des Aufrufs,
nicht nach Abschluss des Scripts. Scriptfehler im Symcon-Meldungsfenster prüfen.

## Voraussetzungen und Testgrenzen

Bibliothek: Symcon ab 8.2 (openObject). Ab 9.1 wird der dokumentierte Modus
`INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN` automatisch genutzt, sofern
verfügbar. Vor 9.1 wird der normale HTML-SDK-Modus verwendet; dort zuerst als
normale Kachel testen. Appbar-/Vollbilddarstellung hängt von Symcon und Client ab.
Ein automatisches Ermitteln des Rücksprungziels ist nicht implementiert.

Die Gestenlogik und die Maus-/Touch-Ereignisadapter wurden mit insgesamt
56 automatisierten Node-Tests und 23 zusätzlichen Darstellungsprüfungen geprüft. Diese Tests simulieren Ereignisse und
ersetzen keinen Test der realen Browser-/Symcon-Einbettung. Erste Praxistests in Symcon bestätigen
Mehrfachtippen, kombinierte Gesten und Mausbedienung. Die neuen Darstellungsoptionen
und native Clients sind separat zu prüfen. Keine PHP-Laufzeit im
Entwicklungscontainer verfügbar; PHP noch auf dem Zielsystem prüfen.

Quellen:
- https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/openobject/
- https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/requestaction/
- https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setvisualizationtype/

## Entwicklerprüfung

`node tests/gestures.test.js`

`node tests/input.test.js`

`node tests/layout.test.js`

`node tests/presentation.test.js`

## Änderungen

1.0 Build 9: Konfigurationsfeld Menütitel und zusätzliche Titelanzeige entfernt.
About-Vorlage auf 1.0 Build 9 aktualisiert. Basis für die ausführliche Testphase.

0.1 Build 8: SelectObject liefert bei «Kein(e)» auch den dokumentierten Default 1.
Dieser wird vor Validierung und Darstellung auf das interne leere Target 0
normalisiert. Damit sind Zwischenüberschriften auch bei dieser Auswahl gültig.

0.1 Build 7: Mindestversion auf den dokumentierten Wert 8.2 korrigiert. Exakte
ListBox-Schriftfarbenregel mit Helligkeitsgrenze 189. Explizite TargetID hat Vorrang
vor allen alten Script-Feldern. Konkrete Validierungsfehler mit Eintragsnummer und
Ursache im Formular und Debug, statt einer pauschalen Sammelmeldung.

0.1 Build 6: Transparente Hintergründe ohne graue Standardfläche; explizite weisse
Schrift auf dunklen Farben. Leere Targets einschliesslich -1 als Zwischenüberschrift,
keine Trennlinien. Veraltete Script-Felder übersteuern kein explizites Target mehr.
Bezeichnung «Ansicht nach Targetaufruf». Gestenerkennung auch im Lizenzbereich
bisheriger HTML-Vorlagen; Textauswahl im HTML-Bereich verhindert. 9.1-Vollbild
bleibt optional, Mindestversion 9.0 bleibt bestehen.

0.1 Build 5: Passive Überschriften und Trennlinien mit Target 0. Hintergrundfarben
für Ansicht und Einträge; optionale TreeView-kompatible Icons. Zentrierte About-
Vorlage mit vollständiger MIT-Lizenz und Button zum Einsetzen der Vorlage.
Lizenzdatei ergänzt.

0.1 Build 4: Einheitliches Target-Feld statt getrennter Script-/Target-Auswahl.
Scripts werden ausgeführt, Variablen und andere Objekte angezeigt. Höhenberechnung
berücksichtigt den tatsächlichen oberen Abstand und begrenzt die Unterkante auf
den sichtbaren Bereich mit 16 px Abstand. Der Schliessen-Button bleibt im Footer;
nur die Menüliste kann bei vielen Einträgen innerhalb der Fläche scrollen.

0.1 Build 3: Darstellung im Shadow DOM isoliert; nutzbare Höhe begrenzt und
16 px Abstand rundum. Maus- und Touch-Eingaben separat verarbeitet. Mausbewegung
und Loslassen werden auch ausserhalb der Gestenfläche verfolgt. Ereignisse werden
gegen Weiterverarbeitung durch die umgebende Kachel abgeschirmt. Zusätzliche
Integrationstests für die Eingabeverarbeitung.

0.1 Build 2: Target-Aktionen ergänzt, Tippanzahl sofort erkennen; Standardpause für
neue Instanzen auf 600 ms erhöht. Archiv ohne übergeordnetes Verzeichnis.

0.1 Build 1: Erste Testversion mit HTML, Gestenfolge, Scriptmenü und Schliessen-Ziel.
