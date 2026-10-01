const { createClient } = require('@supabase/supabase-js');
const env = require('./env');

module.exports = createClient(env.supabaseUrl, env.supabaseServiceKey, {
  auth: { persistSession: false, autoRefreshToken: false },
});
