<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth.user)
const role = computed(() => user.value?.role)
const dropdownOpen = ref(false)

const breadcrumbs = computed(() => {
    const parts = page.url.split('/').filter(Boolean)
    return parts.map((part, i) => ({
        label: part.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        href: '/' + parts.slice(0, i + 1).join('/'),
        isLast: i === parts.length - 1,
    }))
})
</script>

<template>
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-1.5 text-sm">
            <Link href="/dashboard" class="text-slate-400 hover:text-slate-700">Home</Link>
            <template v-for="crumb in breadcrumbs" :key="crumb.href">
                <span class="text-slate-300">/</span>
                <span :class="crumb.isLast ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                    <Link v-if="!crumb.isLast" :href="crumb.href">{{ crumb.label }}</Link>
                    <span v-else>{{ crumb.label }}</span>
                </span>
            </template>
        </nav>

        <!-- Right -->
        <div class="flex items-center gap-3">
            <!-- Bell -->
            <button class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full ring-2 ring-white" style="background-color:#0EA5E9"/>
            </button>

            <!-- User dropdown -->
            <div class="relative">
                <button @click="dropdownOpen = !dropdownOpen"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-semibold text-sm uppercase"
                        style="background-color:#0F2044">
                        {{ user?.name?.charAt(0) ?? 'U' }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-slate-800 leading-tight">{{ user?.name }}</p>
                        <p class="text-xs text-slate-400 leading-tight capitalize">{{ role?.replace('_', ' ') }}</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div v-if="dropdownOpen"
                    class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-lg border border-slate-100 py-1.5 z-50">
                    <div class="px-4 py-2.5 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-800">{{ user?.name }}</p>
                        <p class="text-xs text-slate-400">{{ user?.email }}</p>
                    </div>
                    <Link href="/change-password"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Change Password
                    </Link>
                    <div class="border-t border-slate-100 mt-1 pt-1">
                        <Link href="/logout" method="post" as="button"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors w-full text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign Out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
