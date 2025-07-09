<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { UsersIcon, VideoCameraIcon, QueueListIcon, PhotoIcon, PlayCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';

const page = usePage();

// Gunakan computed untuk mengambil props dengan aman
const stats = computed(() => page.props.stats || {});
const nowPlaying = computed(() => page.props.nowPlaying || []);
const pendingMedia = computed(() => page.props.pendingMedia || []);

const statItems = computed(() => [
    { name: 'Total Klien', value: stats.value.clients, icon: UsersIcon },
    { name: 'Videotron Aktif', value: stats.value.videotrons, icon: VideoCameraIcon },
    { name: 'Total Playlist', value: stats.value.playlists, icon: QueueListIcon },
    { name: 'Total Media', value: stats.value.media, icon: PhotoIcon },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div v-for="item in statItems" :key="item.name" class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
                <div class="bg-blue-100 p-3 rounded-full">
                    <component :is="item.icon" class="h-6 w-6 text-blue-600" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ item.name }}</p>
                    <p class="text-2xl font-bold text-gray-800">{{ item.value }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 flex items-center"><PlayCircleIcon class="h-6 w-6 mr-2 text-green-500"/>Sedang Tayang Saat Ini</h2>
                    <div v-if="nowPlaying.length" class="space-y-4">
                        <div v-for="schedule in nowPlaying" :key="schedule.id" class="p-4 border rounded-md">
                            <p class="font-bold text-gray-800">{{ schedule.playlist.name }}</p>
                            <p class="text-sm text-gray-600">di <span class="font-medium">{{ schedule.videotron.name }}</span></p>
                            <p class="text-xs text-gray-400 mt-1">Berakhir pada: {{ new Date(schedule.end_time).toLocaleTimeString('id-ID') }}</p>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-gray-500">Tidak ada jadwal yang sedang tayang.</p>
                    </div>
                </div>
            </div> -->

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 flex items-center"><ClockIcon class="h-6 w-6 mr-2 text-yellow-500"/>Media Menunggu Persetujuan</h2>
                    <div v-if="pendingMedia.length" class="space-y-3">
                         <div v-for="media in pendingMedia" :key="media.id" class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-700">{{ media.title }}</p>
                                <p class="text-xs text-gray-500">oleh {{ media.client.company_name }}</p>
                            </div>
                            <Link :href="route('admin.media.index')" class="text-sm text-blue-600 hover:underline">Lihat</Link>
                         </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-gray-500">Semua media sudah disetujui.</p>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
