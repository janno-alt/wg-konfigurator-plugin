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

### Release-Prozess (manuell)

1. **Version bump:** `wg-konfigurator.php` → `Version:` Zeile + `WG_KONFIGURATOR_VERSION`-Konstante
2. **Build:** Schritte 1+2 oben
3. **Commit + Tag:**
   ```bash
   git add . && git commit -m "Release v0.2.0"
   git tag v0.2.0
   git push && git push --tags
   ```
4. **GitHub → Releases → Draft a new release** für `v0.2.0` → ZIP aus Schritt 2 als Asset hochladen → Publish.

### Release-Prozess (automatisch via GitHub Actions)

Lege `.github/workflows/release.yml` an (siehe unten). Bei jedem `git tag v*.*.*`
baut die Action das Release-ZIP und published das Release automatisch.

```yaml
name: Release Plugin ZIP

on:
  push:
    tags: ['v*.*.*']

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - run: composer install --no-dev --optimize-autoloader

      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: assets/quiz-app/package.json

      - run: npm install
        working-directory: assets/quiz-app

      - run: npm run build
        working-directory: assets/quiz-app

      - name: Build plugin ZIP
        run: |
          STAGE="$RUNNER_TEMP/wg-konfigurator"
          mkdir -p "$STAGE"
          rsync -av --exclude=.git --exclude=.github --exclude=node_modules \
                --exclude=assets/quiz-app/node_modules \
                --exclude=assets/quiz-app/src \
                --exclude=assets/quiz-app/vite.config.js \
                --exclude='assets/quiz-app/package*.json' \
                --exclude=assets/quiz-app/index.html \
                --exclude=composer.json --exclude=composer.lock \
                --exclude=INSTALL.md --exclude=docs \
                ./ "$STAGE/"
          cd "$RUNNER_TEMP"
          zip -r "$GITHUB_WORKSPACE/wg-konfigurator-${GITHUB_REF_NAME}.zip" wg-konfigurator

      - uses: softprops/action-gh-release@v2
        with:
          files: wg-konfigurator-*.zip
          generate_release_notes: true
```

### Privates Repo?

Falls das GitHub-Repo privat ist:
1. **Personal Access Token** (fine-grained, mit `contents:read` für dieses Repo) auf
   GitHub erstellen.
2. In WP-Admin → **WG Konfigurator → GitHub Token** eintragen.
3. Updates funktionieren dann wie bei einem öffentlichen Repo.

## 5) Fallback ohne Auto-Update

Falls Update-Check mal ausfällt, kann das ZIP wie bei der Erstinstallation
einfach neu hochgeladen werden – WP überschreibt das alte Plugin.
