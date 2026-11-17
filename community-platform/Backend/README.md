# Community Platform - Backend & Database

This folder defines the backend architecture powered by **Supabase** (PostgreSQL, Auth, Storage, and Row-Level Security).

## Root Configuration
- `config.php`: Core database connection, user authentication helper functions, CSRF protection, and session management.

## Modules
- `Authentication/`: User identity, auth triggers, signup profile syncing, and OAuth.
- `Role Management/`: Role-Based Access Control (Admin, Mentor, Member, Visitor).
- `Database (Supabase)/`: Relational PostgreSQL schema definitions (tables, constraints, foreign keys).
- `File Storage/`: Storage buckets, asset policies (avatars, banners, documents).
- `Security & Validation/`: Row-Level Security (RLS) policies and data validation constraints.
