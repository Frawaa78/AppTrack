# Pulse Implementation Plan - AppTrack
**Versjon:** 1.0  
**Dato:** 4. august 2025  
**Status:** Planleggingsfase  

## Overordnet konsept

Pulse er et intelligent mikrointeraksjon-basert system designet for å kontinuerlig vedlikeholde og forbedre nøyaktigheten av applikasjonsdata i AppTrack gjennom AI-genererte spørsmål i stedet for å basere seg utelukkende på manuelle skjemaendringer eller store, sjeldne gjennomgangssessjoner.

### Kjerneprinsipp
- **Mikrointeraksjoner:** Korte, enkle spørsmål som kan besvares raskt
- **AI-drevet:** Intelligente spørsmål basert på detekterte inkonsistenser og endringer
- **Mobiloptimalisert:** Rask interaksjon på alle enheter
- **Gamifisert:** Poengsystem og motivasjon for brukerengasjement
- **Rollebasert:** Spørsmål sendes til riktig person basert på kompetanse og ansvar

## Brukeropplevelse (UX Flow)

### 1. Notifikasjon
- Brukere mottar e-post med link til Pulse-økt på definert frekvens
- Kritiske spørsmål kan sendes umiddelbart

### 2. Pulse-økt
- Mobiloptimalisert webside (ikke app)
- Maksimalt antall spørsmål per økt (konfigurerbart, default fra system)
- Enkle svaralternativer: **Ja**, **Nei**, **Vet ikke**
- Fremdriftsindikatorer og poengsystem

### 3. Svaralternativer
- **Ja/Nei:** Direkte dataoppdatering med audit-logging
- **Vet ikke:** Spørsmål videresendes i hierarki eller lagres til senere
- **Utdypende svar:** Valgfri funksjonalitet for mer komplekse spørsmål

## Teknisk arkitektur

### Analyse-motor (Level 2 AI-tilnærming)
```
Trigger-basert analyse:
├── DataMap endringer (løsningsarkitekt påvirkning)
├── Statusendringer (konsistenssjekk)
├── Nye relasjoner (avhengighetsvalidering)
├── Work Notes aktivitet (oppfølgingsbehov)
└── User Stories endringer (krav-validering)
```

### AI-kostnadskontroll
- **Regelbaserte sjekker:** Gratis validering av åpenbare inkonsistenser
- **Målrettet AI-analyse:** Kun ved spesifikke triggers
- **Batch-behandling:** Samle spørsmål for effektiv token-bruk
- **Caching:** Unngå duplikat-analyser

## Database-endringer

### Nye tabeller

#### 1. pulse_questions
```sql
CREATE TABLE pulse_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    assigned_user_id INT,
    question_type ENUM('inconsistency', 'validation', 'update_required', 'critical'),
    priority ENUM('low', 'medium', 'high', 'critical'),
    question_text TEXT,
    context_info TEXT,
    suggested_action TEXT,
    data_field_affected VARCHAR(100),
    old_value TEXT,
    suggested_value TEXT,
    ai_confidence_score DECIMAL(3,2),
    trigger_type VARCHAR(50),
    trigger_data JSON,
    status ENUM('pending', 'answered', 'forwarded', 'expired'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    answered_at TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (assigned_user_id) REFERENCES users(id)
);
```

#### 2. pulse_responses
```sql
CREATE TABLE pulse_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT,
    user_id INT,
    response ENUM('yes', 'no', 'not_sure'),
    additional_feedback TEXT,
    confidence_level ENUM('low', 'medium', 'high'),
    response_time_seconds INT,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES pulse_questions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### 3. pulse_user_preferences
```sql
CREATE TABLE pulse_user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    notification_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    max_questions_per_session INT DEFAULT 5,
    preferred_time TIME DEFAULT '09:00:00',
    timezone VARCHAR(50) DEFAULT 'Europe/Oslo',
    email_enabled BOOLEAN DEFAULT TRUE,
    include_low_priority BOOLEAN DEFAULT TRUE,
    gamification_enabled BOOLEAN DEFAULT TRUE,
    last_notified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### 4. pulse_triggers
