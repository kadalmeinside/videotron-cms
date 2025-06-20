<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    siswa: Object,
    pageTitle: String,
    error: String, // Prop untuk menangani pesan error dari controller
});

const getStatusClass = (status) => {
    if (status === 'Aktif') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (status === 'Non-Aktif') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    if (status === 'Lulus') return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; // Cuti
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
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ error }}</p>
                </div>

                <div v-if="siswa" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Lengkap Siswa</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                                    <p class="mt-1">
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full" :class="getStatusClass(siswa.status_siswa)">
                                            {{ siswa.status_siswa }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Bergabung</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.tanggal_bergabung }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Wali</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.email_wali }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No. Telepon Wali</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.nomor_telepon_wali || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="siswa.kelas" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Kelas</h3>
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Kelas</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.kelas.nama_kelas }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Biaya SPP Kelas</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.kelas.biaya_spp_default_formatted }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Deskripsi</p>
                                    <p class="mt-1 text-base text-gray-600 dark:text-gray-300">{{ siswa.kelas.deskripsi || '-' }}</p>
                                </div>
                            </div>
                        </div>

                         <div v-if="siswa.jumlah_spp_custom_formatted !== '-'" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Keuangan Khusus</h3>
                             <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Biaya SPP Custom</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.jumlah_spp_custom_formatted }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Admin Fee Custom</p>
                                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ siswa.admin_fee_custom_formatted }}</p>
                                </div>
                            </div>
                         </div>

                    </div>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>