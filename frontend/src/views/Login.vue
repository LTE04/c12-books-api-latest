<script setup>
/**
 * Login — Chapter 12.
 *
 * New vs Chapter 11:
 *  - Handles HTTP 429 (rate limit) from POST /auth/login.
 *  - Reads the Retry-After response header and shows a live countdown.
 *  - Submit button is disabled until the cooldown expires.
 */
import { ref, onUnmounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useAuth } from '../stores/auth';

const auth   = useAuth();
const router = useRouter();
const route  = useRoute();

const email    = ref('member@books.test');
const password = ref('password');
const error    = ref('');
const busy     = ref(false);

// Rate-limit countdown state
const cooldown    = ref(0);   // seconds remaining
let   coolTimer   = null;

function startCooldown(seconds) {
  cooldown.value = Math.max(1, seconds);
  clearInterval(coolTimer);
  coolTimer = setInterval(() => {
    cooldown.value--;
    if (cooldown.value <= 0) clearInterval(coolTimer);
  }, 1000);
}

onUnmounted(() => clearInterval(coolTimer));

async function submit() {
  if (cooldown.value > 0) return;
  error.value = '';
  busy.value  = true;
  try {
    await auth.login(email.value, password.value);
    router.push(route.query.redirect ?? '/');
  } catch (e) {
    if (e.response?.status === 429) {
      const retryAfter = parseInt(e.response.headers['retry-after'] ?? '60', 10);
      startCooldown(retryAfter);
      error.value = `Too many login attempts. You can try again in ${retryAfter} seconds.`;
    } else if (e.response?.status === 401) {
      error.value = 'Invalid email or password.';
    } else {
      error.value = e.response?.data?.error || e.message;
    }
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card" style="max-width: 420px; margin: 32px auto;">
    <h2 style="margin-top: 0;">Sign in</h2>

    <p v-if="error" class="alert error">{{ error }}</p>

    <!-- Rate-limit countdown banner -->
    <p v-if="cooldown > 0" class="note">
      🔒 Login locked. Try again in <strong>{{ cooldown }}s</strong>.
    </p>

    <label>Email</label>
    <input v-model="email" type="email" autocomplete="email" :disabled="cooldown > 0" />

    <label>Password</label>
    <input v-model="password" type="password" autocomplete="current-password" :disabled="cooldown > 0" />

    <p style="margin-top: 18px;">
      <button
        class="primary"
        :disabled="busy || cooldown > 0"
        @click="submit"
      >
        {{ busy ? 'Signing in…' : cooldown > 0 ? `Wait ${cooldown}s…` : 'Sign in' }}
      </button>
    </p>

    <p style="font-size: 13px; color: var(--muted);">
      No account? <RouterLink to="/register">Register</RouterLink>
    </p>

    <p class="note" style="margin-top: 24px;">
      <strong>Seeded demo users:</strong><br>
      admin@books.test / password — admin (can delete books)<br>
      member@books.test / password — member (can create + edit own books)
    </p>

    <p class="note" style="margin-top: 8px;">
      <strong>Try the rate limiter:</strong> enter a wrong password 6 times in a row.
      The 6th attempt returns HTTP 429 with a Retry-After header.
    </p>
  </div>
</template>