```sql
CREATE TABLE pulse_triggers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trigger_name VARCHAR(100),
    trigger_type ENUM('datamap_change', 'status_change', 'relation_change', 'work_note', 'user_story', 'scheduled'),
    enabled BOOLEAN DEFAULT TRUE,
    ai_analysis_required BOOLEAN DEFAULT TRUE,
    priority_boost INT DEFAULT 0,
    conditions JSON,
    prompt_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 5. pulse_user_scores
```sql
CREATE TABLE pulse_user_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_points INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    accuracy_score DECIMAL(4,2) DEFAULT 0.00,
    streak_days INT DEFAULT 0,
    last_activity_date DATE,
    level_name VARCHAR(50) DEFAULT 'Beginner',
    badges JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_score (user_id)
);
```

#### 6. application_roles
```sql
CREATE TABLE application_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    user_id INT,
    role_type ENUM('solution_architect', 'business_analyst', 'application_manager', 'product_owner', 'project_manager', 'assigned_to', 'portfolio_owner', 'deputy'),
    is_primary BOOLEAN DEFAULT FALSE,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_app_user_role (application_id, user_id, role_type)
);
```

#### 7. role_hierarchies
```sql
CREATE TABLE role_hierarchies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_role ENUM('solution_architect', 'business_analyst', 'application_manager', 'product_owner', 'project_manager', 'assigned_to', 'portfolio_owner', 'deputy'),
    to_role ENUM('solution_architect', 'business_analyst', 'application_manager', 'product_owner', 'project_manager', 'assigned_to', 'portfolio_owner', 'deputy'),
    question_type VARCHAR(50),
    priority_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 8. portfolio_roles
```sql
CREATE TABLE portfolio_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT,
    user_id INT,
    role_type ENUM('portfolio_owner', 'deputy', 'asm', 'am'),
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_portfolio_user_role (portfolio_id, user_id, role_type)
);
```

#### 9. pulse_analysis_log
```sql
CREATE TABLE pulse_analysis_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    trigger_type VARCHAR(50),
    analysis_type VARCHAR(50),
    ai_model_used VARCHAR(100),
    input_tokens INT,
    output_tokens INT,
    processing_time_ms INT,
    questions_generated INT,
    analysis_result JSON,
    cost_estimate DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id)
);
```

#### 10. pulse_notifications
```sql
CREATE TABLE pulse_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    notification_type ENUM('scheduled', 'critical', 'reminder'),
    questions_included JSON,
    email_sent_at TIMESTAMP,
    email_opened_at TIMESTAMP,
    pulse_session_started_at TIMESTAMP,
    pulse_session_completed_at TIMESTAMP,
    status ENUM('pending', 'sent', 'opened', 'completed', 'expired'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Endringer i eksisterende tabeller

#### applications
```sql
ALTER TABLE applications 
ADD COLUMN solution_architect_id INT,
ADD FOREIGN KEY (solution_architect_id) REFERENCES users(id);
```

#### portfolios
```sql
ALTER TABLE portfolios 
ADD COLUMN portfolio_owner_id INT,
ADD COLUMN deputy_owner_id INT,
ADD FOREIGN KEY (portfolio_owner_id) REFERENCES users(id),
ADD FOREIGN KEY (deputy_owner_id) REFERENCES users(id);
```

#### users
```sql
ALTER TABLE users 
ADD COLUMN pulse_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN notification_preferences JSON;
```

## Nye filer og komponenter

### Backend (PHP)

#### 1. Pulse Core Service
- `/src/services/PulseService.php` - Hovedlogikk for Pulse-systemet
- `/src/services/PulseAnalyzer.php` - AI-analyse og spørsmålsgenerering
- `/src/services/PulseNotifier.php` - E-post og notifikasjons-håndtering
- `/src/services/PulseScoring.php` - Poengsystem og gamification

#### 2. Controllers
- `/src/controllers/PulseController.php` - API endpoints for Pulse
- `/src/controllers/PulseAdminController.php` - Admin-funksjoner

#### 3. Models
- `/src/models/PulseQuestion.php`
- `/src/models/PulseResponse.php`
- `/src/models/PulseUserPreferences.php`
- `/src/models/ApplicationRole.php`

### Frontend

#### 1. Pulse Web App (basert på eksisterende prototype)
- `/public/pulse/` - Komplett Pulse-applikasjon
- `/public/pulse/index.php` - Hovedside med session-håndtering
- `/public/pulse/api/` - API endpoints for Pulse-appen

#### 2. Admin Interface
- `/public/pulse_admin.php` - Konfigurasjon og overvåkning
- `/assets/js/pages/pulse-admin.js`
- `/assets/css/pages/pulse-admin.css`

#### 3. Integration i hovedsystem
- Pulse-knapper/lenker i eksisterende sider
- Notifikasjoner i hovedgrensesnittet

### API Endpoints

#### 1. Pulse Session API
```
GET  /public/pulse/api/session.php?user_id={id}
POST /public/pulse/api/answer.php
GET  /public/pulse/api/skip.php?question_id={id}
POST /public/pulse/api/forward.php
```

#### 2. Admin API
```
GET  /public/api/pulse/questions.php
POST /public/api/pulse/triggers.php
GET  /public/api/pulse/analytics.php
POST /public/api/pulse/user_preferences.php
```

#### 3. Background Processing
```
POST /public/api/pulse/analyze.php
GET  /public/api/pulse/generate_notifications.php
POST /public/api/pulse/send_notifications.php
```

### Scheduled Jobs/Cron

#### 1. Analyse-job
```bash
# Daglig analyse av endringer
0 2 * * * php /path/to/AppTrack/scripts/pulse_daily_analysis.php

