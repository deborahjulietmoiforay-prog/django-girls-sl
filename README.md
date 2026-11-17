# Django Girls Sierra Leone - Community Platform

A modern community and organisation management platform for Django Girls Sierra Leone.

## Project Structure
The project is organized in the [`community-platform/`](community-platform/) directory:
- **[`community-platform/frontend/`](community-platform/frontend/)**: Standalone frontend repository containing:
  - **Public Website**: Home, About, Events, Resources, Contact, Register, Login
  - **Member Dashboard**: Dashboard, Profile, Events, My Registrations, Learning Resources, Community Forum, Mentorship, Notifications, Settings
  - **Admin Dashboard**: Dashboard, Members, Events, Registrations, Resources, Community, Mentorship, Announcements, Reports & Analytics, Settings
- **[`community-platform/Backend/`](community-platform/Backend/)**: Backend and Supabase integration:
  - Authentication, Role Management (RBAC), Database (Supabase PostgreSQL), File Storage, Security & Validation (RLS)

See [`community-platform/README.md`](community-platform/README.md) for full architecture and setup details.
