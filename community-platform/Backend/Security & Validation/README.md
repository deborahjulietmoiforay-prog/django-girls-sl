# Security & Validation

Defines Row-Level Security (RLS) rules and validation constraints for the platform.

## Security Principles
- **Default Deny**: All public schema tables have RLS enabled by default.
- **Granular Ownership**: Users can only modify their own profile data, registrations, and forum posts.
- **Admin Elevation**: Admins have elevated privileges to moderate forum posts, manage event listings, and alter member roles.
- **Input Sanitization**: Content fields undergo validation to prevent XSS and malicious payloads.
