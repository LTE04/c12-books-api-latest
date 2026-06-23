<script setup>
/**
 * Register — Chapter 12.
 *
 * The Ch12 backend enforces stricter validation (password ≥ 8 chars,
 * name ≤ 150 chars, email format). Field-level errors from the 400
 * response are shown inline.
 */
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuth } from '../stores/auth';

const auth   = useAuth();
const router = useRouter();

const name        = ref('');
const email       = ref('');
const password    = ref('');
const error       = ref('');
const fieldErrors = ref({});
const busy        = ref(false);

async function submit() {
  error.value       = '';
  fieldErrors.value = {};
  busy.value        = true;
  try {
    await auth.register(name.value, email.value, password.value);
    router.push('/');
  } catch (e) {
    const data = e.response?.data;
    if (data?.errors) {
      fieldErrors.value = data.errors;   // e.g. { password: "must be ≥ 8 chars", ... }
    } else {
      error.value = data?.error || e.message;
    }
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card" style="max-width: 420px; margin: 32px auto;">
    <h2 style="margin-top: 0;">Register</h2>

    <p v-if="error" class="alert error">{{ error }}</p>

    <label>Full name</label>
    <input v-model="name" :class="{ 'field-error': fieldErrors.name }" />
    <p v-if="fieldErrors.name" class="field-msg">{{ fieldErrors.name }}</p>

    <label>Email</label>
    <input v-model="email" type="email" autocomplete="email"
           :class="{ 'field-error': fieldErrors.email }" />
    <p v-if="fieldErrors.email" class="field-msg">{{ fieldErrors.email }}</p>

    <label>Password <span style="color: var(--muted); font-size: 12px;">(min 8 chars)</span></label>
    <input v-model="password" type="password" autocomplete="new-password"
           :class="{ 'field-error': fieldErrors.password }" />
    <p v-if="fieldErrors.password" class="field-msg">{{ fieldErrors.password }}</p>

    <p style="margin-top: 18px;">
      <button class="primary" :disabled="busy" @click="submit">
        {{ busy ? 'Creating…' : 'Create account' }}
      </button>
    </p>

    <p style="font-size: 13px; color: var(--muted);">
      Already have an account? <RouterLink to="/login">Sign in</RouterLink>
    </p>
  </div>
</template>
