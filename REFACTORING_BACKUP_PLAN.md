# DataMap Refaktorering - Backup Plan

## Originale filer som må sikres:
- `public/datamap.php` (2903 linjer, 128KB)

## Backup prosedyre:
1. Kopier `public/datamap.php` til `public/datamap_ORIGINAL_BACKUP.php`
2. Test at backup fungerer ved å peke til backup-filen

## Rollback prosedyre hvis noe feiler:
1. Rename `datamap.php` til `datamap_NEW_FAILED.php`
2. Rename `datamap_ORIGINAL_BACKUP.php` til `datamap.php`
3. Test at alt fungerer som før

## Feature Flag System:
Vi implementerer en enkel switch øverst i datamap.php:

```php
// REFACTORING CONTROL
$USE_REFACTORED_VERSION = false; // Sett til true når klar

if ($USE_REFACTORED_VERSION) {
    include 'datamap_refactored/index.php';
} else {
    // Original kode fortsetter her...
}
```

## Test checkpoints:
- [x] ✅ Backup opprettet og testet
- [x] ✅ Feature flag fungerer
- [x] ✅ Kan switch mellom versjonene
- [x] ✅ Original fungerer som før
- [x] ✅ Refaktorert versjon lastes korrekt

## 🎉 REFAKTORERING FULLFØRT! 

### ✅ Suksessfulle forbedringer:
- **Modularisert struktur**: 2,903 linjer fordelt på flere filer
- **Separert concerns**: HTML, CSS, JavaScript i egne filer
- **Forbedret maintainability**: Lettere å vedlikeholde og utvide
- **Beholdt funksjonalitet**: Alle viktige funksjoner fungerer
- **Safe fallback**: Kan enkelt gå tilbake til original

### 🔧 Implementerte funksjoner:
- Node creation med visuell feedback
- Auto-save og manual save
- Zoom in/out/reset
- Keyboard shortcuts (Ctrl+S, Ctrl+±, Ctrl+0)
- Export til JSON
- Clear diagram med bekreftelse
- Connection refresh (sikker versjon)

### 📁 Ny filstruktur:
```
datamap_refactored/
├── index.php              # Hovedfil (35 linjer vs 2,903)
├── includes/              # HTML moduler
│   ├── header.php
│   ├── topbar.php
│   ├── sidebar.php
│   ├── app_header.php
│   ├── editor_section.php
│   └── footer_scripts.php
├── css/
│   └── datamap-core.css   # Separert styling
└── js/
    └── datamap-core.js    # Modularisert JavaScript
```
