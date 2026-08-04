<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { confirmDialog } from '@/Utils/swal';
import { UserRole } from '@/Enums/UserRole';

defineProps<{
  services: Array<any>;
  masterServices?: Array<any>;
  shop: any;
}>();

const page = usePage();
const isShopOwner = computed(() => page.props.auth?.user?.role === UserRole.SHOP_OWNER);
const isSuperAdmin = computed(() => page.props.auth?.user?.role === UserRole.SUPER_ADMIN);

const showAddModal = ref(false);
const showEditModal = ref(false);
const isCreatingMaster = ref(false);
const editingService = ref<any | null>(null);

const form = useForm({
  name: '',
  description: '',
  sort_order: 0,
  is_master: false,
});

const editForm = useForm({
  name: '',
  description: '',
  sort_order: 0,
});

const openAddModal = (forMaster = false) => {
  isCreatingMaster.value = forMaster;
  form.is_master = forMaster;
  form.name = '';
  form.description = '';
  form.sort_order = 0;
  showAddModal.value = true;
};

const submit = () => {
  form.post('/shop-admin/services', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      showAddModal.value = false;
    },
  });
};

const openEditModal = (srv: any) => {
  editingService.value = srv;
  editForm.name = srv.name;
  editForm.description = srv.description || '';
  editForm.sort_order = srv.sort_order || 0;
  showEditModal.value = true;
};

const submitEdit = () => {
  if (!editingService.value) return;
  editForm.put(`/shop-admin/services/${editingService.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      editingService.value = null;
    },
  });
};

const deleteService = async (srv: any, isMaster = false) => {
  const title = isMaster ? `Delete Master Template '${srv.name}'?` : `Delete '${srv.name}'?`;
  const message = isMaster
    ? 'This will permanently remove this default service treatment from the platform catalog.'
    : 'Are you sure you want to delete this service treatment from your shop catalog?';

  const confirmed = await confirmDialog(title, message, 'warning');

  if (confirmed) {
    router.delete(`/shop-admin/services/${srv.id}`, {
      preserveScroll: true,
    });
  }
};

const cloneService = (serviceId: number) => {
  useForm({}).post(`/shop-admin/services/${serviceId}/clone`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Services Catalog</h1>
          <p class="text-xs text-slate-400 mt-1">Manage custom laundry services or clone from platform master templates</p>
        </div>

        <button
          v-if="isShopOwner || isSuperAdmin"
          @click="openAddModal(false)"
          class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform"
        >
          + Create Custom Service
        </button>
      </div>

      <!-- Section 1: Active Shop Services (If shop exists or is shop owner) -->
      <div v-if="shop || isShopOwner" class="space-y-3">
        <h2 class="text-sm font-bold text-slate-200 flex items-center gap-2">
          <span>🧺 My Shop Services</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">{{ services.length }}</span>
        </h2>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
          <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
              <tr>
                <th class="py-3.5 px-6">Order</th>
                <th class="py-3.5 px-6">Service Name</th>
                <th class="py-3.5 px-6">Description</th>
                <th class="py-3.5 px-6">Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/40 text-xs">
              <tr v-for="srv in services" :key="srv.id" class="hover:bg-slate-800/40 transition-colors">
                <td class="py-4 px-6 text-slate-400 font-mono">{{ srv.sort_order }}</td>
                <td class="py-4 px-6 font-semibold text-slate-200">{{ srv.name }}</td>
                <td class="py-4 px-6 text-slate-400 text-xs">{{ srv.description ?? '—' }}</td>
                <td class="py-4 px-6">
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Active
                  </span>
                </td>
                <td class="py-4 px-6 text-right space-x-2">
                  <button
                    @click="openEditModal(srv)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteService(srv, false)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="services.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                  No services defined in your shop. Clone a master template below or click "+ Create Custom Service".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 2: Platform Master Service Templates Catalog -->
      <div v-if="masterServices && masterServices.length > 0" class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-purple-300 flex items-center gap-2">
              <span>📋 Platform Master Service Templates</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20">Admin Templates</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Click "Clone to Shop" to add a master service template to your shop catalog</p>
          </div>

          <button
            v-if="isSuperAdmin"
            @click="openAddModal(true)"
            class="px-3 py-2 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold hover:bg-purple-500/30 transition-all"
          >
            + Create Master Service Template
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="masterSvc in masterServices"
            :key="masterSvc.id"
            class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-lg transition-all group"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-lg">
                🧺
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-200 group-hover:text-purple-300 transition-colors">{{ masterSvc.name }}</p>
                <p class="text-[11px] text-slate-400 truncate">{{ masterSvc.description || 'Standard service treatment' }}</p>
              </div>
            </div>

            <!-- Actions: Clone for Shop Owner, Edit & Delete for Super Admin -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button
                v-if="isShopOwner"
                @click="cloneService(masterSvc.id)"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition-all"
              >
                📋 Clone to Shop
              </button>

              <template v-if="isSuperAdmin">
                <button
                  @click="openEditModal(masterSvc)"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                >
                  Edit
                </button>
                <button
                  @click="deleteService(masterSvc, true)"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                >
                  Delete
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Service Modal -->
      <div v-if="showAddModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-slate-100">
            {{ isCreatingMaster ? 'Add Platform Master Service Template' : 'Add New Service' }}
          </h3>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Service Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="e.g., Wash Only, Wash & Iron, Starch & Press"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Details about this service treatment..."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Display Sort Order</label>
              <input
                v-model="form.sort_order"
                type="number"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showAddModal = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-slate-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-5 py-2 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
              >
                Save Service
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Service Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-slate-100">Edit Service</h3>
          <form @submit.prevent="submitEdit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Service Name *</label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Description</label>
              <textarea
                v-model="editForm.description"
                rows="2"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Display Sort Order</label>
              <input
                v-model="editForm.sort_order"
                type="number"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-slate-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="px-5 py-2 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
              >
                Update Service
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
