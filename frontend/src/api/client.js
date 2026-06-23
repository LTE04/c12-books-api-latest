/**
 * Axios client — Chapter 12 edition.
 *
 * What's new vs Chapter 11:
 *  - 429 response is passed through with the Retry-After header intact so
 *    Login.vue can show a countdown to the user.
 *  - 403 (IDOR / role check) is passed through; callers decide the message.
 *  - Only a 401 still triggers auto-logout (expired / missing token).
 *
 * XSS note: never write API values into the DOM with v-html.
 * Always use Vue's {{ }} interpolation — it HTML-escapes automatically.
 * The backend also applies JSON_HEX_TAG etc., so angle brackets in stored
 * data are additionally encoded at the JSON layer.
 */

import axios from 'axios';
import { useAuth } from '../stores/auth';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 10_000,
});

// Attach JWT to every outgoing request.
api.interceptors.request.use((cfg) => {
  const auth = useAuth();
  if (auth.token) {
    cfg.headers.Authorization = `Bearer ${auth.token}`;
  }
  return cfg;
});

// Handle auth errors globally; surface 429/403 to callers.
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      // Token expired or missing — clear session and let the router redirect.
      useAuth().logout();
    }
    // 429 (rate limit) and 403 (IDOR / role check) bubble up to the caller
    // so the relevant view can show a context-aware message.
    return Promise.reject(err);
  }
);

export default api;
