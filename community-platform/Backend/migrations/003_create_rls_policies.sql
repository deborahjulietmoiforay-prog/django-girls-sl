-- ============================================================================
-- MIGRATION 003: ROW-LEVEL SECURITY (RLS) POLICIES
-- Description: Enforces table-level access rules for public, members, and admins.
-- ============================================================================

-- Helper functions to verify roles
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS boolean AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.profiles
        WHERE id = auth.uid() AND role = 'admin'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Enable Row Level Security on all tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.event_registrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.resources ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.resource_progress ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_topics ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_replies ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.mentorships ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

-- 1. Profiles Policies
CREATE POLICY "Public profiles are viewable by everyone"
    ON public.profiles FOR SELECT USING (true);

CREATE POLICY "Users can insert their own profile"
    ON public.profiles FOR INSERT WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can update their own profile"
    ON public.profiles FOR UPDATE USING (auth.uid() = id OR public.is_admin());

-- 2. Events Policies
CREATE POLICY "Published events are viewable by everyone"
    ON public.events FOR SELECT USING (status = 'published' OR public.is_admin());

CREATE POLICY "Admins can manage all events"
    ON public.events FOR ALL USING (public.is_admin());

-- 3. Event Registrations Policies
CREATE POLICY "Users can view their own registrations or admins view all"
    ON public.event_registrations FOR SELECT USING (auth.uid() = user_id OR public.is_admin());

CREATE POLICY "Authenticated users can register for events"
    ON public.event_registrations FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update or cancel their registrations"
    ON public.event_registrations FOR UPDATE USING (auth.uid() = user_id OR public.is_admin());

-- 4. Resources Policies
CREATE POLICY "Resources are viewable by everyone"
    ON public.resources FOR SELECT USING (true);

CREATE POLICY "Admins can manage resources"
    ON public.resources FOR ALL USING (public.is_admin());

-- 5. Resource Progress Policies
CREATE POLICY "Users can view and manage their own progress"
    ON public.resource_progress FOR ALL USING (auth.uid() = user_id);

-- 6. Forum Topics Policies
CREATE POLICY "Forum topics are viewable by everyone"
    ON public.forum_topics FOR SELECT USING (true);

CREATE POLICY "Authenticated users can create topics"
    ON public.forum_topics FOR INSERT WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "Authors or admins can update topics"
    ON public.forum_topics FOR UPDATE USING (auth.uid() = user_id OR public.is_admin());

-- 7. Forum Replies Policies
CREATE POLICY "Forum replies are viewable by everyone"
    ON public.forum_replies FOR SELECT USING (true);

CREATE POLICY "Authenticated users can create replies"
    ON public.forum_replies FOR INSERT WITH CHECK (auth.role() = 'authenticated');

-- 8. Mentorships Policies
CREATE POLICY "Mentees and Mentors can view their own sessions"
    ON public.mentorships FOR SELECT USING (auth.uid() = mentee_id OR auth.uid() = mentor_id OR public.is_admin());

CREATE POLICY "Members can send mentorship requests"
    ON public.mentorships FOR INSERT WITH CHECK (auth.uid() = mentee_id);

CREATE POLICY "Mentors can respond to requests"
    ON public.mentorships FOR UPDATE USING (auth.uid() = mentor_id OR auth.uid() = mentee_id OR public.is_admin());

-- 9. Announcements Policies
CREATE POLICY "Announcements are viewable by everyone"
    ON public.announcements FOR SELECT USING (is_active = true OR public.is_admin());

CREATE POLICY "Admins can manage announcements"
    ON public.announcements FOR ALL USING (public.is_admin());
