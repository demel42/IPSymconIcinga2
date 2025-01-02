# IPSymconIcinga2

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Icinga (https://de.wikipedia.org/wiki/Icinga) ist ein System zur überwachung von Servern und beliebigen Diensten. Mit der Erweiterung Icinga-Director gibt es ein ein graphisches Frontend, das (fast) die komplette Konfiguration per Browser erlaubt.
Icinga basiert auf einem Fork von Nagios aus 2009, enthät aber inzwischen deutliche Erweiterungen.

Die Dokumentation ist umfangreich (https://icinga.com, https://icinga.com/docs/icinga1/latest/de) und erlaubt, nach einer gewissen Beschäftigung mit Icinga, eine recht einfache Etablierung eines Systems.

Durch die Verwendung eines Docker-Containers (ich habe diesen https://hub.docker.com/r/jordan/icinga2 eingesetzt), kommt man sehr schnell zu einem funktonierenden System.


Das Modul erlaubt eine Verbindung mit Icinga2 in beide Richtungen:

### Check-Command
єs steht ein Check-Command zur Verfügung, um von Icinga aus die Funktionsfähighkeit von IP-Symcon dezidiert zu überprüfen.
Es gibt einen Standard-Test, der so eingesetzt, aber auvh durch ein eigenes Script ersetzt werden kann.

### Notification-Command
Es steht ein Notification-Command zur Verfügung um Benachrichtigungen von Icinga über IP-Symcon durchführen zu können.
Es muss ein entsprechendes Script angeben werden, mit Hilfe dessen die  Benachrichtigungen durchgeführt werden, ein Beispiel findet sich im Verzeichnis _docs_.

### Event-Command
Es steht ein Event-Command zur Verfügung, um bei in Icinga erkannten Fehlerzuständen über IP-Symcon Aktionen durchführen zu können.
Eѕ muss ein entsprechendes Script angeben werden, damit Aktionen durchgeführt werden, ein Beispiel findet sich im Verzeichnis _docs_.

### API-Interface
Ein einfaches Interface zu der Icinga-API ermöglicht es, beliebige Informationen aus Icinga abrufen zu können.
Das Implementierung beshränkt sich auf die Abfrage von Objekt-Daten, die vielfältigen Möglichkeiten, Icinga durch die API zu konfigurieren und zu steuern sind nicht realisiert.
Über diese Funktion werden auch zyklisch ein paar Informationen aus Icinga abgeholt und in IP-Symcon dargestellt

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - Icinga2-Instanz mit aktivertem API-Zugrif

## 3. Installation

### IP-Symcon

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconIcinga2.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _Icinga2_ auswählen.

### Icinga2
es müssen die 3 Kommandos _check_ipsymcon.php_, _notify_ipsymcon.php_ und _event_ipsymcon.php_ eingerichtet werden. Dazu diese Script an einen geeigneten Ort kopieren (z.B. die entsprechende _CustomPluginDir_). Weiterhin müssen die Kommandos in Icinga defineirt werden, siehe hierzu die entsprechendes Angaben in den _.txt_-Dateien.

Für ein Notify- und Event-Scripte liegen dort ebenfalls Beispiele parat ebenso wie einfache Beispiel zu ABruf von Daten.

## 4. Funktionsreferenz

`variant Icinga2_QueryObject(int $InstanzID, string $obj, string $query)`<br>
Durchführung einer Querry des Bereiches _objects_.
Daber bezeichnet _obj_ den Unterbereich, z.B. _hosts_ oder _services_.
Die _query_ muss gemäß der Icinga-API aufgebaut sein.

Das Ergebnis ist eine JSNO-Stuktur.

`variant Icinga2_Query4Host(int $InstanzID, string $hosts)`<br>
Abfrage von Daten einem/einer Liste von Hosts.

Das Ergebnis ist eine JSNO-Stuktur.

`variant Icinga2_Query4Service(int $InstanzID, string $services, string $hosts)`<br>
Abfrage von Daten einem/einer Liste von Serviceis, optiona æingeschränkt auf bestimmte Hosts.

Das Ergebnis ist eine JSNO-Stuktur.

## 5. Konfiguration

### Variablen

| Eigenschaft             | Typ     | Standardwert | Beschreibung |
| :---------------------- | :------ | :----------- | :----------- |
| Instanz deaktivieren    | boolean | false        | Instanz temporär deaktivieren |
|                         |         |              | |
| Host                    | string  |              | Icinga-Server |
| Port                    | integer | 5665         | Icinga-API-Port |
| HTTPS verwenden         | boolean | false        | Zugriff auf Icinga per HTTPS |
| API-Benutzer            | string  |              | Icinga-API-Benutzer |
| API-Passwort            | string  |              | Passwort des API-Benutzer |
| Benutzer                | string  |              | optionale Benutzer zu Zugriff auf den WebHook |
| Passwort                | string  |              | Passwort des WebHook-Benutzers |
| Check-Script            | string  |              | optionales Script für eigene Checks |
| Notify-Script           | string  |              | Script für die Abarbeitung von Icinga-Benachrichtigungen |
| Event-Script            | string  |              | Script für die Abarbeitung von Icinga-Ereignissen |
| Aktualisiere Status ... | integer | 60           | Aktualisierungsintervall, Angabe in Sekunden |

#### Schaltflächen

| Bezeichnung         | Beschreibung |
| :------------------ | :----------- |
| API-Zugriff prüfen  | Zugriff auf die Icinga-API prüfen |
| Aktualisiere Status | aktuellen Icinga-Status holen |

## 6. Anhang

GUIDs

- Modul: `{0E497B51-C4F0-4F68-9D98-F7BC3AE07CA3}`
- Instanzen:
  - Icinga2: `{970F623C-2A4B-4DB3-8C65-786381567D50}`

## 7. Versions-Historie

- 1.15 @ 02.01.2025 14:28
  - interne Änderung
  - update submodule CommonStubs

- 1.14 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.13 @ 03.11.2023 11:06
  - Fix: Verhinderung von Division durch 0
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.12 @ 04.07.2023 14:44
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.11.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.11 @ 07.07.2022 12:10
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun private und ggfs nur noch via IPS_RequestAction() erreichbar
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert
  - update submodule CommonStubs

- 1.10.5 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.10.4 @ 10.05.2022 15:06
  - update submodule CommonStubs
  - SetLocation() -> GetConfiguratorLocation()
  - weitere Absicherung ungültiger ID's

- 1.10.3 @ 01.05.2022 12:39
  - Webhook besser prüfen

- 1.10.2 @ 29.04.2022 18:16
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)

- 1.10.1 @ 26.04.2022 15:01
  - Implememtierung einer Update-Logik
  - Übersetzung vervollständigt
  - diverse interne Änderungen
  - IPS-Version ist nun minimal 6.0

- 1.10 @ 16.04.2022 12:11
  - Anpassungen an IPS 6.2 (Prüfung auf ungültige ID's)
  - Möglichkeit der Anzeige der Instanz-Referenzen sowie referenzierte Statusvariablen sowie Timer
  - diverse interen Änderungen
  - potentieller Namenskonflikt behoben
  - Aktualisierung von submodule CommonStubs

- 1.9 @ 22.12.2021 14:48
  - weitere Absicherung von IPS_GetSnapshotChanges()
  - Anzeige von Modul/Bibliotheks-Informationen

- 1.8 @ 14.07.2021 18:01
  - IPS_GetSnapshotChanges() abgesichert
  - Schalter "Instanz ist deaktiviert" umbenannt in "Instanz deaktivieren"

- 1.7 @ 12.07.2021 14:13
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - Berecnung des TPS ersetzt durch MPS (messages/sec), UPS (variable-updates/sec), LPS (logmessages/sec)

- 1.6 @ 23.07.2020 12:11
  - Nacharbeit zu 'strict_types=1'
  - LICENSE.md hinzugefügt
  - lokale Funktionen aus common.php in locale.php verlagert
  - Traits des Moduls haben nun Postfix "Lib"
  - GetConfigurationForm() überarbeitet
  - define's durch statische Klassen-Variablen ersetzt

- 1.5 @ 06.01.2020 11:17
  - Nutzung von RegisterReference() für im Modul genutze Objekte (Scripte, Kategorien etc)
  - SetTimerInterval() erst nach KR_READY

- 1.4 @ 01.01.2020 18:52
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert
  - Schreibfehler korrigiert

- 1.3 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Icinga-Plugin: Überprüfung von Objekten hinzugefügt
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.2 @ 09.08.2019 14:32
  - Schreibfehler korrigiert

- 1.1 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 1.0 @ 13.03.2019 08:22
  - Initiale Version
