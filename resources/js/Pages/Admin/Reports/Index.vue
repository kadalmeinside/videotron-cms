<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { DocumentChartBarIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue'; // Pastikan komponen ini sudah Anda buat

// Mengambil props yang dikirim dari ReportController
const page = usePage();
const logs = computed(() => page.props.logs || { data: [], links: [] });
const filters = computed(() => page.props.filters || {});
const allVideotrons = computed(() => page.props.allVideotrons || []);
const allClients = computed(() => page.props.allClients || []);

// Menggunakan useForm untuk mengelola state filter dengan mudah
const filterForm = useForm({
    date_start: filters.value.date_start || '',
    date_end: filters.value.date_end || '',
    videotron_id: filters.value.videotron_id || '',
    client_id: filters.value.client_id || '',
});

// Fungsi untuk mengirim request filter ke backend
const submitFilter = () => {
    filterForm.get(route('admin.reports.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

// Fungsi untuk menghapus semua filter dan kembali ke tampilan awal
const clearFilters = () => {
    router.get(route('admin.reports.index'));
};

// Helper untuk memformat tanggal dan waktu agar mudah dibaca
const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return new Date(datetime).toLocaleString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

// URL Ekspor Dinamis yang menyertakan filter aktif
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.date_start) params.append('date_start', filterForm.date_start);
    if (filterForm.date_end) params.append('date_end', filterForm.date_end);
    if (filterForm.videotron_id) params.append('videotron_id', filterForm.videotron_id);
    if (filterForm.client_id) params.append('client_id', filterForm.client_id);
    
    return `${route('admin.reports.export')}?${params.toString()}`;
});
</script>

<template>
    <Head title="Laporan Tayang" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Laporan Proof-of-Play
            </h2>
        </template>
        
        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto space-y-6">

                <!-- Panel Filter -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                             <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 sm:mb-0">Filter Laporan</h3>
                             <!-- Tombol Ekspor -->
                             <a :href="exportUrl" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                 <ArrowDownTrayIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                                 Ekspor Excel
                             </a>
                        </div>
                        <form @submit.prevent="submitFilter" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <div>
                                <InputLabel for="date_start" value="Dari Tanggal" />
                                <TextInput id="date_start" type="date" class="mt-1 block w-full" v-model="filterForm.date_start" />
                            </div>
                            <div>
                                <InputLabel for="date_end" value="Sampai Tanggal" />
                                <TextInput id="date_end" type="date" class="mt-1 block w-full" v-model="filterForm.date_end" />
                            </div>
                            <div>
                                <InputLabel for="videotron_id" value="Videotron" />
                                <select id="videotron_id" v-model="filterForm.videotron_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-[--color-primary-500] dark:focus:border-[--color-primary-600] focus:ring-[--color-primary-500] dark:focus:ring-[--color-primary-600] rounded-md shadow-sm">
                                    <option value="">Semua Videotron</option>
                                    <option v-for="v in allVideotrons" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="client_id" value="Klien" />
                                <select id="client_id" v-model="filterForm.client_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-[--color-primary-500] dark:focus:border-[--color-primary-600] focus:ring-[--color-primary-500] dark:focus:ring-[--color-primary-600] rounded-md shadow-sm">
                                    <option value="">Semua Klien</option>
                                    <option v-for="c in allClients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                                </select>
                            </div>
                            <div class="flex gap-x-2">
                                <PrimaryButton :disabled="filterForm.processing">Filter</PrimaryButton>
                                <SecondaryButton @click="clearFilters" type="button">Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Hasil Laporan -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Waktu Tayang</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Media</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Klien</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Playlist</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Videotron</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!logs.data.length">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        <DocumentChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                                        <h3 class="mt-2 text-sm font-semibold">Tidak Ada Data</h3>
                                        <p class="mt-1 text-sm">Tidak ada data untuk ditampilkan. Coba sesuaikan filter Anda.</p>
                                    </td>
                                </tr>
                                <tr v-for="log in logs.data" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(log.played_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ log.media?.title }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ log.media?.client?.company_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ log.playlist?.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ log.videotron?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Komponen Paginasi -->
                    <div v-if="logs.data.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
