-- Enable RLS on all tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.event_registrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.resources ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_topics ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.forum_replies ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.mentorship_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

-- Profiles Policies
CREATE POLICY "Public profiles are viewable by everyone" 
  ON public.profiles FOR SELECT USING (true);

CREATE POLICY "Users can update their own profile" 
  ON public.profiles FOR UPDATE USING (auth.uid() = id);

-- Events Policies
CREATE POLICY "Published events are viewable by everyone" 
  ON public.events FOR SELECT USING (status = 'published' OR public.is_admin());

CREATE POLICY "Admins can insert and update events" 
  ON public.events FOR ALL USING (public.is_admin());

-- Registrations Policies
CREATE POLICY "Users can view their own registrations" 
  ON public.event_registrations FOR SELECT USING (auth.uid() = user_id OR public.is_admin());

CREATE POLICY "Users can register themselves for events" 
  ON public.event_registrations FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can cancel their own registrations" 
  ON public.event_registrations FOR DELETE USING (auth.uid() = user_id);

-- Forum Policies
CREATE POLICY "Forum topics viewable by authenticated members" 
  ON public.forum_topics FOR SELECT USING (auth.role() = 'authenticated');

CREATE POLICY "Authenticated members can create forum topics" 
  ON public.forum_topics FOR INSERT WITH CHECK (auth.uid() = author_id);

CREATE POLICY "Authors and Admins can update forum topics" 
  ON public.forum_topics FOR UPDATE USING (auth.uid() = author_id OR public.is_admin());
