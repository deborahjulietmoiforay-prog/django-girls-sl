# Community Platform Architecture

The **Community Platform** is structured into two clean layers: the standalone **Frontend Repository** and the **Backend & Supabase Infrastructure**.

```
community-platform/
│
├── frontend/                     # Dedicated Frontend Repository (main branch)
│   ├── Public Website/
│   │   ├── Home/
│   │   ├── About/
│   │   ├── Events/
│   │   ├── Resources/
│   │   ├── Contact/
│   │   ├── Register/
│   │   └── Login/
│   │
│   ├── Member Dashboard/
│   │   ├── Dashboard/
│   │   ├── Profile/
│   │   ├── Events/
│   │   ├── My Registrations/
│   │   ├── Learning Resources/
│   │   ├── Community Forum/
│   │   ├── Mentorship/
│   │   ├── Notifications/
│   │   └── Settings/
│   │
│   ├── Admin Dashboard/
│   │   ├── Dashboard/
│   │   ├── Members/
│   │   ├── Events/
│   │   ├── Registrations/
│   │   ├── Resources/
│   │   ├── Community/
│   │   ├── Mentorship/
│   │   ├── Announcements/
│   │   ├── Reports & Analytics/
│   │   └── Settings/
│   │
│   ├── includes/                 # Shared header, footer & layout styles
│   ├── package.json
│   └── README.md
│
└── Backend/                      # Backend & Supabase Architecture
    ├── config.php                # Database connection, auth helpers & models
    ├── Authentication/           # Supabase Auth triggers & session configs
    ├── Role Management/          # RBAC roles & access policies (Member, Mentor, Admin)
    ├── Database (Supabase)/      # PostgreSQL relational DDL schema & tables
    ├── File Storage/             # Storage buckets (avatars, event-banners, etc.)
    └── Security & Validation/    # Row-Level Security (RLS) policies
```

All duplicate pages and unnecessary stubs have been eliminated.
