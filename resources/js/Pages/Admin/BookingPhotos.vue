<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Image, Upload, Trash2, Eye, EyeOff, ChevronUp,
    ChevronDown, GripVertical, ExternalLink, CheckCircle2,
    X, Plus, AlertTriangle,
} from 'lucide-vue-next'

const props = defineProps({ photos: Array })

const page        = usePage()
const uploadError = ref('')

// ── Upload ────────────────────────────────────────────
const fileInput    = ref(null)
const previews     = ref([]) // { file, url, caption }
const uploading    = ref(false)
const isDragging   = ref(false)

function onFileChange(e) {
    addFiles(Array.from(e.target.files))
    e.target.value = ''
}

function onDrop(e) {
    isDragging.value = false
    addFiles(Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')))
}

function addFiles(files) {
    files.forEach(file => {
        const url = URL.createObjectURL(file)
        previews.value.push({ file, url, caption: '' })
    })
}

function removePreview(i) {
    URL.revokeObjectURL(previews.value[i].url)
    previews.value.splice(i, 1)
}

function submitUpload() {
    if (!previews.value.length) return
    uploading.value = true

    const fd = new FormData()
    previews.value.forEach((p, i) => {
        fd.append(`photos[${i}]`, p.file)
        fd.append(`captions[${i}]`, p.caption)
    })

    uploadError.value = ''

    router.post(route('admin.booking-photos.store'), fd, {
        forceFormData: true,
        onSuccess: () => {
            previews.value.forEach(p => URL.revokeObjectURL(p.url))
            previews.value = []
            uploading.value = false
            uploadError.value = ''
        },
        onError: (errors) => {
            uploading.value = false
            uploadError.value = errors.upload_error
                ?? errors.photos
                ?? Object.values(errors)[0]
                ?? 'An unexpected error occurred during upload.'
        },
    })
}

// ── Reorder (up/down) ─────────────────────────────────
function move(index, dir) {
    const list   = [...props.photos]
    const target = index + dir
    if (target < 0 || target >= list.length) return

    ;[list[index], list[target]] = [list[target], list[index]]
    router.post(route('admin.booking-photos.reorder'), {
        order: list.map(p => p.id),
    }, { preserveScroll: true })
}

// ── Toggle / Delete / Caption ─────────────────────────
function toggle(photo) {
    router.patch(route('admin.booking-photos.toggle', photo.id), {}, { preserveScroll: true })
}

function destroy(photo) {
    if (!confirm(`Delete this photo? This cannot be undone.`)) return
    router.delete(route('admin.booking-photos.destroy', photo.id), { preserveScroll: true })
}

// Inline caption edit
const editingCaption = ref(null)
const captionDraft   = ref('')

function startEdit(photo) {
    editingCaption.value = photo.id
    captionDraft.value   = photo.caption ?? ''
}

function saveCaption(photo) {
    router.patch(route('admin.booking-photos.update', photo.id), {
        caption: captionDraft.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingCaption.value = null },
    })
}
</script>

