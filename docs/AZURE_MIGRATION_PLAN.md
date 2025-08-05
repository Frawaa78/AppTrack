# 📋 AppTrack Migreringeplan: One.com → Aker BP Azure

**Opprettet**: 5. august 2025  
**Versjon**: 1.0  
**Status**: Planleggingsfase  

## 🎯 Oversikt

**Mål**: Migrere AppTrack fra one.com til Aker BP Azure-miljø med dev/prod oppsett og AI-assistert fortsatt utvikling + Entra ID integrasjon.

**Tidsramme**: 8-12 uker  
**DevOps Team Meeting**: Prioriter infrastruktur, sikkerhet og CI/CD pipelines

---

## 📊 Systemanalyse - Nåværende Tilstand

### Core Arkitektur
- **Backend**: PHP 8+ med 93 produksjonsfiler
- **Database**: MySQL 8.0 med 25 tabeller (normalisert design)
- **Frontend**: Bootstrap 5.3 + Vanilla JS (5 kjerne-komponenter)
- **AI Integration**: OpenAI GPT-3.5-turbo
- **File Storage**: BLOB-basert i database + lokale assets

### Teknisk Stack
```
├── PHP Core (39 aktive filer)
├── Database (25 tabeller, foreign key constraints)
├── Assets (CSS/JS/Bilder strukturert i moduler)
├── AI Service (OpenAI integration)
├── Session Management (role-based access)
└── Admin Settings System (nye v3.3.3 funksjoner)
```

### Nåværende Konfiguration
```php
// One.com konfigurasjon
define('DB_HOST', 'localhost');
define('DB_NAME', 'cvp60zaqj_apprackdb');
define('DB_USER', 'cvp60zaqj_apprackdb');
define('DB_PASS', 'Loker1978_');

// AI Configuration
'openai_api_key' => 'key_GXEPIwntltbzskkK'
```

### Avhengigheter og Integrasjoner
- OpenAI API (AI-analyse funksjoner)
- DrawFlow.js (DataMap visual editor)
- Bootstrap 5.3 + FontAwesome Pro
- Choices.js for multi-select
- Chart.js for dashboard

---

## 🏗️ Fase 1: Infrastruktur og Grunnlag (Uke 1-2)

### 1.1 Azure Ressurs Opprettelse

**Dev Miljø:**
```yaml
Resource Group: rg-apptrack-dev-we
App Service Plan: asp-apptrack-dev (Premium P1V2)
App Service: app-apptrack-dev
Database: sql-apptrack-dev (Standard S2)
Key Vault: kv-apptrack-dev
Storage Account: stapptrackdev
Application Insights: ai-apptrack-dev
```

**Prod Miljø:**
```yaml
Resource Group: rg-apptrack-prod-we
App Service Plan: asp-apptrack-prod (Premium P2V2)
App Service: app-apptrack-prod
Database: sql-apptrack-prod (Standard S4)
Key Vault: kv-apptrack-prod
Storage Account: stapptrackprod
Application Insights: ai-apptrack-prod
```

### 1.2 Nettverk og Sikkerhet
- **VNet Integration**: Koble App Services til Aker BP VNet
- **Private Endpoints**: Database og Storage Account
- **NSG Rules**: Begrens tilgang til kun nødvendige porter
- **WAF**: Web Application Firewall for App Service

### 1.3 Database Migrasjon Strategi
```sql
-- Eksporter fra one.com MySQL
mysqldump -u cvp60zaqj_apprackdb -p cvp60zaqj_apprackdb > apptrack_export.sql

-- Tilpass for Azure SQL
-- Erstatt AUTO_INCREMENT med IDENTITY
-- Tilpass datatyper (TEXT → NVARCHAR(MAX))
-- Oppdater foreign key constraints

-- Hovedtabeller som må migreres:
-- 1. applications (kjernetabell - 24 felter)
-- 2. users (tilpass for Entra ID)
-- 3. work_notes (aktivitets logg)
-- 4. ai_analysis (cache for AI-analyse)
-- 5. user_stories (agile funksjonalitet)
-- 6. audit_log (full change tracking)
-- 7. portfolios, phases, statuses (admin config)
-- + 18 andre referanse- og lookup-tabeller
```

**Kritiske Dataelementer:**
- 25 normaliserte tabeller med foreign key constraints
- JSON-felter for DrawFlow diagram data
- BLOB-data for file attachments
- Audit trail med timestamps
- AI cache med SHA-256 hashes

---

