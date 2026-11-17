-- Role definitions and validation helpers
CREATE TYPE user_role AS ENUM ('member', 'mentor', 'admin');

-- Function to check if the executing user is an admin
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS boolean AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role = 'admin'
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to check if the executing user is a mentor
CREATE OR REPLACE FUNCTION public.is_mentor()
RETURNS boolean AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role IN ('mentor', 'admin')
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
