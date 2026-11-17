-- ============================================================================
-- DJANGO GIRLS SIERRA LEONE - COMMUNITY PLATFORM
-- MASTER SUPABASE DATABASE MIGRATION
-- Project: https://ggrqaivaijokkebxeppd.supabase.co
-- ============================================================================

-- Extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ----------------------------------------------------------------------------
-- 1. TABLES DEFINITIONS
-- ----------------------------------------------------------------------------

-- 1. Profiles Table (Linked to auth.users)
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users ON DELETE CASCADE,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    avatar_url TEXT,
    bio TEXT,
    role TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('member', 'mentor', 'admin')),
    interests TEXT DEFAULT '',
    experience_level TEXT DEFAULT 'beginner' CHECK (experience_level IN ('beginner', 'intermediate', 'advanced')),
    is_mentor BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2. Events Table
CREATE TABLE IF NOT EXISTS public.events (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'Workshop' CHECK (type IN ('Event', 'Workshop', 'Training session')),
    topic TEXT NOT NULL DEFAULT 'General',
    event_date TIMESTAMPTZ NOT NULL,
    location TEXT NOT NULL,
    capacity INTEGER NOT NULL DEFAULT 30,
    status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft', 'published', 'completed', 'cancelled')),
    created_by UUID REFERENCES public.profiles(id),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3. Event Registrations Table
CREATE TABLE IF NOT EXISTS public.event_registrations (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    event_id UUID NOT NULL REFERENCES public.events(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    status TEXT NOT NULL DEFAULT 'registered' CHECK (status IN ('registered', 'attended', 'cancelled', 'waitlist')),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(event_id, user_id)
);

-- 4. Learning Resources Table
CREATE TABLE IF NOT EXISTS public.resources (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    category TEXT NOT NULL,
    level TEXT NOT NULL DEFAULT 'beginner' CHECK (level IN ('beginner', 'intermediate', 'advanced')),
    url TEXT NOT NULL,
    created_by UUID REFERENCES public.profiles(id),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 5. Resource Progress Table
CREATE TABLE IF NOT EXISTS public.resource_progress (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    resource_id UUID NOT NULL REFERENCES public.resources(id) ON DELETE CASCADE,
    completed_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(user_id, resource_id)
);

-- 6. Forum Topics Table
CREATE TABLE IF NOT EXISTS public.forum_topics (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    is_locked BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 7. Forum Replies Table
CREATE TABLE IF NOT EXISTS public.forum_replies (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    topic_id UUID NOT NULL REFERENCES public.forum_topics(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    body TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 8. Mentorship Sessions Table
CREATE TABLE IF NOT EXISTS public.mentorships (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    mentee_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    mentor_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    message TEXT DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'declined', 'completed')),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(mentee_id, mentor_id)
);

-- 9. Announcements Table
CREATE TABLE IF NOT EXISTS public.announcements (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by UUID REFERENCES public.profiles(id),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- ----------------------------------------------------------------------------
-- 2. AUTH SYNCHRONIZATION TRIGGER (auth.users -> public.profiles)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger AS $$
BEGIN
    INSERT INTO public.profiles (
        id,
        email,
        full_name,
        avatar_url,
        role,
        interests,
        experience_level,
        is_mentor,
        created_at,
        updated_at
    )
    VALUES (
        new.id,
        new.email,
        COALESCE(new.raw_user_meta_data->>'full_name', split_part(new.email, '@', 1)),
        COALESCE(new.raw_user_meta_data->>'avatar_url', 'member-deborah.svg'),
        COALESCE(new.raw_user_meta_data->>'role', 'member'),
        COALESCE(new.raw_user_meta_data->>'interests', 'Python,Django'),
        COALESCE(new.raw_user_meta_data->>'experience_level', 'beginner'),
        COALESCE((new.raw_user_meta_data->>'is_mentor')::boolean, FALSE),
        NOW(),
        NOW()
    )
    ON CONFLICT (id) DO UPDATE SET
        email = EXCLUDED.email,
        updated_at = NOW();

    RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- ----------------------------------------------------------------------------
-- 3. ROW-LEVEL SECURITY (RLS) POLICIES
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS boolean AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.profiles
        WHERE id = auth.uid() AND role = 'admin'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Enable RLS
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.event_registrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.resources ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.resource_progress ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_topics ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_replies ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.mentorships ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

-- Profiles Policies
CREATE POLICY "Public profiles are viewable by everyone" ON public.profiles FOR SELECT USING (true);
CREATE POLICY "Users can insert their own profile" ON public.profiles FOR INSERT WITH CHECK (auth.uid() = id);
CREATE POLICY "Users can update their own profile" ON public.profiles FOR UPDATE USING (auth.uid() = id OR public.is_admin());

-- Events Policies
CREATE POLICY "Published events are viewable by everyone" ON public.events FOR SELECT USING (status = 'published' OR public.is_admin());
CREATE POLICY "Admins can manage all events" ON public.events FOR ALL USING (public.is_admin());

-- Event Registrations Policies
CREATE POLICY "Users can view their own registrations or admins view all" ON public.event_registrations FOR SELECT USING (auth.uid() = user_id OR public.is_admin());
CREATE POLICY "Authenticated users can register for events" ON public.event_registrations FOR INSERT WITH CHECK (auth.uid() = user_id);
CREATE POLICY "Users can update or cancel their registrations" ON public.event_registrations FOR UPDATE USING (auth.uid() = user_id OR public.is_admin());

-- Resources Policies
CREATE POLICY "Resources are viewable by everyone" ON public.resources FOR SELECT USING (true);
CREATE POLICY "Admins can manage resources" ON public.resources FOR ALL USING (public.is_admin());

-- Resource Progress Policies
CREATE POLICY "Users can view and manage their own progress" ON public.resource_progress FOR ALL USING (auth.uid() = user_id);

-- Forum Topics Policies
CREATE POLICY "Forum topics are viewable by everyone" ON public.forum_topics FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create topics" ON public.forum_topics FOR INSERT WITH CHECK (auth.role() = 'authenticated');
CREATE POLICY "Authors or admins can update topics" ON public.forum_topics FOR UPDATE USING (auth.uid() = user_id OR public.is_admin());

-- Forum Replies Policies
CREATE POLICY "Forum replies are viewable by everyone" ON public.forum_replies FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create replies" ON public.forum_replies FOR INSERT WITH CHECK (auth.role() = 'authenticated');

-- Mentorships Policies
CREATE POLICY "Mentees and Mentors can view their own sessions" ON public.mentorships FOR SELECT USING (auth.uid() = mentee_id OR auth.uid() = mentor_id OR public.is_admin());
CREATE POLICY "Members can send mentorship requests" ON public.mentorships FOR INSERT WITH CHECK (auth.uid() = mentee_id);
CREATE POLICY "Mentors can respond to requests" ON public.mentorships FOR UPDATE USING (auth.uid() = mentor_id OR auth.uid() = mentee_id OR public.is_admin());

-- Announcements Policies
CREATE POLICY "Announcements are viewable by everyone" ON public.announcements FOR SELECT USING (is_active = true OR public.is_admin());
CREATE POLICY "Admins can manage announcements" ON public.announcements FOR ALL USING (public.is_admin());

-- ----------------------------------------------------------------------------
-- 4. SEED INITIAL WORKSHOPS & TUTORIALS
-- ----------------------------------------------------------------------------
INSERT INTO public.events (title, slug, description, type, topic, event_date, location, capacity, status)
VALUES
  ('Intro to Python Workshop', 'intro-to-python', 'Write your first Python programs hands-on. No prior coding experience needed; laptops and coaching provided.', 'Workshop', 'Python', NOW() + INTERVAL '6 days 10 hours', 'Freetown, Limkokwing Lab 2', 30, 'published'),
  ('Build Your First Django Site', 'build-your-first-django-site', 'Follow the official Django Girls tutorial step-by-step with a dedicated mentor beside you.', 'Workshop', 'Django', NOW() + INTERVAL '13 days 9 hours', 'Freetown, Limkokwing Lab 1', 25, 'published'),
  ('HTML & CSS UI/UX Training Session', 'html-css-uiux-training', 'Learn modern responsive layouts, CSS Grid, Flexbox, and visual design principles.', 'Training session', 'HTML & CSS', NOW() + INTERVAL '20 days 14 hours', 'Online (Zoom & Freetown Hub)', 40, 'published'),
  ('Women in Tech Sierra Leone Meetup', 'women-in-tech-sl-meetup', 'Network with fellow developers, showcase your projects, and hear from women leading tech in Salone.', 'Event', 'General', NOW() + INTERVAL '27 days 16 hours', 'Freetown, Civic Innovation Hall', 60, 'published')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO public.resources (title, description, category, level, url)
VALUES
  ('Django Girls Official Tutorial', 'The step-by-step handbook: build and deploy your first blog application with Python and Django.', 'Django', 'beginner', 'https://tutorial.djangogirls.org/'),
  ('Python Official Beginner Guide', 'A guided tour of Python syntax, data structures, and functions from the official documentation.', 'Python', 'beginner', 'https://docs.python.org/3/tutorial/'),
  ('MDN Learn Web Development (HTML & CSS)', 'Structured interactive lessons covering semantic HTML5, responsive CSS, and layouts.', 'HTML & CSS', 'beginner', 'https://developer.mozilla.org/en-US/docs/Learn_web_development'),
  ('The Modern JavaScript Tutorial', 'From core fundamentals to DOM events and asynchronous programming with clear examples.', 'JavaScript', 'intermediate', 'https://javascript.info/')
ON CONFLICT DO NOTHING;
