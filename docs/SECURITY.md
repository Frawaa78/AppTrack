# AppTrack Security Documentation v3.3.2

This document outlines the comprehensive security measures implemented in AppTrack v3.3.2 to protect enterprise application portfolio data, with enhanced security hardening following production optimization and file structure cleanup.

## 🔒 Security Overview

AppTrack implements enterprise-grade security across multiple layers:

### 1. Data Privacy & AI Security
- **Sensitive Data Exclusion**: AI analysis automatically excludes sensitive fields
  - Contract numbers (`contract_number`)
  - Contract responsible parties (`contract_responsible`)
- **Data Anonymization**: Configurable personal data anonymization for AI processing
- **Secure API Integration**: OpenAI API calls use encrypted connections
- **Environment Variables**: API keys stored securely outside codebase
- **Content Filtering**: AI prompts filtered to prevent information leakage
- **User Stories Privacy**: Story data protected with application-level access control

### 2. Access Control & Authentication
- **Role-Based Access Control (RBAC)**:
  - **Admin**: Full system access including user management and all filtering options
  - **Editor**: View and edit applications, access to User Stories management
  - **Viewer**: Read-only access to application data, limited User Stories access
- **Session Security**: Secure session management with automatic timeout
- **Domain Restrictions**: Configurable allowed domains list
- **Request Rate Limiting**: 20 requests per user per hour
- **User Stories Access Control**: Create/edit restricted by user role and application ownership

### 3. API Security Measures
- **Input Validation**: Multi-layer validation and sanitization
- **SQL Injection Protection**: PDO prepared statements with parameter binding
- **CSRF Protection**: Cross-site request forgery prevention
- **Token Usage Limits**: Daily AI token limits (50,000 per user)
- **Error Handling**: Production-safe error messages
- **User Stories API Security**: 7 dedicated endpoints with proper validation
- **File Upload Security**: Comprehensive validation for User Stories attachments

### 4. Enhanced User Stories Security (v3.3.0 NEW)
- **Application Binding**: User Stories securely linked to applications with foreign key constraints
- **File Upload Protection**: Comprehensive validation for story attachments
  - File type restrictions (documents, images only)
  - File size limits (configurable per installation)
  - Malware scanning integration points
  - Secure file storage outside web root
- **User Story Ownership**: Created by tracking with user attribution
- **Assignment Validation**: Only valid users can be assigned to stories
- **Audit Logging**: All User Story operations logged with user attribution
- **Cross-Application Security**: Stories isolated by application_id to prevent data leakage

### 5. File Attachment Security (v3.3.0 NEW)
- **Upload Validation**: 
  - MIME type verification
  - File extension whitelist
  - Content-based validation
  - Virus scanning hooks
- **Storage Security**:
  - Files stored outside web root
  - Unique filename generation
  - Access controlled through PHP scripts
  - Download logging and tracking
- **Database Security**:
  - File metadata in separate table (user_story_attachments)
  - Foreign key cascading for data integrity
  - User attribution for all uploads

### 6. Audit & Compliance
- **Complete Audit Trail**: All changes logged with user attribution
- **Timestamp Integrity**: Immutable timestamps for compliance
- **AI Usage Logging**: Full tracking of AI requests and costs
- **Data Snapshots**: Automatic preservation before critical operations
- **User Stories Tracking**: Complete audit trail for all story operations
- **Change Monitoring**: Real-time tracking of modifications
- **Kanban Audit Trail**: Comprehensive logging of all drag-and-drop operations and phase changes

### 5. Production Security
- **Clean Codebase**: All debug files removed (17 files cleaned)
- **Environment Configuration**: Secure config with environment variables
- **Error Logging**: Configurable levels (debug, info, warning, error)
- **File Permissions**: Proper access controls on sensitive files

## 🛡️ Security Configuration

### AI Security Settings
```php
define('AI_CONFIG', [
    // Data privacy controls
    'anonymize_personal_data' => true,
    'exclude_sensitive_fields' => [
        'contract_number',
        'contract_responsible'
    ],
    
    // Rate limiting
    'max_requests_per_user_per_hour' => 20,
    'max_tokens_per_user_per_day' => 50000,
    
    // Domain restrictions
    'allowed_domains' => [
        'localhost',
        '127.0.0.1',
        'your-domain.com'
    ],
    
    // Logging controls
    'log_ai_requests' => true,
    'log_level' => 'info'
]);
```

### Database Security
- **Encrypted Storage**: Sensitive data encrypted at rest
- **Foreign Key Constraints**: Data integrity protection
- **Transaction Safety**: ACID compliance for data consistency
- **Connection Security**: Encrypted database connections

### Web Application Security
- **HTTPS Enforcement**: SSL/TLS encryption for all connections
- **XSS Protection**: Input sanitization and output encoding
- **Content Security Policy**: Prevents code injection attacks
- **Secure Headers**: Security headers for browser protection

## 🔍 Security Monitoring

### Audit Logging
- **User Actions**: All CRUD operations logged with timestamps
- **AI Usage**: Complete tracking of analysis requests and responses
- **Authentication Events**: Login/logout activities tracked
- **Data Changes**: Before/after values for all modifications

### Performance Monitoring
- **Request Tracking**: API call frequency and patterns
- **Error Monitoring**: Real-time error detection and alerting
- **Resource Usage**: Database and AI API consumption tracking
- **Security Events**: Suspicious activity detection

## 📋 Security Checklist

### Development Security ✅
- [x] All debug and test files removed from production
- [x] Environment variables for sensitive configuration
- [x] Production-safe error handling
- [x] Secure coding practices throughout codebase
- [x] Input validation and output encoding

### Deployment Security
- [ ] HTTPS certificate installed and configured
- [ ] Database encryption enabled
- [ ] Regular security updates scheduled
- [ ] Backup encryption configured
- [ ] Access logging enabled

### Operational Security
- [ ] Regular security audits scheduled
- [ ] User access reviews conducted
- [ ] Incident response plan established
- [ ] Security training completed
- [ ] Vulnerability scanning implemented

## 🚨 Security Best Practices

1. **Regular Updates**: Keep all dependencies and frameworks updated
2. **Access Reviews**: Quarterly review of user permissions and roles
3. **Backup Security**: Encrypt and secure all backup files
4. **Network Security**: Use VPN and secure networks for access
5. **Monitoring**: Implement comprehensive logging and monitoring
6. **Training**: Regular security awareness training for users

## 📞 Security Contact

For security issues or questions:
- **Internal**: Contact system administrator
- **External**: Follow responsible disclosure practices
- **Documentation**: Refer to this security guide and system documentation

---

**Document Version**: 2.5.0  
**Last Updated**: July 18, 2025  
**Next Review**: October 18, 2025
