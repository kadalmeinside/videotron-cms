<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ClipboardDocumentListIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const logs = computed(() => page.props.logs || { data: [], links: [] });
const filters = computed(() => page.props.filters || {});
const allVideotrons = computed(() => page.props.allVideotrons || []);

const filterForm = useForm({
    date: filters.value.date || '',
    videotron_id: filters.value.videotron_id || '',
});

const submitFilter = () => {
    filterForm.get(route('admin.logs.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.reset();
    submitFilter();
};

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return new Date(datetime).toLocaleString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.date) params.append('date', filterForm.date);
    if (filterForm.videotron_id) params.append('videotron_id', filterForm.videotron_id);
    
    return `${route('admin.logs.export')}?${params.toString()}`;
});
</script>

<template>
    <Head title="Log Aktivitas Player" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Log Aktivitas Player
            </h2>
        </template>
        
        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto space-y-6">

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                             <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 sm:mb-0">Filter Log</h3>
                            
                             <a :href="exportUrl" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                 <ArrowDownTrayIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                                 Ekspor Excel
                             </a>
                        </div>
                        <form @submit.prevent="submitFilter" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <InputLabel for="date" value="Tanggal" />
                                <TextInput id="date" type="date" class="mt-1 block w-full" v-model="filterForm.date" />
                            </div>
                            <div>
                                <InputLabel for="videotron_id" value="Videotron" />
                                <select id="videotron_id" v-model="filterForm.videotron_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-[--color-primary-500] dark:focus:border-[--color-primary-600] focus:ring-[--color-primary-500] dark:focus:ring-[--color-primary-600] rounded-md shadow-sm">
                                    <option value="">Semua Videotron</option>
                                    <option v-for="v in allVideotrons" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                            </div>
                            <div class="flex gap-x-2">
                                <PrimaryButton :disabled="filterForm.processing">Filter</PrimaryButton>
                                <SecondaryButton @click="clearFilters" type="button">Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Videotron</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pesan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!logs.data.length">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-gray-400" />
                                        <h3 class="mt-2 text-sm font-semibold">Tidak Ada Log</h3>
                                        <p class="mt-1 text-sm">Tidak ada data log untuk ditampilkan.</p>
                                    </td>
                                </tr>
                                <tr v-for="log in logs.data" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(log.logged_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ log.videotron?.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="log.event_type.includes('FAIL') ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'">
                                            {{ log.event_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ log.message }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="logs.data.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