## 🔐 Fase 2: Sikkerhet og Entra ID (Uke 3-4)

### 2.1 Entra ID App Registration
```yaml
Navn: AppTrack-Production
Redirect URIs: 
  - https://apptrack.akerbp.com/auth/callback
  - https://apptrack-dev.akerbp.com/auth/callback
API Permissions:
  - User.Read
  - Profile
  - OpenId
  - Email
App ID URI: api://apptrack.akerbp.com
```

### 2.2 Autentisering Implementering

**Ny fil: `src/auth/EntraIdAuth.php`**
```php
<?php
class EntraIdAuth {
    private $clientId;
    private $tenantId;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct() {
        $this->clientId = getenv('ENTRA_CLIENT_ID');
        $this->tenantId = getenv('ENTRA_TENANT_ID');
        $this->clientSecret = getenv('ENTRA_CLIENT_SECRET');
        $this->redirectUri = getenv('ENTRA_REDIRECT_URI');
    }
    
    public function initiateLogin() {
        $authUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize";
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid profile User.Read email',
            'state' => bin2hex(random_bytes(16))
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        header('Location: ' . $authUrl . '?' . http_build_query($params));
        exit;
    }
    
    public function handleCallback() {
        // Handle OAuth callback and token exchange
        // Validate state parameter
        // Exchange code for tokens
        // Extract user info from ID token
        return $this->syncUserData($userInfo);
    }
    
    public function syncUserData($userInfo) {
        // Sync Entra ID user data with local users table
        // Map Aker BP roles to AppTrack roles
        return $this->mapAkerBPRoles($userInfo);
    }
    
    public function mapAkerBPRoles($userInfo) {
        $roleMappings = [
            'AppTrack.Admin' => 'admin',
            'AppTrack.Editor' => 'editor', 
            'AppTrack.Viewer' => 'viewer'
        ];
        
        // Default role if no mapping found
        return $roleMappings[$userInfo['role']] ?? 'viewer';
    }
}
```

**Oppdater: `public/login.php`**
```php
// Legg til Entra ID login button
<div class="d-grid gap-2">
    <button type="submit" class="btn btn-primary">Login with Local Account</button>
    <a href="auth/entra-login.php" class="btn btn-outline-primary">
        <i class="bi bi-microsoft"></i> Login with Aker BP
    </a>
</div>

// Behold lokalt login som fallback for dev/testing
```

### 2.3 Bruker-migrasjon Strategi
```sql
-- Oppdater users tabell for Entra ID
ALTER TABLE users ADD COLUMN entra_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN entra_upn VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN last_sync DATETIME NULL;

-- Index for performance
CREATE INDEX idx_users_entra_id ON users(entra_id);
```

---

## 💾 Fase 3: Database og Konfiguration (Uke 4-5)

### 3.1 Azure SQL Database Setup

**Database Creation Script:**
```sql
-- Opprett database med riktig collation
CREATE DATABASE apptrack_prod
COLLATE SQL_Latin1_General_CP1_CI_AS;

-- Opprett login og bruker
CREATE LOGIN apptrack_admin WITH PASSWORD = '[Complex Password]';
USE apptrack_prod;
CREATE USER apptrack_admin FOR LOGIN apptrack_admin;
ALTER ROLE db_owner ADD MEMBER apptrack_admin;
```

**Schema Konvertering:**
```sql
-- Konverter MySQL AUTO_INCREMENT til SQL Server IDENTITY
-- applications tabell eksempel:
CREATE TABLE applications (
    id INT IDENTITY(1,1) PRIMARY KEY,
    short_description NVARCHAR(255) NOT NULL,
    application_service NVARCHAR(255) NULL,
    -- ... resten av feltene
    drawflow_diagram NVARCHAR(MAX) NULL, -- JSON data
    created_at DATETIME2 DEFAULT GETDATE(),
    updated_at DATETIME2 DEFAULT GETDATE()
);

-- Konverter alle 25 tabeller med foreign keys intakt
```

### 3.2 Connection String og Secrets

**Azure Key Vault Secrets:**
```yaml
DB-HOST: apptrack-sql-server.database.windows.net
DB-NAME: apptrack_prod
DB-USER: apptrack_admin
DB-PASS: [Complex Password - generert av Key Vault]
OPENAI-API-KEY: [Existing key fra one.com]
ENTRA-CLIENT-ID: [Fra App Registration]
ENTRA-CLIENT-SECRET: [Fra App Registration]
ENTRA-TENANT-ID: [Aker BP Tenant ID]
ENTRA-REDIRECT-URI: https://apptrack.akerbp.com/auth/callback
```

