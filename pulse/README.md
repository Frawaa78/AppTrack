# Pulse - AppTrack UI Prototype

Pulse er en prototype for et nytt brukergrensesnitt for AppTrack som fokuserer på "micro-interaction gamification". Prototypen lar brukere validere eller sende videre informasjon gjennom enkle spørsmål med engasjerende visuelle tilbakemeldinger.

## Funksjonalitet

### Spørsmålskort
- Viser ett spørsmål om gangen i et elegant kort
- Informasjonstekst som forklarer situasjonen
- Et oppfølgingsspørsmål som krever handling
- Tre store handlingsknapper: No, Not sure, Yes

### Animasjoner og Interaksjoner

**Yes-svar:**
- Utløser konfetti-animasjon over hele skjermen
- Kortet "eksploderer" visuelt før det fjernes
- Indikerer suksess og positive tilbakemeldinger

**No-svar:**
- Kortet animeres som om det "knuses i biter"
- Bitene faller ned og ut av skjermen
- Gir tydelig tilbakemelding på avslag

**Not sure-svar:**
- Viser modal: "Do you want to send this question to someone else?"
- Hvis Yes: Kortet animeres opp og ut av skjermen (delegering)
- Hvis No: Lukk modal og returner til kortet

## Teknisk Implementering

### Teknologier
- **React** - For komponentbasert UI
- **Framer Motion** - For avanserte animasjoner
- **canvas-confetti** - For konfetti-effekten
- **Vite** - For rask utvikling og building

### Mappestruktur
```
/pulse/
├── components/
│   └── QuestionCard.jsx    # Hovedkomponent for spørsmålskort
├── data/
│   └── questions.js        # Dummy-data med spørsmål
├── App.jsx                 # Hovedapplikasjon
├── main.jsx               # React entry point
├── styles.css             # All styling
├── index.html             # HTML template
├── package.json           # Dependencies
├── vite.config.js         # Vite configuration
└── README.md              # Denne filen
```

## Installasjon og Kjøring

1. Naviger til pulse-mappen:
```bash
cd pulse
```

2. Installer dependencies:
```bash
npm install
```

3. Start utviklingsserveren:
```bash
npm run dev
```

4. Åpne nettleseren på http://localhost:3001

## Dummy Data

Prototypen bruker tre eksempel-spørsmål:

1. **Lenel S2** - Applikasjon i Build Phase med gammel Go-live dato
2. **CRM Portal** - Aktiv app uten aktivitet
3. **Financial Management System** - Kritiske sårbarheter

## Design og UX

### Visuelle Elementer
- **Gradient bakgrunn** - Moderne, profesjonell look
- **Glassmorfisme** - Subtile blur-effekter og transparens
- **Fargekoding** - Grønn (Yes), Oransje (No), Grå (Not sure)
- **Responsive design** - Fungerer på desktop og mobil

### Interaksjonsdesign
- **Hover-effekter** - Knapper reagerer på museover
- **Micro-animasjoner** - Smooth overganger og feedback
- **Progress indicator** - Viser fremgang gjennom spørsmål
- **Completion screen** - Oppsummering når alle spørsmål er besvart

## Fremtidige Utvidelser

Prototypen er designet for enkelt å kunne utvides med:
- Integrasjon mot AppTrack-database
- Brukerautentisering
- Historikk og rapportering
- Flere spørsmålstyper
- Team-delegering
- Notifikasjoner

## Mål

Pulse demonstrerer hvordan komplekse datavaliderings-workflows kan gjøres engasjerende og intuitive gjennom:
- **Gamification** - Belønning for riktige svar
- **Visuell feedback** - Tydelige animasjoner for hver handling
- **Enkel interaksjon** - Maksimalt tre valg per spørsmål
- **Progressfølelse** - Tydelig fremgang og fullføring
