<script setup>
/**
 * BookList — Chapter 12.
 *
 * What's new vs Chapter 11:
 *
 * IDOR awareness
 *   The Ch12 API returns `created_by` (user id) on every book row.
 *   The Edit button is now shown only when the signed-in user owns the book
 *   OR is an admin.  Attempting a PUT on a book you don't own would return
 *   403 anyway — this just gives honest UI feedback upfront.
 *   A "yours" badge is shown on books the current user created.
 *
 * Field-level validation errors
 *   A 400 from POST/PUT now carries { errors: { field: "message" } }.
 *   Those are forwarded to <BookForm> as `fieldErrors` so each field can
 *   display its own inline error.
 *
 * 403 IDOR message
 *   If a PUT/DELETE is rejected with 403 (e.g. concurrent ownership change),
 *   a clear "You don't own that book" message is shown.
 *
 * XSS safety
 *   All book data is rendered with {{ }} interpolation — NEVER v-html.
 *   Vue auto-escapes; the backend also applies JSON_HEX_TAG etc. as a
 *   second layer of defence.
 */

import { ref, onMounted } from 'vue';
import api from '../api/client';
import { useAuth } from '../stores/auth';
import BookForm from '../components/BookForm.vue';

const auth = useAuth();

const books       = ref([]);
const q           = ref('');
const error       = ref('');
const ok          = ref('');
const editing     = ref(null);      // null | 'new' | bookObj
const fieldErrors = ref({});
const loading     = ref(false);

// Returns true when the current user may edit this book.
function canEdit(book) {
  if (!auth.isAuthenticated) return false;
  return auth.isAdmin || auth.user?.id === book.created_by;
}

async function load() {
  error.value = ''; ok.value = '';
  loading.value = true;
  try {
    const { data } = await api.get('/api/books', { params: { q: q.value || undefined } });
    books.value = data.data;
  } catch (e) {
    error.value = e.response?.data?.error || e.message;
  } finally {
    loading.value = false;
  }
}

async function save(book) {
  error.value = ''; ok.value = ''; fieldErrors.value = {};
  try {
    if (book.id) {
      await api.put(`/api/books/${book.id}`, book);
      ok.value = 'Book updated.';
    } else {
      await api.post('/api/books', book);
      ok.value = 'Book created.';
    }
    editing.value = null;
    await load();
  } catch (e) {
    const d = e.response?.data;
    if (e.response?.status === 400 && d?.errors) {
      // Forward field-level errors back into the form.
      fieldErrors.value = d.errors;
    } else if (e.response?.status === 403) {
      error.value = "You don't own that book — only the creator or an admin can edit it.";
      editing.value = null;
    } else if (e.response?.status === 401) {
      error.value = 'Please sign in first.';
    } else {
      error.value = d?.error || e.message;
    }
  }
}

async function remove(book) {
  if (!confirm(`Delete "${book.title}"?`)) return;
  error.value = ''; ok.value = '';
  try {
    await api.delete(`/api/books/${book.id}`);
    ok.value = `Deleted "${book.title}".`;
    await load();
  } catch (e) {
    if (e.response?.status === 403) {
      error.value = 'Only admins can delete books.';
    } else {
      error.value = e.response?.data?.error || e.message;
    }
  }
}

onMounted(load);
</script>

<template>
  <div class="card">
    <div class="row" style="align-items: end;">
      <div style="flex: 2;">
        <label>Search by title or author</label>
        <input v-model="q" placeholder="e.g. clean" @keyup.enter="load" />
      </div>
      <div>
        <button class="primary" :disabled="loading" @click="load">
          {{ loading ? 'Loading…' : 'Search' }}
        </button>
      </div>
      <div v-if="auth.isAuthenticated">
        <button class="primary" @click="editing = 'new'">+ New book</button>
      </div>
    </div>
    <p v-if="!auth.isAuthenticated" class="note" style="margin: 14px 0 0;">
      Browsing as guest. <strong>Login</strong> to create or edit books.
    </p>
  </div>

  <BookForm
    v-if="editing !== null && auth.isAuthenticated"
    :book="editing === 'new' ? null : editing"
    :field-errors="fieldErrors"
    @save="save"
    @cancel="editing = null; fieldErrors = {}"
  />

  <p v-if="error" class="alert error">{{ error }}</p>
  <p v-if="ok"    class="alert ok">{{ ok }}</p>

  <div v-if="books.length" class="card">
    <div class="book" v-for="b in books" :key="b.id">
      <div>
        <!-- {{ }} interpolation — Vue auto-escapes; never use v-html here -->
        <strong>{{ b.title }}</strong>
        <span class="tag">{{ b.year }}</span>
        <span v-if="auth.user?.id === b.created_by" class="tag mine">yours</span>
        <div class="meta">{{ b.author }} • {{ b.genre }}</div>
      </div>
      <div class="actions" v-if="auth.isAuthenticated">
        <!-- Edit only shown if this user owns the book or is admin (IDOR UX) -->
        <button v-if="canEdit(b)" @click="editing = { ...b }; fieldErrors = {}">Edit</button>
        <button class="danger" v-if="auth.isAdmin" @click="remove(b)">Delete</button>
      </div>
    </div>
  </div>
  <p v-else-if="!loading" class="card" style="text-align: center; color: var(--muted);">
    No books found.
  </p>
</template>