### 3.3 Konfigurasjon for Azure

**Oppdater: `src/config/config.php`**
```php
<?php
// Azure-spesifikke innstillinger
date_default_timezone_set('Europe/Oslo');

// Database configuration fra Key Vault
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'apptrack_dev');
define('DB_USER', getenv('DB_USER') ?: 'sa');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_DRIVER', 'sqlsrv'); // Endret fra mysql til sqlsrv

// Environment configuration
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true');
define('AZURE_STORAGE_CONNECTION', getenv('AZURE_STORAGE_CONNECTION'));

// AI Configuration med Azure integration
define('AI_CONFIG', [
    'openai_api_key' => getenv('OPENAI_API_KEY'),
    'azure_openai_endpoint' => getenv('AZURE_OPENAI_ENDPOINT'), // Hvis Aker BP har Azure OpenAI
    'default_model' => 'gpt-3.5-turbo',
    'default_temperature' => 0.7,
    'cache_duration_hours' => 24,
    'max_requests_per_user_per_hour' => 50, // Økt for prod
    'azure_table_storage' => getenv('AZURE_TABLE_STORAGE_CONNECTION')
]);

// Entra ID Configuration
define('ENTRA_CONFIG', [
    'client_id' => getenv('ENTRA_CLIENT_ID'),
    'client_secret' => getenv('ENTRA_CLIENT_SECRET'),
    'tenant_id' => getenv('ENTRA_TENANT_ID'),
    'redirect_uri' => getenv('ENTRA_REDIRECT_URI')
]);
```

**Ny fil: `src/db/AzureSqlConnection.php`**
```php
<?php
class AzureSqlConnection {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $connectionInfo = [
            'Database' => DB_NAME,
            'Uid' => DB_USER,
            'PWD' => DB_PASS,
            'CharacterSet' => 'UTF-8',
            'TrustServerCertificate' => true
        ];
        
        $this->connection = sqlsrv_connect(DB_HOST, $connectionInfo);
        if (!$this->connection) {
            throw new Exception('Azure SQL connection failed: ' . print_r(sqlsrv_errors(), true));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
```

---

## 🚀 Fase 4: CI/CD og DevOps (Uke 5-6)

### 4.1 Azure DevOps Pipeline

**azure-pipelines.yml:**
```yaml
trigger:
  branches:
    include:
      - main
      - develop

variables:
  - group: AppTrack-Variables

stages:
- stage: Build
  displayName: 'Build Application'
  jobs:
  - job: BuildApp
    displayName: 'Build AppTrack'
    pool:
      vmImage: 'ubuntu-latest'
    steps:
    - task: UsePhpVersion@0
      inputs:
        versionSpec: '8.1'
        
    - script: |
        composer install --no-dev --optimize-autoloader
        npm install
        npm run build:production
      displayName: 'Install dependencies and build'
      
    - task: ArchiveFiles@2
      inputs:
        rootFolderOrFile: '$(Build.SourcesDirectory)'
        includeRootFolder: false
        archiveFile: '$(Build.ArtifactStagingDirectory)/apptrack.zip'
        excludePaths: |
          node_modules
          .git
          .env
          
    - task: PublishBuildArtifacts@1
      inputs:
        PathtoPublish: '$(Build.ArtifactStagingDirectory)'
        ArtifactName: 'apptrack-package'

- stage: DeployDev
  displayName: 'Deploy to Development'
  condition: eq(variables['Build.SourceBranch'], 'refs/heads/develop')
  dependsOn: Build
  jobs:
  - deployment: DeployToDev
    displayName: 'Deploy to Dev Environment'
    environment: 'AppTrack-Dev'
    pool:
      vmImage: 'ubuntu-latest'
    strategy:
      runOnce:
        deploy:
          steps:
          - task: AzureKeyVault@2
            inputs:
              azureSubscription: 'Aker BP Subscription'
              KeyVaultName: 'kv-apptrack-dev'
              SecretsFilter: '*'
              
          - task: AzureWebApp@1
            inputs:
              azureSubscription: 'Aker BP Subscription'
              appType: 'webApp'
              appName: 'app-apptrack-dev'
              package: '$(Pipeline.Workspace)/apptrack-package/apptrack.zip'
              
          - task: SqlAzureDacpacDeployment@1
            inputs:
              azureSubscription: 'Aker BP Subscription'
              ServerName: 'apptrack-sql-server-dev.database.windows.net'
              DatabaseName: 'apptrack_dev'
              SqlUsername: '$(DB-USER)'
              SqlPassword: '$(DB-PASS)'
              deployType: 'SqlTask'
              SqlFile: '$(Pipeline.Workspace)/apptrack-package/database/migration.sql'

- stage: DeployProd
  displayName: 'Deploy to Production'
  condition: eq(variables['Build.SourceBranch'], 'refs/heads/main')
  dependsOn: Build
  jobs:
  - deployment: DeployToProd
    displayName: 'Deploy to Production'
    environment: 'AppTrack-Prod'
    pool:
      vmImage: 'ubuntu-latest'
    strategy:
      runOnce:
        deploy:
          steps:
          - task: AzureKeyVault@2
            inputs:
              azureSubscription: 'Aker BP Subscription'
              KeyVaultName: 'kv-apptrack-prod'
              SecretsFilter: '*'
              
          - task: AzureWebApp@1
            inputs:
              azureSubscription: 'Aker BP Subscription'
              appType: 'webApp'
              appName: 'app-apptrack-prod'
              package: '$(Pipeline.Workspace)/apptrack-package/apptrack.zip'
              appSettings: |
                -ENVIRONMENT production
                -DB_HOST $(DB-HOST)
                -DB_NAME $(DB-NAME)
                -DB_USER $(DB-USER)
                -DB_PASS $(DB-PASS)
                -OPENAI_API_KEY $(OPENAI-API-KEY)
                -ENTRA_CLIENT_ID $(ENTRA-CLIENT-ID)
                -ENTRA_CLIENT_SECRET $(ENTRA-CLIENT-SECRET)
                -ENTRA_TENANT_ID $(ENTRA-TENANT-ID)
```