<template>
    <AppLayout title="Booking Page Photos">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Booking Page Photos</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Manage photos displayed on the public appointment booking page</p>
                </div>
                <a :href="route('appointments.book')" target="_blank"
                    class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 border border-blue-200 rounded-xl px-3 py-1.5 hover:bg-blue-50 transition-colors">
                    <ExternalLink class="w-3.5 h-3.5"/> Preview Booking Page
                </a>
            </div>
        </template>

        <!-- ── Upload Zone ─────────────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm p-6 mb-6">
            <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <Upload class="w-4 h-4 text-slate-400"/> Upload New Photos
            </p>

            <!-- Upload error banner -->
            <Transition name="fade">
                <div v-if="uploadError"
                    class="flex items-start gap-3 mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <AlertTriangle class="w-5 h-5 text-red-600 shrink-0 mt-0.5"/>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-red-700 mb-0.5">Upload Failed</p>
                        <p class="text-xs text-red-600 break-words">{{ uploadError }}</p>
                    </div>
                    <button @click="uploadError = ''"
                        class="shrink-0 text-red-400 hover:text-red-600 transition-colors">
                        <X class="w-4 h-4"/>
                    </button>
                </div>
            </Transition>

            <!-- Drop zone -->
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                @click="fileInput.click()"
                :class="[
                    'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all',
                    isDragging
                        ? 'border-blue-400 bg-blue-50'
                        : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50'
                ]">
                <Image class="w-10 h-10 text-slate-300 mx-auto mb-2"/>
                <p class="text-sm font-semibold text-slate-600">Drop images here or click to browse</p>
                <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP — max 4 MB each</p>
                <input ref="fileInput" type="file" multiple accept="image/*"
                    class="hidden" @change="onFileChange"/>
            </div>

            <!-- Previews -->
            <div v-if="previews.length" class="mt-4">
                <p class="text-xs font-bold text-slate-500 uppercase mb-3">{{ previews.length }} photo(s) ready to upload</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                    <div v-for="(p, i) in previews" :key="i" class="relative group">
                        <img :src="p.url" class="w-full h-28 object-cover rounded-xl border"/>
                        <button @click.stop="removePreview(i)"
                            class="absolute top-1.5 right-1.5 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <X class="w-3.5 h-3.5"/>
                        </button>
                        <input v-model="p.caption" type="text" placeholder="Caption (optional)"
                            class="mt-1.5 w-full text-xs px-2 py-1 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="previews = []"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-700 border border-slate-200 rounded-xl px-4 py-2 transition-colors">
                        Clear All
                    </button>
                    <button @click="submitUpload"
                        :disabled="uploading"
                        class="flex items-center gap-1.5 text-xs font-bold text-white px-5 py-2 rounded-xl transition-opacity disabled:opacity-60"
                        style="background:#1B4F9B">
                        <Upload class="w-3.5 h-3.5"/>
                        {{ uploading ? 'Uploading...' : `Upload ${previews.length} Photo(s)` }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Photo Library ───────────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <p class="text-sm font-bold text-slate-700">
                    Photo Library
                    <span class="ml-2 text-xs font-normal text-slate-400">{{ photos.length }} total &bull; {{ photos.filter(p => p.is_active).length }} visible</span>
                </p>
                <p class="text-xs text-slate-400">Use arrows to reorder</p>
            </div>

            <div v-if="!photos.length" class="py-16 text-center">
                <Image class="w-12 h-12 text-slate-200 mx-auto mb-3"/>
                <p class="text-slate-500 font-semibold">No photos yet</p>
                <p class="text-slate-400 text-sm mt-1">Upload photos above to display them on the booking page</p>
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div v-for="(photo, idx) in photos" :key="photo.id"
                    class="flex items-center gap-4 px-5 py-3"
                    :class="photo.is_active ? '' : 'opacity-50'">

                    <!-- Sort order controls -->
                    <div class="flex flex-col gap-0.5 shrink-0">
                        <button @click="move(idx, -1)" :disabled="idx === 0"
                            class="w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 disabled:opacity-20 hover:bg-slate-100 transition-colors">
                            <ChevronUp class="w-3.5 h-3.5"/>
                        </button>
                        <span class="text-xs text-slate-300 text-center">{{ idx + 1 }}</span>
                        <button @click="move(idx, 1)" :disabled="idx === photos.length - 1"
                            class="w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 disabled:opacity-20 hover:bg-slate-100 transition-colors">
                            <ChevronDown class="w-3.5 h-3.5"/>
                        </button>
                    </div>

                    <!-- Thumbnail -->
                    <img :src="photo.url" class="w-20 h-14 object-cover rounded-xl border shrink-0"/>

                    <!-- Caption -->
                    <div class="flex-1 min-w-0">
                        <div v-if="editingCaption === photo.id" class="flex items-center gap-2">
                            <input v-model="captionDraft" type="text"
                                placeholder="Photo caption..."
                                class="flex-1 text-sm px-3 py-1.5 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                                @keyup.enter="saveCaption(photo)"
                                @keyup.escape="editingCaption = null"/>
                            <button @click="saveCaption(photo)"
                                class="text-xs font-bold text-white px-3 py-1.5 rounded-lg"
                                style="background:#1B4F9B">Save</button>
                            <button @click="editingCaption = null"
                                class="text-xs text-slate-400 hover:text-slate-600">Cancel</button>
                        </div>
                        <div v-else>
                            <p class="text-sm font-semibold text-slate-700 truncate">
                                {{ photo.caption || 'No caption' }}
                            </p>
                            <button @click="startEdit(photo)"
                                class="text-xs text-blue-500 hover:underline mt-0.5">
                                {{ photo.caption ? 'Edit caption' : 'Add caption' }}
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Added {{ photo.created_at }}</p>
                    </div>

                    <!-- Status badge -->
                    <div class="shrink-0">
                        <span :class="['text-xs font-bold px-2.5 py-1 rounded-full',
                            photo.is_active
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-slate-100 text-slate-400']">
                            {{ photo.is_active ? 'Visible' : 'Hidden' }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0">
                        <button @click="toggle(photo)"
                            :title="photo.is_active ? 'Hide from booking page' : 'Show on booking page'"
                            class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 transition-colors"
                            :class="photo.is_active ? 'hover:text-slate-600' : 'hover:text-emerald-600'">
                            <EyeOff v-if="photo.is_active" class="w-4 h-4"/>
                            <Eye v-else class="w-4 h-4"/>
                        </button>
                        <button @click="destroy(photo)"
                            title="Delete photo"
                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                            <Trash2 class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(-4px); }
</style>
