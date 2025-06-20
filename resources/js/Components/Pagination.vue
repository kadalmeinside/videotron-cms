<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <!-- Tampilkan paginasi hanya jika ada lebih dari 3 link (Prev, 1, Next) -->
    <nav v-if="links.length > 3" aria-label="Pagination">
        <ul class="flex items-center justify-center -space-y-px text-sm">
            <template v-for="(link, key) in links" :key="key">
                <li>
                    <!-- Tombol "Previous" / "Back" -->
                    <template v-if="link.label.includes('Previous')">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="flex items-center justify-center px-4 h-9 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white mx-1"
                        >
                            &lt; Back
                        </Link>
                        <span
                            v-else
                            class="flex items-center justify-center px-4 h-9 ms-0 leading-tight text-gray-400 bg-white border border-gray-300 rounded-lg cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500 mx-1"
                        >
                            &lt; Back
                        </span>
                    </template>
                    
                    <!-- Tombol "Next" -->
                    <template v-else-if="link.label.includes('Next')">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="flex items-center justify-center px-4 h-9 leading-tight text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white mx-1"
                        >
                            Next &gt;
                        </Link>
                        <span
                            v-else
                            class="flex items-center justify-center px-4 h-9 leading-tight text-gray-400 bg-white border border-gray-300 rounded-lg cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500 mx-1"
                        >
                            Next &gt;
                        </span>
                    </template>
                    
                    <!-- Item di Tengah (Angka atau ...) -->
                    <template v-else>
                         <!-- Jika item adalah link angka yang bisa diklik -->
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="flex items-center justify-center px-4 h-9 leading-tight mx-1 rounded-lg"
                            :class="{
                                'z-10 text-white bg-gray-900 border border-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:border-gray-600': link.active,
                                'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white': !link.active,
                            }"
                            v-html="link.label"
                        />
                        <!-- Jika item adalah separator "..." yang tidak bisa diklik -->
                        <span
                            v-else
                            class="flex items-center justify-center px-4 h-9 leading-tight text-gray-500 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 mx-1"
                            v-html="link.label"
                        ></span>
                    </template>
                </li>
            </template>
        </ul>
    </nav>
</template>