### 4.2 Environment Configuration

**Dev Environment Innstillinger:**
- Automated deployment fra develop branch
- Test data og sandbox AI API key
- Debug logging aktivert
- Relaxed security policies for testing

**Prod Environment Innstillinger:**
- Manual approval required for deployment
- Production AI API key med full rate limits
- Minimal logging (kun error og warning)
- Strict security policies

### 4.3 Backup og Disaster Recovery
```yaml
# Azure Backup konfiguration
Database Backup:
  - Point-in-time restore aktivert
  - Automated nightly backups
  - Cross-region backup replikering

Application Backup:
  - App Service backup til Storage Account
  - Include app settings og certificates
  - Weekly full backup, daily incremental
```

---

## 🤖 Fase 5: AI og Utviklingsflyt (Uke 6-7)

### 5.1 AI-Assistert Utvikling Setup

**GitHub Copilot Integration:**
```yaml
# .github/workflows/ai-code-review.yml
name: AI Code Review
on:
  pull_request:
    types: [opened, synchronize]

jobs:
  ai-review:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    - name: AI Code Analysis
      uses: github/super-linter@v4
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
    - name: Generate Documentation
      run: |
        # AI-generated documentation updates
        # Code quality suggestions
        # Security vulnerability detection
```

### 5.2 AI Feature Optimization

**Oppdater: AI-konfigurasjon for Azure**
```php
// src/services/AzureAIService.php
class AzureAIService extends AIService {
    private $azureOpenAIEndpoint;
    private $azureTableStorage;
    
    public function __construct() {
        $this->azureOpenAIEndpoint = getenv('AZURE_OPENAI_ENDPOINT');
        $this->azureTableStorage = getenv('AZURE_TABLE_STORAGE_CONNECTION');
        parent::__construct();
    }
    
    protected function getCachedAnalysis($cacheKey) {
        // Bruk Azure Table Storage i stedet for database cache
        return $this->azureTableStorage->getEntity('AICache', $cacheKey);
    }
    
    protected function setCachedAnalysis($cacheKey, $analysis) {
        // Lagre til Azure Table Storage
        return $this->azureTableStorage->insertEntity('AICache', [
            'PartitionKey' => 'Analysis',
            'RowKey' => $cacheKey,
            'Content' => $analysis,
            'Timestamp' => new DateTime()
        ]);
    }
}
```

### 5.3 Monitoring og Analytics

**Application Insights Integration:**
```php
// src/monitoring/AppInsights.php
class AppInsights {
    private $telemetryClient;
    
    public function trackAIUsage($analysisType, $duration, $success) {
        $this->telemetryClient->trackMetric('AI.Analysis.Duration', $duration, [
            'Type' => $analysisType,
            'Success' => $success
        ]);
    }
    
    public function trackUserActivity($userId, $action) {
        $this->telemetryClient->trackEvent('User.Activity', [
            'UserId' => $userId,
            'Action' => $action,
            'Timestamp' => time()
        ]);
    }
}
```

