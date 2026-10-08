// LCC Payroll — Supabase browser client (optional, for future REST use).
// The PHP app uses direct DB connections; this file is for any
// client-side Supabase queries. Replace with your framework as needed.
const SUPABASE_URL = 'https://htlbmhkseweclwlixzlz.supabase.co';
const SUPABASE_PUBLISHABLE_KEY = 'sb_publishable_zQGus9OPXs2WB9SJ5tahTA_NIVRurF3';
if (window.supabase && window.supabase.createClient) {
  window.lccSupabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_PUBLISHABLE_KEY);
}
