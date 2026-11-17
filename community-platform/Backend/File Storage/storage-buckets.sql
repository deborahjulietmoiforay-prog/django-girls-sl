-- Supabase Storage Bucket Initialization
INSERT INTO storage.buckets (id, name, public)
VALUES 
  ('avatars', 'avatars', true),
  ('event-banners', 'event-banners', true),
  ('learning-materials', 'learning-materials', false),
  ('mentorship-docs', 'mentorship-docs', false)
ON CONFLICT (id) DO NOTHING;

-- Storage Policy: Anyone can view avatars
CREATE POLICY "Public Avatars"
ON storage.objects FOR SELECT
USING (bucket_id = 'avatars');

-- Storage Policy: Users can upload their own avatar
CREATE POLICY "User Avatar Upload"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'avatars' AND auth.uid()::text = (storage.foldername(name))[1]);

-- Storage Policy: Admins can upload event banners
CREATE POLICY "Admin Event Banners"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'event-banners' AND public.is_admin());