# Ukentlig grundig analyse
0 3 * * 0 php /path/to/AppTrack/scripts/pulse_weekly_analysis.php
```

#### 2. Notifikasjons-job
```bash
# Send planlagte notifikasjoner
0 8 * * * php /path/to/AppTrack/scripts/pulse_send_notifications.php

# Rens opp gamle spørsmål
0 4 * * * php /path/to/AppTrack/scripts/pulse_cleanup.php
```

## Integrasjonspunkter

### 1. Trigger-oppsett
- **DataMap endringer:** Hook inn i DrawFlow save-funksjonen
- **Work Notes:** Trigger ved nye notater eller status-endringer
- **User Stories:** Analyse ved nye stories eller endringer
- **Applikasjons-endringer:** Overvåk kritiske felt-endringer

### 2. Eksisterende AI-service
- Utvid `AIService.php` med Pulse-spesifikk funksjonalitet
- Gjenbruk eksisterende OpenAI-integrasjon og caching
- Legg til Pulse-prompts i `ai_configurations` tabellen

### 3. Notification System
- Integrer med eksisterende e-post-infrastruktur
- Bruk eksisterende bruker-preferanser hvor mulig

## Implementeringsfaser

### Fase 1: Database og Backend Core (2-3 uker)
1. Opprett alle nye tabeller
2. Implementer PulseService og PulseAnalyzer
3. Basis API endpoints
4. Enkel trigger-system

### Fase 2: Frontend og UX (2-3 uker)
1. Oppgradere eksisterende Pulse-prototype
2. Integrasjon med backend API
3. Poengsystem og gamification
4. Responsive design og optimalisering

### Fase 3: Integration og Testing (2-3 uker)
1. Koble sammen med eksisterende AppTrack-funksjoner
2. E-post templates og notifikasjoner
3. Admin-grensesnitt for konfigurasjon
4. Comprehensive testing

### Fase 4: Deployment og Monitoring (1-2 uker)
1. Scheduled jobs oppsett
2. Monitoring og logging
3. Performance optimalisering
4. Brukertrening og dokumentasjon

## Risikovurdering

### Tekniske risikoer
- **AI-kostnader:** Kan bli høye hvis ikke kontrollert - implementer streng caching og batch-behandling
- **Performance:** Mange spørsmål kan påvirke database - implementer indeksering og arkivering
- **E-post deliverability:** Pulse-e-poster kan ende i spam - bruk god e-post-praksis

### Bruker-risikoer
- **Bruker-utmattelse:** For mange spørsmål kan skape motstand - implementer intelligente limits
- **Dårlig datakvalitet:** Feil svar kan forringe data - implementer rollback og validering
- **Lav deltakelse:** Brukere kan ignorere Pulse - fokus på gamification og verdi

## Suksessmåling

### KPI-er
1. **Deltakelsesrate:** % av sendte Pulse-økter som fullføres
2. **Datakvalitet:** Reduksjon i inkonsistenser og manglende data
3. **Responstid:** Gjennomsnittlig tid fra spørsmål til svar
4. **Brukerengasjement:** Poengscore og streak-vedlikehold
5. **Cost per insight:** AI-kostnader vs. datakvalitetsforbedring

### Målsettinger år 1
- 80% deltakelsesrate på kritiske spørsmål
- 50% reduksjon i manuelle dataoppryddings-oppgaver
- Månedlige AI-kostnader under [å defineres] NOK
- 90% brukertilfredshet med Pulse-opplevelsen

---

**Neste steg:** Gjennomgang og godkjenning av implementeringsplan før utvikling starter.
