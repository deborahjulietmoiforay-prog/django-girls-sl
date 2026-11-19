================================================================================
DJANGO GIRLS SIERRA LEONE - COMMUNITY & ORGANISATION MANAGEMENT PLATFORM
DCOMP207 Web Programming Final Project (Continuation of Assignment 1: AUTH)
Student: Deborah Juliet Moiforay | Semester 5
================================================================================

HOW TO RUN LOCALLY (XAMPP / WAMP / PHP 7.4+):
1. Copy the `final_project` folder into `C:\xampp\htdocs\final_project`
   (already copied and configured for you!).
2. Start Apache in the XAMPP Control Panel and visit:
   http://localhost/final_project/
   (Or run `php -S localhost:8000` inside the project folder).
3. The SQLite database (`data/djangogirls.sqlite`) is created and seeded
   automatically on first visit with workshops, tutorials, mentors, and forum
   discussions.

DEMO & ADMIN ACCOUNTS:
- Administrator:
    Email:    admin@djangogirls.sl
    Password: Admin@12345
- Community Mentor:
    Email:    aminata@djangogirls.sl
    Password: Mentor@12345

ASSESSMENT CRITERIA COVERAGE (100 MARKS):
1. User Registration (signup.php)      - Full registration with interests & level
2. Login Functionality (login.php)     - Secure authentication + quick demo fill
3. Member Profiles (profile.php,       - Profile editor, photo upload, preset
   member.php, members.php)              avatars, and public member directory
4. Logout Functionality (logout.php)   - Secure session destruction & cookie expiry
5. Dashboard Design (dashboard.php)    - Personalized dashboard, training progress,
                                         Assignment 1 account activity & next steps
6. Events & Workshops (events.php)     - Event registration, seat tracker & history
7. Learning Resources (resources.php)  - Category filters, search & progress tracker
8. Community Interaction               - Discussion forum + 1-on-1 mentorship hub
   (community.php, topic.php, mentors.php)
9. Administrative Panel (admin/*)      - Analytics charts, CSV export, user/event/
                                         resource/attendance management
10. Presentation & UI/UX (assets/*)    - Custom Django Girls SL branding & imagery
