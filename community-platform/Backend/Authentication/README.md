# Supabase Authentication

This module handles identity and authentication for the Community Platform using Supabase Auth.

## Core Capabilities
- **Email & Password Authentication**: Secure sign-up and sign-in with email confirmation.
- **OAuth Providers**: GitHub, Google, LinkedIn integrations.
- **Session & JWT Management**: Automatic token refresh and secure HTTP-only cookie or local storage handling.
- **Password Reset**: Automated password recovery emails and token verification.
- **Auth Hooks / Triggers**: Automatically provision a `profiles` table record whenever a new user registers in `auth.users`.
