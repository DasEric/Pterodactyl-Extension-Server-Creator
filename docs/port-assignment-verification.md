# Portzuordnung: Umsetzung und Prüfstand

## Änderungen

- Automatische Werte werden vor der Nutzer-Variablenvalidierung eingesetzt und nach dem Zusammenführen der Defaults erhalten. Nutzerwerte überschreiben diese Werte nicht.
- Neue benutzerdefinierte Spiele verwenden `port_mode=automatic`. Freie Ports müssen auf demselben Node und derselben IP liegen, aber nicht aufeinanderfolgen. Variablenschlüssel werden für eine eingabereihenfolgeunabhängige Zuordnung sortiert.
- Bestehende Spiele, alte API-Aufrufe ohne Modus und Spielvorlagen behalten `consecutive`: ihre gespeicherte Variablenreihenfolge bleibt maßgeblich.
- Portbedarf ist mindestens Hauptport plus Anzahl der Portvariablen. Eine ausdrücklich höhere Mindestanzahl bleibt erhalten.
- Adminformular zeigt Egg-Variablen und tatsächlichen Bedarf; Backend prüft die Zugehörigkeit zum Egg auch nach Katalogimport.
- Spielwechsel und Portabgleich berücksichtigen den Modus. Ein fremdes Egg ohne passende Spielkonfiguration erhält keine Portvariablen des bisherigen Spiels.
- Gemeinsame Cache-Sperre für Erstellung/Portabgleich innerhalb dieser Extension ergänzt die bisherige Nutzersperre.
- Migration ergänzt ausschließlich die Modusspalte; sie verändert keine Server-Allocations.

## Reproduzierte Regression

Mit der ursprünglichen Panel-Adapterdatei aus Commit `5c38754c8bd7b5633de4ae3153f073c9573e6825` schlägt `test_required_port_omitted_by_user_is_populated` fehl:

`Illuminate\Validation\ValidationException: validation.required`

`Tests: 1, Assertions: 0, Errors: 1.` Exitstatus: 2.

Der gleiche Fall mit dem geänderten Adapter besteht. Der Test verwendet Pterodactyl `v2.0.0-beta.2`, dessen echten VariableValidatorService, Laravel-Validierung und eine SQLite-In-Memory-Datenbank. Die übrigen Panel-Adapter-Abhängigkeiten werden in diesem Test nicht benötigt.

## Lokale Prüfungen

Ergebnis: PHP `OK (19 tests, 175 assertions)`; Frontend `25 passed (25)` in fünf Dateien; TypeScript, Produktionsbuild und `git diff --check` erfolgreich. Die Migration wurde in SQLite vorwärts und rückwärts geprüft. Es handelt sich nicht um eine Live-Abnahme des vollständigen Erstellungsprozesses.

Aus dem Repository-Verzeichnis:

```powershell
.panel\php-runtime\php.exe -d extension_dir=.panel/php-runtime/ext -d extension=mbstring -d extension=pdo_sqlite .panel\phpunit.phar --colors=never
npm run typecheck
npm test
npm run build
git diff --check
```

PHP-Tests decken Portgruppen, Reihenfolgeunabhängigkeit, unbekannte Variablen, fehlende Allocations, Bereichsgrenzen, erforderliche automatische Werte, geschützte Variablen und normale Pflichtfelder ab. Die Migration wird vorwärts und rückwärts in SQLite geprüft. Frontendtests decken unter anderem das Eingeben der Portvariablen bei anfänglicher Portanzahl 1 und die Ablehnung unbekannter Variablen ab.

Die Panel-Integrationstests benötigen `.panel/vendor` und `pdo_sqlite`; ohne diese Voraussetzungen werden sie ausdrücklich übersprungen. Lokale Testwerkzeuge und Panel-Abhängigkeiten liegen im bereits Git-ignorierten `.panel`-Verzeichnis. Es wurden keine Umbau- oder Automatisierungsskripte angelegt.

## Noch offene Abnahme

- Kein Live-Panel/Wings-Server wurde erstellt, gestartet oder verändert. Die End-to-End-Abnahme mit dem tatsächlich installierten 7-Days-to-Die-Egg steht aus.
- Konkurrenztests mit mehreren Prozessen und mit Zuweisungen außerhalb der Extension stehen aus. Die Extension-Cache-Sperre ersetzt keine panelweite atomare Reservierung; ihr Backend muss zwischen Workern geteilt werden.
- Der bestehende Spielwechsel bleibt ein mehrstufiger Vorgang; eine vollständige transaktionale Rücknahme bei Fehlern nach einem Egg-Wechsel ist nicht implementiert.
- Automatische Einzelports und zusammenhängende Blöcke sind alternative Modi; gemischte explizite Bindungen pro Variable sind noch nicht implementiert.
- Paketinstallation, produktive Datenbankmigration und Rücknahme eines produktiven Updates wurden nicht ausgeführt. Ein Downgrade benötigt vorherige Prüfung neuer automatischer Konfigurationen; die Schema-Rücknahme allein stellt deren Semantik nicht wieder her.

Damit sind der konkrete Validierungsfehler und die vereinfachte normale Portkonfiguration implementiert und lokal getestet. Die umfassende Live- und Konkurrenzabnahme des ursprünglichen Plans ist noch offen.