**Custom Metrics:**
- AI analysis request count og success rate
- Database query performance
- User session duration
- Feature usage statistics

---

## 📁 Fase 6: File Storage og Assets (Uke 7-8)

### 6.1 Azure Storage Migration

**Ny service: `src/services/AzureStorageService.php`**
```php
<?php
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureStorageService {
    private $blobClient;
    private $connectionString;
    
    public function __construct() {
        $this->connectionString = getenv('AZURE_STORAGE_CONNECTION');
        $this->blobClient = BlobRestProxy::createBlobService($this->connectionString);
    }
    
    public function uploadFile($file, $container, $fileName = null) {
        $fileName = $fileName ?: basename($file['name']);
        
        $content = fopen($file['tmp_name'], 'r');
        $this->blobClient->createBlockBlob($container, $fileName, $content);
        
        return $this->getBlobUrl($container, $fileName);
    }
    
    public function downloadFile($fileName, $container) {
        $blob = $this->blobClient->getBlob($container, $fileName);
        return stream_get_contents($blob->getContentStream());
    }
    
    public function deleteFile($fileName, $container) {
        $this->blobClient->deleteBlob($container, $fileName);
    }
    
    public function getBlobUrl($container, $fileName) {
        return "https://{$this->getStorageAccountName()}.blob.core.windows.net/{$container}/{$fileName}";
    }
    
    private function getStorageAccountName() {
        // Ekstraher storage account navn fra connection string
        preg_match('/AccountName=([^;]+)/', $this->connectionString, $matches);
        return $matches[1];
    }
}
```

**Storage Containere:**
```yaml
assets:
  purpose: CSS, JS, bilder, fonts
  access: Public read
  cdn: Aktivert

uploads:
  purpose: User uploads og attachments
  access: Private med SAS tokens
  backup: Aktivert

backups:
  purpose: Database backups og system snapshots
  access: Private
  retention: 90 dager

logs:
  purpose: Application logs og audit trails
  access: Private
  retention: 365 dager
```

### 6.2 CDN og Performance

**Azure CDN Setup:**
```yaml
CDN Profile: cdn-apptrack-prod
CDN Endpoint: apptrack-assets
Origin: stapptrackprod.blob.core.windows.net

Caching Rules:
  - Static Assets (CSS/JS): 1 år
  - Images: 6 måneder
  - HTML: 1 time
  - API responses: No cache

Compression: Aktivert for alle text-based files
```

**Oppdater asset URLs:**
```php
// src/helpers/AssetHelper.php
class AssetHelper {
    public static function getAssetUrl($path) {
        $cdnUrl = getenv('CDN_BASE_URL');
        if ($cdnUrl && ENVIRONMENT === 'production') {
            return $cdnUrl . '/' . ltrim($path, '/');
        }
        return '/assets/' . ltrim($path, '/');
    }
}
```

---

## 🔧 Fase 7: Testing og Validering (Uke 8-9)

### 7.1 Migreringesting

**Database Integritet Test:**
```sql
-- Test alle 25 tabeller
SELECT TABLE_NAME, COLUMN_COUNT, ROW_COUNT 
FROM INFORMATION_SCHEMA.TABLES t
JOIN (SELECT TABLE_NAME, COUNT(*) as COLUMN_COUNT 
      FROM INFORMATION_SCHEMA.COLUMNS 
      GROUP BY TABLE_NAME) c ON t.TABLE_NAME = c.TABLE_NAME
LEFT JOIN (SELECT TABLE_NAME, COUNT(*) as ROW_COUNT 
           FROM sys.tables st
           JOIN sys.partitions p ON st.object_id = p.object_id 
           WHERE p.index_id = 1
           GROUP BY st.name) r ON t.TABLE_NAME = r.TABLE_NAME
WHERE t.TABLE_TYPE = 'BASE TABLE'
ORDER BY t.TABLE_NAME;
```

**Funksjonalitetstester:**
- [ ] Database connectivity og queries
- [ ] Entra ID autentisering flow
- [ ] AI-analyse med nye cache system
- [ ] File upload til Azure Storage
- [ ] Dashboard loading og kanban-board
- [ ] Admin settings CRUD operasjoner
- [ ] User stories modul med attachments
- [ ] DataMap visual editor
- [ ] Executive dashboard charts
- [ ] Activity tracker og audit logs

