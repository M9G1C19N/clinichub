<script setup>
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const user = page.props.auth.user
</script>
<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-slate-800">Welcome, {{ user?.name }} 👋</h1>
            <p class="text-slate-500 mt-2">Role: <span class="font-semibold">{{ user?.role }}</span></p>
            <div class="mt-4 inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 rounded-xl text-sm">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse inline-block"></span>
                Dashboard loading...
            </div>
        </div>
    </div>
</template>
