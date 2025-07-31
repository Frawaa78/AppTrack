# Pulse Prototype - One.com Installasjon

## Filer som skal lastes opp til one.com

Du trenger kun disse 3 filene for å kjøre Pulse-prototypen på one.com:

### 1. index.html
Hovedfilen som inneholder HTML-strukturen

### 2. styles.css  
All styling for prototypen

### 3. app.js
JavaScript-koden som håndterer all funksjonalitet

## Installasjon på one.com

1. **Logg inn på one.com kontrollpanel**
2. **Gå til File Manager**
3. **Naviger til public_html-mappen** (eller din domene-mappe)
4. **Opprett en ny mappe kalt "pulse"** (valgfritt, kan også legges direkte i public_html)
5. **Last opp de 3 filene:**
   - index.html
   - styles.css  
   - app.js

## Mappestruktur på serveren

```
public_html/
├── pulse/                  (eller direkte i public_html)
│   ├── index.html         ← Last opp denne
│   ├── styles.css         ← Last opp denne  
│   └── app.js             ← Last opp denne
```

## Åpne prototypen

Etter opplasting kan du åpne prototypen på:
- **Hvis i pulse-mappe:** `https://dittdomene.com/pulse/`
- **Hvis direkte i public_html:** `https://dittdomene.com/`

## Tekniske krav

- ✅ Ingen Node.js eller build-prosess nødvendig
- ✅ Kjører direkte i nettleseren
- ✅ Bruker CDN for canvas-confetti (krever internett)
- ✅ Responsive design for mobil og desktop
- ✅ Fungerer på alle moderne nettlesere

## Funksjonalitet

### Spørsmål og svar
- 3 dummy-spørsmål om AppTrack-applikasjoner
- Tre svaralternativer: No (oransje), Not sure (grå), Yes (grønn)

### Animasjoner
- **Yes:** Konfetti-regn + eksplosjon
- **No:** Kort "knuses" og faller ned
- **Not sure:** Modal spør om delegering, kort flyr opp og bort

### Progresjon
- Fremdriftsindikator viser spørsmål X av 3
- Ferdigskjerm med statistikk
- "Start Over" knapp for å begynne på nytt

## Tilpasning

For å endre spørsmålene, rediger `questions`-arrayet i `app.js`:

```javascript
const questions = [
    {
        id: 1,
        infoText: "Din informasjonstekst her...",
        questionText: "Ditt spørsmål her?",
        correctAction: "yes"
    }
    // Legg til flere spørsmål...
];
```

## Support

Prototypen krever:
- Moderne nettleser (Chrome, Firefox, Safari, Edge)
- JavaScript aktivert
- Internettforbindelse (for konfetti-biblioteket)

Alle filer er selvstendige og krever ingen ekstern database eller server-side kode.