### 7.2 Performance Testing

**Load Testing Script:**
```yaml
# Azure Load Testing konfigurasjon
testPlan: apptrack-load-test.jmx
targetUrl: https://app-apptrack-dev.azurewebsites.net
testDuration: 10 minutes
virtualUsers: 50 concurrent

scenarios:
  - name: Login Flow
    weight: 20%
    requests: /login, /dashboard
    
  - name: Application Management
    weight: 40%
    requests: /app_form.php, /app_view.php, /api/*
    
  - name: AI Analysis
    weight: 30%
    requests: /ai_insights.php, /api/ai_analysis.php
    
  - name: Admin Functions
    weight: 10%
    requests: /settings_admin.php, /api/settings/*
```

**Performance Targets:**
- Page load time: < 2 sekunder
- API response time: < 500ms
- Database query time: < 100ms
- AI analysis time: < 30 sekunder

### 7.3 Security Testing

**OWASP Compliance Check:**
```yaml
Security Tests:
  - SQL Injection: Automated testing med SQLMap
  - XSS Protection: Manual og automated testing
  - CSRF Protection: Token validation testing
  - Authentication: Entra ID integration testing
  - Authorization: Role-based access testing
  - File Upload: Malicious file testing
  - Session Management: Session hijacking tests
```

**Penetration Testing Scope:**
- External vulnerability assessment
- Entra ID integration security
- API endpoint security
- Database access controls
- File storage permissions

---

## 📋 Fase 8: Go-Live og Monitoring (Uke 10-12)

### 8.1 Cutover Plan

**Pre-Cutover (1 uke før):**
- [ ] Final testing i staging environment
- [ ] User communication sendt ut
- [ ] Backup av one.com data
- [ ] DNS TTL redusert til 300 sekunder

**Cutover Day:**
```yaml
Time: Fredag 18:00 (etter arbeidstid)

Hour 0-1: Preparation
  - Final data sync fra one.com
  - DNS record preparation
  - Team på standby

Hour 1-2: Switch
  - DNS switching fra one.com til Azure
  - SSL certificate validation
  - Basic smoke testing

Hour 2-4: Validation
  - Full functionality testing
  - User acceptance testing
  - Performance monitoring

Hour 4-24: Monitoring
  - 24/7 monitoring aktivert
  - Support team på standby
  - Error tracking intensivert
```

**Rollback Plan:**
- DNS quick switch tilbake til one.com
- Database restore fra backup
- User communication om midlertidig tilbakestilling

### 8.2 Post-Migration Monitoring

**Day 1-7: Intensive Monitoring**
```yaml
Metrics å overvåke:
  - Application availability (99.9% target)
  - Response times (< 2s target)
  - Error rates (< 0.1% target)
  - User login success rate
  - AI API success rate
  - Database performance

Alerting:
  - Immediate: Application down
  - 5 min: High error rate
  - 15 min: Slow response times
  - 30 min: High resource usage
```

**Week 1-4: Optimization**
- Performance tuning basert på real-world data
- User feedback innsamling og implementering
- Cost optimization
- Security monitoring

### 8.3 User Support og Training

**Support Plan:**
- Helpdesk bemanning utvidet første uke
- FAQ dokument med vanlige spørsmål
- Video tutorials for nye funksjoner
- Direct contact info for kritiske issues

**Training Materials:**
- New URL og login process
- Entra ID authentication guide
- Feature improvements documentation
- Troubleshooting guide

---

## 💰 Kostnadsanalyse

### Månedlige Driftskostnader

**Dev Environment:**
```yaml
App Service Plan P1V2: $146
Azure SQL S2: $75
Storage Account (LRS): $10
Application Insights: $5
Key Vault: $1
CDN: $5
Total Dev: ~$242/måned
```

**Prod Environment:**
```yaml
App Service Plan P2V2: $292
Azure SQL S4: $300
Storage Account (GRS): $25
Application Insights: $15
Key Vault: $3
CDN: $15
Private Endpoints: $20
Total Prod: ~$670/måned
```

**Total månedlig: ~$912 (vs one.com ~$50)**

### Engangs-kostnader

**Migrering og Utvikling:**
```yaml
DevOps/Infrastructure setup: $15,000
Entra ID integration utvikling: $10,000
Database migration og testing: $8,000
Security assessment og testing: $5,000
Training og dokumentasjon: $3,000
Project management: $5,000

Total engangs: $46,000
```

