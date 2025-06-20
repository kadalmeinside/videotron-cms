<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3'; // <-- Import Link

// Terima prop 'href', defaultnya null atau undefined
const props = defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    href: {
        type: String,
        default: null,
    },
});

// Tentukan komponen apa yang akan dirender
const componentIs = computed(() => {
    return props.href ? Link : 'button';
});
</script>

<template>
    <component
        :is="componentIs"
        :href="href" 
        :type="type"
        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
    >
        <slot />
    </component>
</template>