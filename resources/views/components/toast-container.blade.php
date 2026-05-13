<template x-for="notice in $store.toastManager.notices" :key="notice.id">
    <div
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg min-w-[320px] max-w-[420px]"
        :class="{
            'bg-white dark:bg-zinc-900 border-l-4 border-[--c-accent] text-zinc-900 dark:text-zinc-100': notice.type === 'success',
            'bg-white dark:bg-zinc-900 border-l-4 border-red-500 text-zinc-900 dark:text-zinc-100': notice.type === 'error',
            'bg-white dark:bg-zinc-900 border-l-4 border-blue-500 text-zinc-900 dark:text-zinc-100': notice.type === 'notice'
        }"
        role="alert"
    >
        <div class="flex-1 text-sm font-medium" x-text="notice.message"></div>
        <button
            @click="$store.toastManager.remove(notice.id)"
            class="flex-shrink-0 ml-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
            :aria-label="'Dismiss ' + notice.type + ' notification'"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>
