# IPSymconIcinga2

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
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

 - IP-Symcon ab Version 5.3
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
| Instanz ist deaktiviert | boolean | false        | Instanz temporär deaktivieren |
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

- 1.6 @ 13.07.2020 14:56
  - LICENSE.md hinzugefügt

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
