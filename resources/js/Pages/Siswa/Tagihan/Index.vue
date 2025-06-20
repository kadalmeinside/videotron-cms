<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue'; // Gunakan layout khusus siswa
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { EyeIcon, BanknotesIcon } from '@heroicons/vue/20/solid';

const page = usePage();

// Gunakan computed dengan default yang aman untuk mencegah error
const tagihanList = computed(() => page.props.tagihanList || { data: [], links: [] });
const pageTitle = computed(() => page.props.pageTitle || 'Tagihan Saya');
const errorMessage = computed(() => page.props.error_message || null);

// Helper untuk styling status
const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
    <Head :title="pageTitle" />

    <SiswaLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="errorMessage" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md shadow-md" role="alert">
                    <p class="font-bold">Terjadi Kesalahan</p>
                    <p>{{ errorMessage }}</p>
                </div>

                <div class="md:hidden space-y-4">
                    <div v-if="tagihanList.data.length === 0 && !errorMessage" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <p class="text-center text-gray-500 dark:text-gray-400">Tidak ada data tagihan untuk ditampilkan.</p>
                    </div>
                    <div v-else v-for="tagihan in tagihanList.data" :key="tagihan.id_tagihan" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-100">{{ tagihan.periode_tagihan }}</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ tagihan.total_tagihan_formatted }}</p>
                            </div>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(tagihan.status_pembayaran_xendit)">
                                {{ tagihan.status_pembayaran_xendit }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Jatuh Tempo: {{ tagihan.tanggal_jatuh_tempo }}
                        </div>
                        <div v-if="tagihan.can_pay && tagihan.xendit_payment_url" class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <a :href="tagihan.xendit_payment_url" target="_blank" class="w-full inline-flex items-center justify-center text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none dark:focus:ring-indigo-800">
                                <BanknotesIcon class="h-5 w-5 mr-2" />
                                Bayar Sekarang
                            </a>
                        </div>
                        <div v-else-if="tagihan.xendit_payment_url" class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                             <a :href="tagihan.xendit_payment_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200 inline-flex items-center" title="Lihat Invoice">
                                <EyeIcon class="h-4 w-4 mr-1"/> Lihat Invoice
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Tagihan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="tagihanList.data.length === 0 && !errorMessage">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data tagihan.</td>
                                </tr>
                                <tr v-else v-for="tagihan in tagihanList.data" :key="tagihan.id_tagihan">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ tagihan.periode_tagihan }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ tagihan.total_tagihan_formatted }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ tagihan.tanggal_jatuh_tempo }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(tagihan.status_pembayaran_xendit)">
                                            {{ tagihan.status_pembayaran_xendit }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a v-if="tagihan.can_pay && tagihan.xendit_payment_url" :href="tagihan.xendit_payment_url" target="_blank" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none dark:focus:ring-indigo-800">
                                            Bayar Sekarang
                                        </a>
                                        <a v-else-if="tagihan.xendit_payment_url" :href="tagihan.xendit_payment_url" target="_blank" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200" title="Lihat Invoice">
                                            Lihat Invoice
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="tagihanList.links && tagihanList.links.length > 3" class="mt-6">
                    <div class="flex flex-wrap -mb-1 justify-center">
                        <template v-for="(link, key) in tagihanList.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 select-none" v-html="link.label" />
                            <Link v-else
                                  class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                                  :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active }"
                                  :href="link.url"
                                  v-html="link.label"
                                  preserve-scroll />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>