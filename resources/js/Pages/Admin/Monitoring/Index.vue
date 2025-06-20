<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { CheckCircleIcon, XCircleIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    allVideotrons: Array,
});

const onlineVideotronIds = ref(new Set());

const formatLastSeen = (timestamp) => {
    if (!timestamp) return 'Belum pernah terlihat';
    return `Terakhir terlihat pada: ${new Date(timestamp).toLocaleTimeString('id-ID')}`;
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    if (!window.Echo) {
        console.error("Laravel Echo tidak diinisialisasi!");
        return;
    }

    window.Echo.join('monitoring')
        .here((users) => {
            console.log('Diterima saat masuk:', users);
            const onlineIds = users.map(user => user.id); // user.id dari Reverb berisi id
            onlineVideotronIds.value = new Set(onlineIds);
        })
        .joining((user) => {
            console.log('Bergabung:', user);
            const newSet = new Set(onlineVideotronIds.value);
            newSet.add(user.id);
            onlineVideotronIds.value = newSet;
        })
        .leaving((user) => {
            console.log('Meninggalkan:', user);
            // --- PERBAIKAN TYPO DI SINI ---
            // Menggunakan variabel yang benar: onlineVideotronIds
            const newSet = new Set(onlineVideotronIds.value); 
            newSet.delete(user.id);
            onlineVideotronIds.value = newSet;
        })
        .error((error) => {
            console.error('Koneksi WebSocket Error:', error);
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('monitoring');
    }
});

</script>

<template>
    <Head title="Monitoring Status" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Monitoring Real-Time Videotron
            </h2>
        </template>
        
        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                           
                           <!-- Iterasi melalui semua videotron yang ada di sistem -->
                           <div v-for="videotron in allVideotrons" :key="videotron.id"
                                class="border-l-4 p-4 rounded-r-lg shadow-md transition-all duration-300"
                                :class="{
                                    // --- PERBAIKAN PERBANDINGAN DI SINI ---
                                    // Bandingkan dengan videotron.id, bukan videotron.id
                                    'border-green-500 bg-green-50 dark:bg-green-900/20': onlineVideotronIds.has(videotron.id),
                                    'border-red-500 bg-red-50 dark:bg-red-900/20': !onlineVideotronIds.has(videotron.id),
                                }">
                                
                                <p class="font-bold text-lg text-gray-800 dark:text-gray-100">{{ videotron.name }}</p>
                                
                                <div class="flex items-center mt-1">
                                    <!-- --- PERBAIKAN PERBANDINGAN DI SINI --- -->
                                    <template v-if="onlineVideotronIds.has(videotron.id)">
                                        <CheckCircleIcon class="h-5 w-5 mr-1.5 text-green-500" />
                                        <span class="text-sm font-medium text-green-700 dark:text-green-400">Online</span>
                                    </template>
                                    <template v-else>
                                        <XCircleIcon class="h-5 w-5 mr-1.5 text-red-500" />
                                        <span class="text-sm font-medium text-red-700 dark:text-red-400">Offline</span>
                                    </template>
                                </div>

                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 h-4">
                                    {{ videotron.id }}
                                </p>
                           </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
