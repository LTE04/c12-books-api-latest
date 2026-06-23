<script setup>
import { ref, onMounted } from 'vue';
import api from '../api/client';

const me    = ref(null);
const error = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/auth/me');
    me.value = data;
  } catch (e) {
    error.value = e.message;
  }
});
</script>

<template>
  <div class="card" style="max-width: 480px; margin: 0 auto;">
    <h2 style="margin-top: 0;">My account</h2>
    <p v-if="error" class="alert error">{{ error }}</p>

    <div v-if="me">
      <p><strong>ID:</strong>    {{ me.id }}</p>
      <p><strong>Name:</strong>  {{ me.name }}</p>
      <p><strong>Email:</strong> {{ me.email }}</p>
      <p><strong>Role:</strong>
        <span class="tag" :class="me.role === 'admin' ? 'admin' : 'member'">{{ me.role }}</span>
      </p>
      <p><strong>Joined:</strong> {{ me.created_at }}</p>
    </div>
  </div>
</template>
