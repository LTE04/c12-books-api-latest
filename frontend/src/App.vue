<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuth } from './stores/auth';

const auth   = useAuth();
const router = useRouter();
const apiBase = import.meta.env.VITE_API_BASE_URL;

function logout() {
  auth.logout();
  router.push({ name: 'login' });
}
</script>

<template>
  <header>
    <h1>📚 Books API</h1>
    <RouterLink to="/">Books</RouterLink>
    <RouterLink v-if="auth.isAuthenticated" to="/me">My account</RouterLink>
    <RouterLink v-else to="/login">Login</RouterLink>
    <span class="badge">Chapter 12 • Security</span>

    <span v-if="auth.isAuthenticated" class="me">
      {{ auth.user?.name }}
      <span class="tag" :class="auth.isAdmin ? 'admin' : 'member'">{{ auth.user?.role }}</span>
    </span>
    <button v-if="auth.isAuthenticated" @click="logout">Logout</button>
  </header>

  <main>
    <RouterView />
    <p style="text-align: center; color: var(--muted); font-size: 11px; margin-top: 24px;">
      API: {{ apiBase }}
    </p>
  </main>
</template>
