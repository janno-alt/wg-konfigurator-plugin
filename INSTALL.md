# Installation & Update-Workflow

## 1) Plugin lokal bauen

```bash
# PHP-Dependencies
composer install --no-dev --optimize-autoloader

# React-Quiz bauen
cd assets/quiz-app
npm install
npm run build
cd ../..
```

Damit liegen unter `vendor/` (PHP) und `assets/quiz-app/dist/assets/` (JS/CSS)
alle benötigten Artefakte – **diese müssen mit ins Release-ZIP**.

## 2) Release-ZIP packen

```bash
# Im Plugin-Wurzelverzeichnis (mit vendor/ + dist/ schon gebaut)
zip -r wg-konfigurator-$(grep "Version:" wg-konfigurator.php | awk '{print $3}').zip . \
  -x "*.git*" \
  -x "node_modules/*" \
  -x "assets/quiz-app/node_modules/*" \
  -x "assets/quiz-app/src/*" \
  -x "assets/quiz-app/vite.config.js" \
  -x "assets/quiz-app/package*.json" \
  -x "assets/quiz-app/index.html" \
  -x "composer.json" \
  -x "composer.lock" \
  -x "INSTALL.md" \
  -x "docs/*"
```

(Der Build-Schritt in GitHub-Actions, siehe unten, macht das automatisch.)

## 3) Erstinstallation in WordPress

1. **Plugins → Installieren → Plugin hochladen**
2. ZIP wählen → Aktivieren
3. **Einstellungen → WG Konfigurator** → API-Keys + SMTP + Webhook befüllen
4. Auf einer Seite testen: `[wg_konfigurator]` einfügen
5. Test-Submit machen → PDF kommt per Mail, Webhook trifft beim Mock-Endpoint ein

## 4) Auto-Update via GitHub-Releases

Das Plugin checkt GitHub auf neue Releases (über [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)).
Sobald du ein neues Release published, erscheint in WP unter **Plugins** ein
Update-Hinweis und es kann **direkt im WP-Admin per Klick** aktualisiert werden.

### Release-Prozess (über Git gesteuert)

Ein Release entsteht ausschließlich dadurch, dass ein Versions-Tag gepusht wird. Die GitHub Action in `.github/workflows/release.yml` baut daraus die Zip und hängt sie an ein GitHub-Release.

1. Setze die neue Version an beiden Stellen in `wg-konfigurator.php`, also in der Zeile `Version:` im Plugin-Kopf und in der Konstante `WG_KONFIGURATOR_VERSION`.
2. Committe die Änderung und pushe sie auf `main`.
3. Setze den passenden Tag und pushe ihn, zum Beispiel `git tag v0.14.0 && git push origin v0.14.0`.

Die Action bricht ab, wenn die Version im Tag nicht exakt mit der Version im Plugin-Kopf und in der Konstante übereinstimmt. Das Asset heißt immer `wg-konfigurator.zip` und enthält den Wurzelordner `wg-konfigurator`, damit bestehende Installationen beim Update im selben Ordner bleiben. Tags mit Bindestrich wie `v0.14.0-beta.1` werden als Vorabversion veröffentlicht und von den Update-Prüfungen nicht als reguläres Update angeboten.

Der Plugin-Kopf enthält die Zeile `Update URI: https://wg-digitalmarketing.de/wg-suite/wg-konfigurator`. Dadurch fragt WordPress nie bei wordpress.org nach Updates für dieses Plugin, sondern überlässt die Update-Meldung der WG Suite und dem eingebauten Plugin Update Checker.

### Privates Repo?

Falls das GitHub-Repo privat ist:
1. **Personal Access Token** (fine-grained, mit `contents:read` für dieses Repo) auf
   GitHub erstellen.
2. In WP-Admin → **WG Konfigurator → GitHub Token** eintragen.
3. Updates funktionieren dann wie bei einem öffentlichen Repo.

## 5) Fallback ohne Auto-Update

Falls Update-Check mal ausfällt, kann das ZIP wie bei der Erstinstallation
einfach neu hochgeladen werden – WP überschreibt das alte Plugin.
