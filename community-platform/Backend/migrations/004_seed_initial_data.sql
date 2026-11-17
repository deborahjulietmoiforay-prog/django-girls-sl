-- ============================================================================
-- MIGRATION 004: SEED INITIAL COMMUNITY DATA
-- Description: Seeds initial workshops, learning guides, and forum topics.
-- ============================================================================

-- Seed Workshops
INSERT INTO public.events (title, slug, description, type, topic, event_date, location, capacity, status)
VALUES
  ('Intro to Python Workshop', 'intro-to-python', 'Write your first Python programs hands-on. No prior coding experience needed; laptops and coaching provided.', 'Workshop', 'Python', NOW() + INTERVAL '6 days 10 hours', 'Freetown, Limkokwing Lab 2', 30, 'published'),
  ('Build Your First Django Site', 'build-your-first-django-site', 'Follow the official Django Girls tutorial step-by-step with a dedicated mentor beside you.', 'Workshop', 'Django', NOW() + INTERVAL '13 days 9 hours', 'Freetown, Limkokwing Lab 1', 25, 'published'),
  ('HTML & CSS UI/UX Training Session', 'html-css-uiux-training', 'Learn modern responsive layouts, CSS Grid, Flexbox, and visual design principles.', 'Training session', 'HTML & CSS', NOW() + INTERVAL '20 days 14 hours', 'Online (Zoom & Freetown Hub)', 40, 'published'),
  ('Women in Tech Sierra Leone Meetup', 'women-in-tech-sl-meetup', 'Network with fellow developers, showcase your projects, and hear from women leading tech in Salone.', 'Event', 'General', NOW() + INTERVAL '27 days 16 hours', 'Freetown, Civic Innovation Hall', 60, 'published')
ON CONFLICT (slug) DO NOTHING;

-- Seed Learning Resources
INSERT INTO public.resources (title, description, category, level, url)
VALUES
  ('Django Girls Official Tutorial', 'The step-by-step handbook: build and deploy your first blog application with Python and Django.', 'Django', 'beginner', 'https://tutorial.djangogirls.org/'),
  ('Python Official Beginner Guide', 'A guided tour of Python syntax, data structures, and functions from the official documentation.', 'Python', 'beginner', 'https://docs.python.org/3/tutorial/'),
  ('MDN Learn Web Development (HTML & CSS)', 'Structured interactive lessons covering semantic HTML5, responsive CSS, and layouts.', 'HTML & CSS', 'beginner', 'https://developer.mozilla.org/en-US/docs/Learn_web_development'),
  ('The Modern JavaScript Tutorial', 'From core fundamentals to DOM events and asynchronous programming with clear examples.', 'JavaScript', 'intermediate', 'https://javascript.info/')
ON CONFLICT DO NOTHING;
