<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { EyeIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    videotrons: Array,
});

const formatDateTime = (datetime) => {
    if (!datetime) return '';
    return new Date(datetime).toLocaleString('id-ID', {
        weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Manajemen Jadwal" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Manajemen Jadwal
            </h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                            Daftar Videotron
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Pilih videotron untuk melihat atau mengedit jadwal lengkapnya.
                        </p>
                        
                        <div class="mt-6 border-t border-gray-200 dark:border-gray-700">
                            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="videotron in videotrons" :key="videotron.id" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-x-6 py-5">
                                    <!-- Info Videotron -->
                                    <div class="min-w-0">
                                        <div class="flex items-start gap-x-3">
                                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ videotron.name }}</p>
                                            <span :class="[videotron.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20', 'rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset']">
                                                {{ videotron.status }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            <p class="whitespace-nowrap font-mono">{{ videotron.device_id || 'Device ID belum diatur' }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Ringkasan Jadwal -->
                                    <div class="min-w-0 flex-auto mt-4 sm:mt-0">
                                        <div v-if="videotron.schedule_items.length > 0" class="space-y-1">
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Jadwal Berikutnya:</p>
                                            <div v-for="item in videotron.schedule_items" :key="item.id" class="flex items-center gap-x-2">
                                                <p class="text-sm leading-6 text-gray-900 dark:text-white truncate" :title="item.media.title">
                                                   <span class="font-mono">{{ formatDateTime(item.play_at) }}</span> - {{ item.media.title }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center justify-start text-sm text-gray-500 dark:text-gray-400">
                                            <CalendarDaysIcon class="h-5 w-5 mr-2" />
                                            <span>Jadwal kosong</span>
                                        </div>
                                    </div>

                                    <!-- Tombol Aksi -->
                                    <div class="flex flex-none items-center gap-x-4 mt-4 sm:mt-0">
                                        <Link :href="route('admin.schedules.show', videotron.id)" class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:block">
                                            View / Edit<span class="sr-only">, {{ videotron.name }}</span>
                                        </Link>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
