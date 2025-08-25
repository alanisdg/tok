<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  user: { type: Object, required: true },
  toks: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 20, total: 0 }) },
})

const page = ref(props.meta.current_page || 1)
const perPage = ref(props.meta.per_page || 20)
const loading = ref(false)
const error = ref('')

const pageCtx = usePage()
const isSelf = computed(() => {
  const me = pageCtx?.props?.auth?.user
  return me && props.user && me.id === props.user.id
})

async function goTo(p) {
  if (p < 1 || (props.meta.last_page && p > props.meta.last_page)) return
  await fetchPage(p, perPage.value)
}

async function changePerPage(val) {
  await fetchPage(1, val)
}

async function fetchPage(p, pp) {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    params.set('page', String(p))
    params.set('per_page', String(pp))
    const res = await fetch(`${window.location.pathname}?${params.toString()}`, {
      headers: { 'Accept': 'text/html, application/xhtml+xml' },
      credentials: 'same-origin',
    })
    // Let Inertia handle full visit for pagination to keep SSR/props
    window.location.href = `${window.location.pathname}?${params.toString()}`
  } catch (e) {
    error.value = e.message || 'Error'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Head :title="props.user.name" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        {{ props.user.name }} — Toks
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <div class="p-6">
            <div class="mb-4" v-if="!isSelf">
              <button type="button" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                Solicitar videollamada
              </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-700 mb-4">
              <div class="flex items-center gap-2">
                <span>Por página:</span>
                <select :value="perPage" @change="changePerPage(($event.target).value)" class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="10">10</option>
                  <option value="20">20</option>
                  <option value="50">50</option>
                </select>
              </div>
              <div class="flex items-center gap-2">
                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || props.meta.current_page <= 1" @click="goTo(props.meta.current_page - 1)">Anterior</button>
                <span>Página {{ props.meta.current_page }} de {{ props.meta.last_page }}</span>
                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || (props.meta.last_page && props.meta.current_page >= props.meta.last_page)" @click="goTo(props.meta.current_page + 1)">Siguiente</button>
              </div>
            </div>

            <ul class="divide-y divide-gray-200">
              <li v-for="t in props.toks" :key="t.id" class="py-3">
                <div class="font-medium">{{ t.title }}</div>
                <div class="text-sm text-gray-500">Categoría: {{ t.category ? t.category.title : '—' }}</div>
              </li>
              <li v-if="!props.toks.length" class="py-3 text-gray-500">Sin Toks.</li>
            </ul>

            <div class="mt-4 flex items-center justify-between text-sm text-gray-700" v-if="props.meta.total">
              <div>Total: {{ props.meta.total }}</div>
              <div class="flex items-center gap-2">
                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || props.meta.current_page <= 1" @click="goTo(props.meta.current_page - 1)">Anterior</button>
                <span>Página {{ props.meta.current_page }} de {{ props.meta.last_page }}</span>
                <button class="px-3 py-1 rounded border hover:bg-gray-50 disabled:opacity-50" :disabled="loading || (props.meta.last_page && props.meta.current_page >= props.meta.last_page)" @click="goTo(props.meta.current_page + 1)">Siguiente</button>
              </div>
            </div>

            <div v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