**ROI Vurdering:**
- Økt sikkerhet og compliance
- Bedre performance og skalabilitet
- AI-assistert utvikling capabilities
- Enterprise-grade backup og disaster recovery
- Automatic scaling under load

---

## ⚠️ Risikoanalyse og Mitigering

### Kritiske Risikoer (Høy Impact)

**1. Entra ID Integration Kompleksitet**
- *Risiko*: Autentisering fungerer ikke som forventet
- *Sannsynlighet*: Medium
- *Mitigering*: Tidlig POC, tett samarbeid med Aker BP AD-team, fallback til lokal auth
- *Eier*: Development team + Aker BP AD team

**2. Database Migration Data Loss**
- *Risiko*: Tap av kritiske data under migrering
- *Sannsynlighet*: Lav
- *Mitigering*: Multiple backups, staged migration, extensive testing
- *Eier*: Database team

**3. Performance Degradering**
- *Risiko*: Systemet blir tregere enn one.com
- *Sannsynlighet*: Medium
- *Mitigering*: Load testing, performance monitoring, CDN implementation
- *Eier*: Infrastructure team

### Moderate Risikoer (Medium Impact)

**4. User Adoption Motstand**
- *Risiko*: Brukere har problemer med ny URL/login
- *Sannsynlighet*: Høy
- *Mitigering*: Extensive communication, training, support
- *Eier*: Project manager + Support team

**5. Cost Overrun**
- *Risiko*: Azure kostnader høyere enn forventet
- *Sannsynlighet*: Medium
- *Mitigering*: Cost monitoring, resource optimization, reserved instances
- *Eier*: Finance + DevOps team

**6. AI API Changes**
- *Risiko*: OpenAI API endringer påvirker funksjonalitet
- *Sannsynlighet*: Lav
- *Mitigering*: API versioning, fallback mechanisms, Azure OpenAI alternativ
- *Eier*: Development team

### Lave Risikoer (Lav Impact)

**7. Third-party Dependencies**
- *Risiko*: Bootstrap, FontAwesome etc. compatibility issues
- *Sannsynlighet*: Lav
- *Mitigering*: Vendor locking på spesifikke versjoner, local copies
- *Eier*: Frontend team

---

## 📊 Suksesskriterier og KPIer

### Tekniske KPIer
```yaml
Availability: 99.9% uptime
Performance: 
  - Page load: < 2 sekunder
  - API response: < 500ms
  - Database queries: < 100ms

Security:
  - Zero security incidents første 90 dager
  - 100% Entra ID authentication adoption
  - OWASP compliance score > 95%

Functionality:
  - All 25 database tabeller migrert successfully
  - 100% feature parity med one.com versjon
  - AI analysis funksjoner operational
```

### Business KPIer
```yaml
User Adoption:
  - 90% user login success første uke
  - < 5% support tickets related til migration
  - 95% user satisfaction score

Cost Efficiency:
  - Stay within $1000/month budget første år
  - ROI positive innen 12 måneder
  - 50% reduction i maintenance time

Development Velocity:
  - AI-assisted development active
  - 30% faster feature development cycle
  - Automated CI/CD pipeline functional
```

---

## 🎯 Kritiske Suksessfaktorer

### 1. DevOps Team Alignment
- **Betydning**: Kritisk for infrastruktur setup
- **Tiltak**: Ukentlige møter, felles roadmap, tydelige ansvarsområder
- **Timeline**: Uke 1-2

### 2. Security Approval Process
- **Betydning**: Må ha security clearance før go-live
- **Tiltak**: Tidlig security review, penetration testing, compliance check
- **Timeline**: Uke 6-8

### 3. User Communication
- **Betydning**: Smooth overgang avhenger av forberedte brukere
- **Tiltak**: Proaktiv kommunikasjon, training sessions, support readiness
- **Timeline**: Uke 8-10

### 4. Data Integrity Verification
- **Betydning**: Kan ikke tape data under migrering
- **Tiltak**: Multiple backup strategies, parallel running, extensive testing
- **Timeline**: Uke 4-9

### 5. AI Development Continuity
- **Betydning**: Må opprettholde AI-assistert utvikling kapabilitet
- **Tiltak**: Parallel development environment, API key management, fallback plans
- **Timeline**: Uke 6-7

---

## 📞 DevOps Meeting Agenda

### Pre-Meeting Forberedelse

**Dokumenter å dele:**
1. Denne migrerineplanen
2. Current system architecture diagram
3. Database schema dokumentasjon
4. Security requirements overview
5. Cost-benefit analysis

