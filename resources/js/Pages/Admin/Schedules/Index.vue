<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusIcon, PencilIcon, TrashIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    schedules: Object,
    can: Object,
});

const page = usePage();

// --- STATE UNTUK MODAL & FORM ---
const isModalOpen = ref(false);
const isEditMode = ref(false);
const showDeleteConfirmModal = ref(false);

const form = useForm({
    id: null,
    name: '',
    description: '',
});

const deleteForm = useForm({});
const scheduleToDelete = ref(null);

// --- FUNGSI-FUNGSI MODAL ---
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (schedule) => {
    isEditMode.value = true;
    form.id = schedule.id;
    form.name = schedule.name;
    form.description = schedule.description;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submit = () => {
    if (isEditMode.value) {
        form.put(route('admin.schedules.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.schedules.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDeleteSchedule = (schedule) => {
    scheduleToDelete.value = schedule;
    showDeleteConfirmModal.value = true;
};

const deleteSchedule = () => {
    deleteForm.delete(route('admin.schedules.destroy', scheduleToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            scheduleToDelete.value = null;
        }
    });
};

</script>

<template>
    <Head title="Manajemen Template Jadwal" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Manajemen Template Jadwal
            </h2>
        </template>
        
        <Toast :message="$page.props.flash?.message" :type="$page.props.flash?.type" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="flex justify-end mb-4">
                    <PrimaryButton @click="openCreateModal" v-if="can.manage_schedules">
                        <PlusIcon class="h-5 w-5 mr-2" /> Buat Template Baru
                    </PrimaryButton>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Template Jadwal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jumlah Item</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!schedules.data.length"><td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada template jadwal.</td></tr>
                                <tr v-for="schedule in schedules.data" :key="schedule.id">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ schedule.name }}</td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-300">{{ schedule.schedule_items_count }} item</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-4">
                                        <Link :href="route('admin.schedules.show', schedule.id)" class="font-semibold text-blue-600 hover:text-blue-900">
                                            Atur Jadwal
                                        </Link>
                                        <button @click="openEditModal(schedule)" class="font-semibold text-indigo-600 hover:text-indigo-900">
                                            Edit Nama
                                        </button>
                                        <button @click="confirmDeleteSchedule(schedule)" class="font-semibold text-red-600 hover:text-red-900">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    
    <Modal :show="isModalOpen" @close="closeModal">
        <form @submit.prevent="submit" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ isEditMode ? 'Edit' : 'Tambah' }} Template Jadwal
            </h2>
            <div class="mt-6 space-y-4">
                <div>
                    <InputLabel for="name" value="Nama Template" required />
                    <TextInput id="name" v-model="form.name" class="mt-1 w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="description" value="Deskripsi (Opsional)" />
                    <textarea id="description" v-model="form.description" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Simpan
                </PrimaryButton>
            </div>
        </form>
    </Modal>

    <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Konfirmasi Hapus</h2>
            <p class="mt-2 text-sm text-gray-600">
                Apakah Anda yakin ingin menghapus template "{{ scheduleToDelete?.name }}"?
            </p>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="showDeleteConfirmModal = false">Batal</SecondaryButton>
                <DangerButton @click="deleteSchedule" class="ml-3">Ya, Hapus</DangerButton>
            </div>
        </div>
    </Modal>
</template>
