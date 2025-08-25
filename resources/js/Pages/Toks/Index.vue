<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  toks: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const form = useForm({
  title: '',
  tok_category_id: null,
})

function submit() {
  form.post('/toks', {
    onSuccess: () => {
      form.reset('title', 'tok_category_id')
    },
  })
}

function destroy(id) {
  if (!confirm('¿Eliminar este Tok?')) return
  useForm({}).delete(`/toks/${id}`)
}

// Inline edit state
const editingId = ref(null)
const editForm = useForm({
  title: '',
  tok_category_id: null,
})

function startEdit(t) {
  editingId.value = t.id
  editForm.title = t.title
  editForm.tok_category_id = t.tok_category_id ?? null
}

function cancelEdit() {
  editingId.value = null
  editForm.reset('title', 'tok_category_id')
  editForm.clearErrors()
}

function saveEdit(id) {
  editForm.patch(`/toks/${id}`, {
    onSuccess: () => {
      cancelEdit()
    },
  })
}
</script>

<template>
  <Head title="Toks" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Toks</h2>
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
                <label class="block text-sm font-medium text-gray-700">Category (opcional)</label>
                <select v-model="form.tok_category_id" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option :value="null">— Sin categoría —</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.title }}</option>
                </select>
                <div v-if="form.errors.tok_category_id" class="text-sm text-red-600 mt-1">{{ form.errors.tok_category_id }}</div>
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
              <li v-for="t in toks" :key="t.id" class="py-3 flex items-start justify-between gap-4">
                <div class="flex-1">
                  <template v-if="editingId === t.id">
                    <div class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input v-model="editForm.title" type="text" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div v-if="editForm.errors.title" class="text-sm text-red-600 mt-1">{{ editForm.errors.title }}</div>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Categoría</label>
                        <select v-model="editForm.tok_category_id" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                          <option :value="null">— Sin categoría —</option>
                          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.title }}</option>
                        </select>
                        <div v-if="editForm.errors.tok_category_id" class="text-sm text-red-600 mt-1">{{ editForm.errors.tok_category_id }}</div>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="font-medium">{{ t.title }}</div>
                    <div class="text-sm text-gray-500">Categoría: {{ t.category ? t.category.title : '—' }}</div>
                  </template>
                </div>
                <div class="flex items-center gap-3">
                  <template v-if="editingId === t.id">
                    <button @click="saveEdit(t.id)" class="text-sm text-green-700 hover:underline">Guardar</button>
                    <button @click="cancelEdit" class="text-sm text-gray-600 hover:underline">Cancelar</button>
                  </template>
                  <template v-else>
                    <button @click="startEdit(t)" class="text-sm text-indigo-700 hover:underline">Editar</button>
                    <button @click="destroy(t.id)" class="text-sm text-red-600 hover:underline">Eliminar</button>
                  </template>
                </div>
              </li>
              <li v-if="!toks.length" class="py-3 text-gray-500">No hay Toks aún.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
