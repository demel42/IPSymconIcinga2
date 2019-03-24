# IPSymconIcinga2

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.0-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/175371809/shield?branch=master)](https://github.styleci.io/repos/150288134)

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

Icinga (https://de.wikipedia.org/wiki/Icinga) ist ein System zum überwaxchen von Servern und beliebigen Diensten. Mit der Erweiterung Icinga-Director gibt es ein ein graphisches Frontend, das (fast) die komplette Konfiguration per Browser erlaubt.
Icinga basiert auf einem Fork von Nagios aus 2009, enthät aber inzwischen deutliche Erweiterungen.

Die Dokumentation ist umfangreich (https://icinga.com, https://icinga.com/docs/icinga1/latest/de) und erlaubt, nach einer gewissen Beschäftigung mit Icinga, eine recht einfache Etablierung eines Systems.

Durch die Verwendung eines Docker-Containers (ich habe diesen https://hub.docker.com/r/jordan/icinga2 eingesetzt), kommt man sehr schnell zu einem funktonierenden System.


Das Modul erlaubt eine Verbindung mit Icinga2 in beide Richtungen:

### ein Check-Kommando, um von Icinga aus die Funktionsfähighkeit von IP-Symcon zu überprüfen
es gibt einen Standard-Test, der so eingesetzt, aber mit einem eigenen Script ersetzt werden kann.

### ein Nofitikation-Kommando um Benachrichtigungen von Icinga über IP-Symcon durchzuführen
man muss ein entsprechendes Script angeben, damit Benachrichtigungen durchgeführt werden, ein Beispiel findet sich im Verzeichnis _docs_.

### eine Event-Kommando, um bei in Icinga erkannten Fehlerzuständen über IP-Symcon Aktionen durchzuführen
man muss ein entsprechendes Script angeben, damit Aktionen durchgeführt werden, ein Beispiel findet sich im Verzeichnis _docs_.

### ein einfaches Interface zu der API, mit dem man beliebige Informationen aus Icinga abrufen kann
hier[ber werden auch yzklisch ein paar Informationen aus Icinga abgeholt und in IP-Symcon dargestellt

Es ist zweifelsohne so, das man eigentlich alle Tests auch von IP⁻Symcon aus direkt machen kann, aber der Schwerpunkt von IP-Symcon liegt im Bereich der Hausstuerung, der von Icinga im Bereich des Monitoring; hierfür gibt es eine umfangreiche und bewährte Infrastruktur.

Es gibt standardmässig bei Icinga sehr viele Prüf-Kommandos, auch für spezielle Anwendungen und eine recht umfangreiche Bibliothek zusätzlicher Kommandos (https://exchange.icinga.com). Es gibt eine dezidierte Funktion um auf Remote-Hosts Prüfungen durchzuführen und gesichert die Informationen und Kommandos zu kommunizieren.

## 2. Voraussetzungen

 - IP-Symcon ab Version 5
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
Die _query_ muss gemäß der Icinga-API aufgebaut sein…

Das Ergebnis ist eine JSNO-Stuktur.

`variant Icinga2_Query4Host(int $InstanzID, string $hosts)`<br>
Abfrage von Daten einem/einer Liste von Hosts.

Das Ergebnis ist eine JSNO-Stuktur.

`variant Icinga2_Query4Service(int $InstanzID, string $services, string $hosts)`<br>
Abfrage von Daten einem/einer Liste von Serviceis, optiona æingeschränkt auf bestimmte Hosts.

Das Ergebnis ist eine JSNO-Stuktur.

## 5. Konfiguration

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :-----------------------------------------: |
| Instanz ist deaktiviert   | boolean  | false        | Instanz temporär deaktivieren |
|                           |          |              | |
| Host                      | string   |              | Icƣnga-Server |
| Port                      | integer  | 5665         | Icinga-API-Port |
| HTTPS verwenden           | boolean  | false        | Zugriff auf Icinga per HTTPS |
| API-Benutzer              | string   |              | Icinga-API-Benutzer |
| API-Passwort              | string   |              | Passwort des API-Benutzer |
| Benutzer                  | string   |              | optionale Benutzer zu Zugriff auf den WebHook |
| Passwort                  | string   |              | Passwort des WebHook-Benutzers |
| Check-Script              | string   |              | optionales Script für eigene Checks |
| Notify-Script             | string   |              | Script für die Abarbeitung von Icinga-Benachrichtigungen |
| Event-Script              | string   |              | Script für die Abarbeitung von Icinga-Ereignissen |
| Aktualisiere Status ...   | integer  | 60           | Aktualisierungsintervall, Angabe in Sekunden |

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :-------------------------------------------------------------: |
| API-Zugriff prüfen           | Zugriff auf die Icinga-API prüfen |
| Aktualisiere Status          | aktuellen I♣inga-Status holen |

## 6. Anhang

GUIDs

- Modul: `{0E497B51-C4F0-4F68-9D98-F7BC3AE07CA3}`
- Instanzen:
  - Icinga2: `{970F623C-2A4B-4DB3-8C65-786381567D50}`

## 7. Versions-Historie

- 1.0 @ 13.03.2019 08:22<br>
  Initiale Version
