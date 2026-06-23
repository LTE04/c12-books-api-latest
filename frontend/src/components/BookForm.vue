<script setup>
/**
 * BookForm — Chapter 12.
 *
 * What's new vs Chapter 11:
 *  - Accepts a `fieldErrors` prop (object keyed by field name).
 *  - Shows an inline error message below each field when the API returns
 *    a 400 with { errors: { title: "...", year: "..." } }.
 *  - Highlights the offending input with a red border (.field-error class).
 */
import { ref, watchEffect } from 'vue';

const props = defineProps({
  book:        { type: Object, default: null },
  fieldErrors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['save', 'cancel']);

const form = ref({ title: '', author: '', year: new Date().getFullYear(), genre: '' });

watchEffect(() => {
  form.value = props.book
    ? { ...props.book }
    : { title: '', author: '', year: new Date().getFullYear(), genre: '' };
});

function submit() {
  emit('save', {
    ...(props.book?.id ? { id: props.book.id } : {}),
    title:  form.value.title?.trim(),
    author: form.value.author?.trim(),
    year:   Number(form.value.year),
    genre:  form.value.genre?.trim() || undefined,
  });
}
</script>

<template>
  <div class="card">
    <h3 style="margin-top: 0;">{{ props.book?.id ? 'Edit book' : 'New book' }}</h3>

    <div class="row">
      <div>
        <label>Title</label>
        <input v-model="form.title" :class="{ 'field-error': fieldErrors.title }" />
        <p v-if="fieldErrors.title" class="field-msg">{{ fieldErrors.title }}</p>
      </div>
      <div>
        <label>Author</label>
        <input v-model="form.author" :class="{ 'field-error': fieldErrors.author }" />
        <p v-if="fieldErrors.author" class="field-msg">{{ fieldErrors.author }}</p>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Year</label>
        <input v-model.number="form.year" type="number"
               :class="{ 'field-error': fieldErrors.year }" />
        <p v-if="fieldErrors.year" class="field-msg">{{ fieldErrors.year }}</p>
      </div>
      <div>
        <label>Genre</label>
        <input v-model="form.genre" :class="{ 'field-error': fieldErrors.genre }" />
        <p v-if="fieldErrors.genre" class="field-msg">{{ fieldErrors.genre }}</p>
      </div>
    </div>

    <p style="margin-top: 16px; display: flex; gap: 10px;">
      <button class="primary" @click="submit">Save</button>
      <button @click="$emit('cancel')">Cancel</button>
    </p>
  </div>
</template>