### Meeting Agenda (2 timer)

**Del 1: Infrastruktur Diskusjon (45 min)**
1. Azure resource sizing og recommendations
2. Network architecture og security groups
3. Database migration strategy
4. Backup og disaster recovery plans

**Del 2: CI/CD og DevOps (30 min)**
1. Preferred pipeline tools (Azure DevOps vs GitHub Actions)
2. Deployment strategies og approval processes
3. Environment management (dev/staging/prod)
4. Monitoring og alerting preferences

**Del 3: Sikkerhet og Compliance (30 min)**
1. Entra ID integration requirements
2. Security scanning og vulnerability management
3. Compliance requirements og audit trails
4. Secret management strategies

**Del 4: Timeline og Next Steps (15 min)**
1. Resource allocation og team assignments
2. Dependencies og blockers identification
3. Communication plan
4. First sprint planning

### Spørsmål til DevOps Team

**Infrastruktur:**
1. Finnes det eksisterende Azure OpenAI tjenester i Aker BP?
2. Hvilke App Service Plan sizes anbefaler dere for vårt use case?
3. Har dere standarder for database sizing og backup policies?
4. Hvordan håndterer dere cross-region disaster recovery?

**Security:**
1. Hvilke security scanning tools bruker dere?
2. Er det spesielle compliance krav for vårt system?
3. Hvordan håndterer dere Key Vault permissions?
4. Finnes det network restrictions vi må ta hensyn til?

**DevOps:**
1. Preferred CI/CD verktøy og pipeline patterns?
2. Hvordan håndterer dere environment-specific configuration?
3. Monitoring og alerting stack preferences?
4. Code review og approval processes?

**Timeline:**
1. Hvor lang tid tar typical Azure resource provisioning?
2. Security review process duration?
3. DNS change procedures og timing?
4. Support team availability during cutover?

---

## 📋 Action Items og Next Steps

### Umiddelbare Tasks (Next 2 uker)
- [ ] Schedule DevOps team meeting
- [ ] Begin Azure subscription access requests
- [ ] Start security requirements documentation
- [ ] Create detailed database migration scripts
- [ ] Set up development branch for Azure changes

### Phase 1 Deliverables (Uke 3-4)
- [ ] Azure resources provisioned og configured
- [ ] Network connectivity established
- [ ] Basic CI/CD pipeline operational
- [ ] Database migration tested in dev

### Phase 2 Deliverables (Uke 5-6)
- [ ] Entra ID integration implemented
- [ ] Security review completed
- [ ] Load testing conducted
- [ ] User training materials prepared

### Phase 3 Deliverables (Uke 7-8)
- [ ] Production deployment tested
- [ ] Rollback procedures verified
- [ ] Go-live plan finalized
- [ ] Support team trained

---

## 📚 Appendiks

### A. Current System Metrics
```yaml
Current one.com Performance:
  - Average page load: 3-4 sekunder
  - Database size: ~500MB
  - Monthly users: ~50-100
  - AI API calls: ~1000/month
  - Storage usage: ~2GB

Current Features:
  - 25 database tabeller
  - 39 active PHP files
  - AI-powered analysis
  - DataMap visual editor
  - Admin settings system
  - User stories management
  - Executive dashboard
```

### B. Technical Dependencies
```yaml
Backend Dependencies:
  - PHP 8.1+
  - MySQL → Azure SQL Server
  - OpenAI API
  - PDO database abstraction

Frontend Dependencies:
  - Bootstrap 5.3
  - FontAwesome Pro
  - Choices.js
  - Chart.js
  - DrawFlow.js

Infrastructure Dependencies:
  - HTTPS/SSL certificates
  - DNS management
  - Email delivery (notifications)
  - File storage (user uploads)
```

### C. Backup og Recovery Procedures
```yaml
Backup Strategy:
  - Database: Point-in-time restore + nightly full backups
  - Application: Weekly snapshots + daily incremental
  - User files: Geo-redundant storage + versioning
  - Configuration: Version controlled + Key Vault backup

Recovery Procedures:
  - RTO: 4 timer for full system restore
  - RPO: 1 time for data loss
  - Disaster recovery: Cross-region failover available
  - Testing: Monthly DR drills
```

---

**Dokument opprettet**: 5. august 2025  
**Neste review**: Etter DevOps team meeting  
**Eier**: AppTrack Development Team  
**Godkjent av**: [Pending DevOps meeting]
