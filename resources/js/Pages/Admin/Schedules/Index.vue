<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from 'axios';

// Komponen Modal dan Form
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';


const page = usePage();
const playlists = computed(() => page.props.playlists);
const videotrons = computed(() => page.props.videotrons);

// State untuk modal
const showScheduleModal = ref(false);
const form = useForm({
    id: null,
    playlist_id: '',
    videotron_id: '',
    start_time: '',
    end_time: '',
});

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek', // Tampilan awal per minggu
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true, // bisa di drag-n-drop
    selectable: true, // bisa memilih slot waktu
    events: (fetchInfo, successCallback, failureCallback) => {
        axios.get(route('admin.schedules.index', {
            start: fetchInfo.startStr,
            end: fetchInfo.endStr
        }))
        .then(response => successCallback(response.data))
        .catch(error => failureCallback(error));
    },
    select: (selectionInfo) => {
        form.reset();
        form.clearErrors();
        form.start_time = selectionInfo.startStr;
        form.end_time = selectionInfo.endStr;
        showScheduleModal.value = true;
    },
    // eventClick, eventDrop bisa ditambahkan di sini untuk edit/delete
};

const submitSchedule = () => {
    form.post(route('admin.schedules.store'), {
        onSuccess: () => {
            showScheduleModal.value = false;
            // Di dunia nyata, kita akan panggil refetchEvents() pada kalender
        },
    });
};

</script>

<template>
    <Head title="Penjadwalan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Kalender Penjadwalan</h2>
        </template>
        
        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white p-4 rounded-lg shadow">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>

        <Modal :show="showScheduleModal" @close="showScheduleModal = false">
            <form @submit.prevent="submitSchedule" class="p-6">
                <h2 class="text-lg font-medium mb-4">Buat Jadwal Baru</h2>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="playlist_id" value="Pilih Playlist" />
                        <select v-model="form.playlist_id" id="playlist_id" class="w-full mt-1 border-gray-300 rounded-md">
                            <option v-for="p in playlists" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.playlist_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="videotron_id" value="Pilih Videotron" />
                        <select v-model="form.videotron_id" id="videotron_id" class="w-full mt-1 border-gray-300 rounded-md">
                            <option v-for="v in videotrons" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <InputError :message="form.errors.videotron_id" class="mt-1" />
                    </div>
                    </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showScheduleModal = false">Batal</SecondaryButton>
                    <PrimaryButton class="ml-3" :disabled="form.processing">Simpan Jadwal</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>