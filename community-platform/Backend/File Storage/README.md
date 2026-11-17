# Supabase File Storage

Storage bucket architecture and asset management guidelines.

## Storage Buckets
1. **`avatars`**: Publicly readable, authenticated write for own user avatar.
2. **`event-banners`**: Publicly readable, admin-only upload for workshop header banners.
3. **`learning-materials`**: Authenticated member access for slides, PDFs, code samples.
4. **`mentorship-docs`**: Private access restricted to paired mentor and mentee.
