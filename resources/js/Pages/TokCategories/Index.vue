<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const form = useForm({
  title: '',
})

function submit() {
  form.post('/tok-categories', {
    onSuccess: () => form.reset('title'),
  })
}

function destroy(id) {
  if (!confirm('¿Eliminar esta categoría?')) return
  useForm({}).delete(`/tok-categories/${id}`)
}
</script>

<template>
  <Head title="Categorías" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Categorías</h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-3xl space-y-8 sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input v-model="form.title" type="text" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</div>
              </div>
              <div>
                <button type="submit" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Crear</button>
              </div>
            </form>
          </div>
        </div>

        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium mb-3">Listado</h3>
            <ul class="divide-y divide-gray-200">
              <li v-for="c in categories" :key="c.id" class="py-3 flex items-center justify-between">
                <div class="font-medium">{{ c.title }}</div>
                <button @click="destroy(c.id)" class="text-sm text-red-600 hover:underline">Eliminar</button>
              </li>
              <li v-if="!categories.length" class="py-3 text-gray-500">No hay categorías aún.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
