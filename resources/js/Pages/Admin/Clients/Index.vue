<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';


// Menggunakan computed untuk keamanan dan reaktivitas
const page = usePage();
const clientList = computed(() => page.props.clientList || { data: [] });
const filters = computed(() => page.props.filters || { search: '' });
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// State untuk Modal dan Form
const showClientModal = ref(false);
const isEditMode = ref(false);
const form = useForm({
    id: null,
    company_name: '',
    contact_person: '',
    contact_email: '',
    password: '',
    password_confirmation: '',
});

// State untuk Modal Hapus
const showDeleteConfirmModal = ref(false);
const clientToDelete = ref(null);

// State untuk Pencarian
const searchQuery = ref(filters.value.search);
watch(searchQuery, debounce((value) => {
    router.get(route('admin.clients.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['clientList', 'filters'],
    });
}, 300));


// Fungsi-fungsi Modal
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    showClientModal.value = true;
};

const openEditModal = (clientItem) => {
    const data = clientItem.full_data_for_edit;
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = data.id;
    form.company_name = data.company_name;
    form.contact_person = data.contact_person;
    form.contact_email = data.user.email;
    form.password = '';
    form.password_confirmation = '';
    showClientModal.value = true;
};

const closeModal = () => {
    showClientModal.value = false;
    form.reset();
};

// --- PERUBAHAN DI SINI: FUNGSI SUBMIT DENGAN VALIDASI INSTAN ---
const submitClientForm = () => {
    // 1. Hapus error lama sebelum validasi baru
    form.clearErrors();

    // 2. Lakukan validasi sisi klien (client-side)
    let hasClientErrors = false;
    if (!form.company_name) {
        form.setError('company_name', 'Nama Perusahaan wajib diisi.');
        hasClientErrors = true;
    }
    if (!form.contact_person) {
        form.setError('contact_person', 'Nama Narahubung wajib diisi.');
        hasClientErrors = true;
    }
    if (!form.contact_email) {
        form.setError('contact_email', 'Email wajib diisi.');
        hasClientErrors = true;
    }
    if (!isEditMode.value && !form.password) {
        form.setError('password', 'Password wajib diisi untuk klien baru.');
        hasClientErrors = true;
    }
    if (form.password && form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Konfirmasi password tidak cocok.');
        hasClientErrors = true;
    }

    // 3. Jika ada error, hentikan proses submit
    if (hasClientErrors) return;

    // 4. Jika lolos, kirim data ke server
    const submissionRoute = isEditMode.value
        ? route('admin.clients.update', form.id)
        : route('admin.clients.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => console.error('Client form submission error:', errors),
    });
};

const confirmDeleteClient = (clientItem) => {
    clientToDelete.value = clientItem;
    showDeleteConfirmModal.value = true;
};

const deleteClient = () => {
    if (!clientToDelete.value) return;

    const currentClientData = clientList.value.data;
    const currentPage = clientList.value.current_page;
    const wasLastItemOnPage = currentClientData.length === 1 && currentPage > 1;

    router.delete(route('admin.clients.destroy', clientToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            clientToDelete.value = null;
            if (wasLastItemOnPage) {
                router.get(route('admin.clients.index', { page: currentPage - 1, search: searchQuery.value }), {}, {
                    preserveState: false,
                });
            }
        },
    });
};

</script>

<template>
    <Head title="Manajemen Klien" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Klien</h2>
        </template>
        
        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex-grow">
                                <TextInput type="text" v-model="searchQuery" placeholder="Cari perusahaan, kontak, email..." class="w-full md:max-w-md" />
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.manage_clients">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" /> Tambah Klien Baru
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perusahaan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Narahubung</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email (Login)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Terdaftar</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!clientList.data || clientList.data.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data klien.</td>
                                    </tr>
                                    <tr v-else v-for="item in clientList.data" :key="item.id">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ item.company_name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ item.contact_person }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ item.email }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ item.created_at_formatted }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-medium">
                                            <button @click="openEditModal(item)" class="p-1 mr-2 text-indigo-600 hover:text-indigo-900" title="Edit Klien"><PencilIcon class="h-5 w-5" /></button>
                                            <button @click="confirmDeleteClient(item)" class="p-1 text-red-600 hover:text-red-900" title="Hapus Klien"><TrashIcon class="h-5 w-5" /></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showClientModal" @close="closeModal" maxWidth="xl">
            <form @submit.prevent="submitClientForm" class="p-6" novalidate>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-3 mb-6">
                    {{ isEditMode ? 'Edit Klien' : 'Tambah Klien Baru' }}
                </h2>
                <div class="space-y-6">
                    <div>
                        <InputLabel for="company_name" value="Nama Perusahaan" required />
                        <TextInput id="company_name" v-model="form.company_name" type="text" class="mt-1 block w-full" placeholder="Cth: PT. Videotron Jaya" />
                        <InputError :message="form.errors.company_name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="contact_person" value="Nama Narahubung" required />
                        <TextInput id="contact_person" v-model="form.contact_person" type="text" class="mt-1 block w-full" placeholder="Cth: Budi Santoso" />
                        <InputError :message="form.errors.contact_person" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="contact_email" value="Email (Untuk Login)" required />
                        <TextInput id="contact_email" v-model="form.contact_email" type="email" class="mt-1 block w-full" placeholder="Cth: kontak@videotronjaya.com" />
                        <InputError :message="form.errors.contact_email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="password" :value="isEditMode ? 'Password Baru (Opsional)' : 'Password'" :required="!isEditMode" />
                        <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" :placeholder="isEditMode ? 'Isi jika ingin ganti password' : 'Minimal 8 karakter'" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                    <div v-if="form.password">
                        <InputLabel for="password_confirmation" value="Konfirmasi Password" required />
                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" placeholder="Ulangi password" />
                        <InputError :message="form.errors.password_confirmation" class="mt-2" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 border-t pt-6">
                    <SecondaryButton @click="closeModal" type="button">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                        {{ isEditMode ? 'Update Klien' : 'Simpan Klien' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Konfirmasi Hapus Klien</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Yakin ingin menghapus klien "{{ clientToDelete?.company_name }}"? Aksi ini akan ditandai sebagai 'dihapus' (soft delete).
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false">Batal</SecondaryButton>
                    <DangerButton @click="deleteClient" :class="{ 'opacity-25': router.processing }" :disabled="router.processing">
                        Ya, Hapus
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>