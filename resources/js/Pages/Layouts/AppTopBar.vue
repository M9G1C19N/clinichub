<script setup>
import { ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Bell, ChevronDown, LogOut, User, KeyRound } from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'

const { user, role } = usePermissions()
const page = usePage()

const dropdownOpen = ref(false)

// Build breadcrumb from current URL
const breadcrumbs = computed(() => {
    const parts = page.url.split('/').filter(Boolean)
    return parts.map((part, i) => ({
        label: part.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        href: '/' + parts.slice(0, i + 1).join('/'),
        isLast: i === parts.length - 1,
    }))
})
</script>

<script>
import { computed } from 'vue'
export default { name: 'AppTopBar' }
</script>

<template>
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0 shadow-sm">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-1.5 text-sm">
            <Link href="/dashboard" class="text-mid-blue hover:text-blue font-medium">
                Home
            </Link>
            <template v-for="crumb in breadcrumbs" :key="crumb.href">
                <span class="text-slate-400">/</span>
                <span
                    :class="crumb.isLast ? 'text-navy font-semibold' : 'text-slate-500 hover:text-blue cursor-pointer'"
                >
                    <Link v-if="!crumb.isLast" :href="crumb.href">{{ crumb.label }}</Link>
                    <span v-else>{{ crumb.label }}</span>
                </span>
            </template>
        </nav>

        <!-- Right Side -->
        <div class="flex items-center gap-3">
            <!-- Notification Bell -->
            <button class="relative p-2 rounded-xl text-slate-500 hover:text-navy hover:bg-sky transition-colors">
                <Bell class="w-5 h-5" />
                <!-- Unread badge -->
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-accent rounded-full ring-2 ring-white" />
            </button>

            <!-- User Dropdown -->
            <div class="relative">
                <button
                    @click="dropdownOpen = !dropdownOpen"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-sky transition-colors"
                >
                    <div class="w-8 h-8 rounded-lg bg-navy flex items-center justify-center text-white font-semibold text-sm uppercase">
                        {{ user?.name?.charAt(0) ?? 'U' }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-navy leading-tight">{{ user?.name }}</p>
                        <p class="text-xs text-slate-500 leading-tight capitalize">{{ role?.replace('_', ' ') }}</p>
                    </div>
                    <ChevronDown class="w-4 h-4 text-slate-400" />
                </button>

                <!-- Dropdown Menu -->
                <transition name="dropdown">
                    <div
                        v-if="dropdownOpen"
                        v-click-outside="() => dropdownOpen = false"
                        class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-card border border-slate-100 py-1.5 z-50"
                    >
                        <div class="px-4 py-2.5 border-b border-slate-100">
                            <p class="text-sm font-semibold text-navy">{{ user?.name }}</p>
                            <p class="text-xs text-slate-500">{{ user?.email }}</p>
                        </div>

                        <Link
                            href="/profile"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-sky hover:text-navy transition-colors"
                        >
                            <User class="w-4 h-4" />
                            My Profile
                        </Link>

                        <Link
                            href="/change-password"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-sky hover:text-navy transition-colors"
                        >
                            <KeyRound class="w-4 h-4" />
                            Change Password
                        </Link>

                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors w-full text-left"
                            >
                                <LogOut class="w-4 h-4" />
                                Sign Out
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
