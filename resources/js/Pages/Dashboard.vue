<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue'

const q = ref('')
const all = ref(true)
const loading = ref(false)
const toks = ref([])
const users = ref([])
const error = ref('')
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const page = ref(1)
const perPage = ref(20)

async function search() {
  error.value = ''
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (q.value) params.set('q', q.value)
    if (all.value) params.set('all', '1')
    params.set('page', String(page.value))
    params.set('per_page', String(perPage.value))
    const res = await fetch(`/toks/search?${params.toString()}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error('Error de búsqueda')
    const data = await res.json()
    toks.value = data.toks || []
    users.value = data.users || []
    meta.value = data.meta || meta.value
  } catch (e) {
    error.value = e.message || 'Error'
  } finally {
    loading.value = false
  }
}

// Debounce helper
let debounceTimer
function debounced(fn, delay = 350) {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fn, delay)
}

// Watch term and toggle to auto-search with debounce, resetting to page 1
watch([q, all], () => {
  page.value = 1
  debounced(() => {
    search()
  })
})

function goTo(p) {
  if (p < 1 || (meta.value.last_page && p > meta.value.last_page)) return
  page.value = p
  search()
}

watch(perPage, () => {
  page.value = 1
  search()
})

onMounted(() => {
  search()
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 space-y-4">
                        <div class="flex gap-3">
                            <a href="/toks" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Toks</a>
                            <a href="/tok-categories" class="inline-flex items-center rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">Categorías</a>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold">Buscar Toks</h3>
                        <form @submit.prevent="search" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                            <div class="flex-1 w-full">
                                <label class="block text-sm font-medium text-gray-700">Término</label>
                                <input v-model="q" type="text" placeholder="palabra clave..." class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="all" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                Buscar en todos los usuarios
                            </label>
                            <button type="submit" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="loading">
                                {{ loading ? 'Buscando...' : 'Buscar' }}
                            </button>
                        </form>
                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-700">
                            <div class="flex items-center gap-2">
                                <span>Por página:</span>
                                <select v-model.number="perPage" class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || page <= 1" @click="goTo(page - 1)">Anterior</button>
                                <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
                                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || (meta.last_page && page >= meta.last_page)" @click="goTo(page + 1)">Siguiente</button>
                            </div>
                        </div>
                        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-md font-medium mb-3">Resultados Toks</h4>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="t in toks" :key="t.id" class="py-3">
                                    <div class="font-medium">{{ t.title }}</div>
                                    <div class="text-sm text-gray-500">
                                      Usuario:
                                      <template v-if="t.user?.name">
                                        <a :href="`/${encodeURIComponent(t.user.name)}`" class="text-indigo-600 hover:underline">{{ t.user.name }}</a>
                                      </template>
                                      <template v-else>—</template>
                                      • Categoría: {{ t.category ? t.category.title : '—' }}
                                    </div>
                                </li>
                                <li v-if="!loading && !toks.length" class="py-3 text-gray-500">Sin resultados.</li>
                            </ul>
                            <div class="mt-4 flex items-center justify-between text-sm text-gray-700" v-if="meta.total">
                                <div>Total: {{ meta.total }}</div>
                                <div class="flex items-center gap-2">
                                    <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || page <= 1" @click="goTo(page - 1)">Anterior</button>
                                    <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
                                    <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || (meta.last_page && page >= meta.last_page)" @click="goTo(page + 1)">Siguiente</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-md font-medium mb-3">Usuarios con coincidencias</h4>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="u in users" :key="u.id" class="py-3 flex justify-between">
                                    <span>{{ u.name ?? 'Usuario #' + u.id }}</span>
                                    <span class="text-sm text-gray-500">{{ u.count }} toks</span>
                                </li>
                                <li v-if="!loading && !users.length" class="py-3 text-gray-500">Sin usuarios coincidentes.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
