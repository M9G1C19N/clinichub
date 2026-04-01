<script setup>
defineProps({
    status: { type: String, required: true },
    size:   { type: String, default: 'sm' },
})

const statusConfig = {
    waiting:    { label: 'Waiting',    bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500' },
    calling:    { label: 'Calling',    bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500', pulse: true },
    serving:    { label: 'Serving',    bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', pulse: true },
    completed:  { label: 'Done',       bg: 'bg-slate-50',   text: 'text-slate-500',   dot: 'bg-slate-400' },
    no_show:    { label: 'No Show',    bg: 'bg-red-50',     text: 'text-red-600',     dot: 'bg-red-500' },
    skipped:    { label: 'Skipped',    bg: 'bg-slate-50',   text: 'text-slate-400',   dot: 'bg-slate-300' },
    released:   { label: 'Released',  bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    processing: { label: 'Draft',     bg: 'bg-blue-50',    text: 'text-blue-600',    dot: 'bg-blue-500' },
    pending:    { label: 'Pending',   bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500' },
    finalized:  { label: 'Finalized', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    in_progress:{ label: 'Draft',    bg: 'bg-blue-50',    text: 'text-blue-600',    dot: 'bg-blue-400' },
    none:       { label: 'N/A',       bg: 'bg-slate-50',   text: 'text-slate-400',   dot: 'bg-slate-300' },
    paid:       { label: 'Paid',      bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    unpaid:     { label: 'Unpaid',    bg: 'bg-red-50',     text: 'text-red-600',     dot: 'bg-red-500' },
    partial:    { label: 'Partial',   bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500' },
    cancelled:  { label: 'Cancelled', bg: 'bg-slate-50',   text: 'text-slate-400',   dot: 'bg-slate-300' },
}
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full font-semibold"
        :class="[
            statusConfig[status]?.bg ?? 'bg-slate-50',
            statusConfig[status]?.text ?? 'text-slate-500',
            size === 'xs' ? 'text-xs px-2 py-0.5' : 'text-xs px-2.5 py-1'
        ]"
    >
        <span
            class="rounded-full"
            :class="[
                statusConfig[status]?.dot ?? 'bg-slate-300',
                statusConfig[status]?.pulse ? 'animate-pulse' : '',
                size === 'xs' ? 'w-1.5 h-1.5' : 'w-2 h-2'
            ]"
        />
        {{ statusConfig[status]?.label ?? status }}
    </span>
</template>
