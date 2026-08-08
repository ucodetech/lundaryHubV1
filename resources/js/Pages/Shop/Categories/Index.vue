<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { confirmDialog } from '@/Utils/swal';
import { UserRole } from '@/Enums/UserRole';

const props = defineProps<{
  categories: Array<any>;
  masterCategories?: Array<any>;
  shop: any;
}>();

const page = usePage();
const isShopOwner = computed(() => page.props.auth?.user?.role === UserRole.SHOP_OWNER);
const isSuperAdmin = computed(() => page.props.auth?.user?.role === UserRole.SUPER_ADMIN);

const iconPresets = [
  { icon: '👕', label: 'Shirts & Tops' },
  { icon: '👔', label: 'Formal / Suit' },
  { icon: '👖', label: 'Trousers / Jeans' },
  { icon: '👗', label: 'Dresses & Gowns' },
  { icon: '🧥', label: 'Coats & Jackets' },
  { icon: '🥋', label: 'Native Wear' },
  { icon: '🛏️', label: 'Bedding & Duvet' },
  { icon: '🧦', label: 'Socks & Delicates' },
  { icon: '👠', label: 'Footwear' },
  { icon: '🧺', label: 'General Laundry' },
  { icon: '🏷️', label: 'Misc Items' },
];

const showAddModal = ref(false);
const showEditModal = ref(false);
const isCreatingMaster = ref(false);
const editingCategory = ref<any | null>(null);

const form = useForm({
  name: '',
  icon: '👕',
  sort_order: 0,
  is_master: false,
});

const editForm = useForm({
  name: '',
  icon: '',
  sort_order: 0,
});

const openAddModal = (forMaster = false) => {
  isCreatingMaster.value = forMaster;
  form.is_master = forMaster;
  form.name = '';
  form.icon = '👕';
  form.sort_order = 0;
  showAddModal.value = true;
};

const submit = () => {
  form.post('/shop-admin/categories', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      showAddModal.value = false;
    },
  });
};

const openEditModal = (cat: any) => {
  editingCategory.value = cat;
  editForm.name = cat.name;
  editForm.icon = cat.icon || '👕';
  editForm.sort_order = cat.sort_order || 0;
  showEditModal.value = true;
};

const submitEdit = () => {
  if (!editingCategory.value) return;
  editForm.put(`/shop-admin/categories/${editingCategory.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      editingCategory.value = null;
    },
  });
};

const deleteCategory = async (cat: any, isMaster = false) => {
  const title = isMaster ? `Delete Master Template '${cat.name}'?` : `Delete '${cat.name}'?`;
  const message = isMaster
    ? 'This will permanently remove this default template from the platform catalog.'
    : 'Are you sure you want to delete this category from your shop catalog?';

  const confirmed = await confirmDialog(title, message, 'warning');

  if (confirmed) {
    router.delete(`/shop-admin/categories/${cat.id}`, {
      preserveScroll: true,
    });
  }
};

const cloneCategory = (categoryId: number) => {
  useForm({}).post(`/shop-admin/categories/${categoryId}/clone`, {
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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Item Categories</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Manage custom item categories or clone from platform master templates</p>
        </div>

        <button
          v-if="isShopOwner || isSuperAdmin"
          @click="openAddModal(false)"
          class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform"
        >
          + Create Custom Category
        </button>
      </div>

      <!-- Section 1: Active Shop Categories (If shop exists or is shop owner) -->
      <div v-if="shop || isShopOwner" class="space-y-3">
        <h2 class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
          <span>🏪 My Shop Categories</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">{{ categories.length }}</span>
        </h2>

        <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
          <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-white dark:bg-slate-900/80 text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-700/60">
              <tr>
                <th class="py-3.5 px-6">Order</th>
                <th class="py-3.5 px-6">Category Name</th>
                <th class="py-3.5 px-6">Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700/40 text-xs">
              <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-800/40 transition-colors">
                <td class="py-4 px-6 text-gray-500 dark:text-slate-400 font-mono">{{ cat.sort_order }}</td>
                <td class="py-4 px-6 font-semibold text-gray-800 dark:text-slate-200">
                  <span v-if="cat.icon" class="mr-2 text-base">{{ cat.icon }}</span>
                  <span>{{ cat.name }}</span>
                </td>
                <td class="py-4 px-6">
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Active
                  </span>
                </td>
                <td class="py-4 px-6 text-right space-x-2">
                  <button
                    @click="openEditModal(cat)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteCategory(cat, false)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="categories.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-500 dark:text-slate-400 text-xs">
                  No categories in your shop catalog.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 2: Platform Master Templates Catalog -->
      <div v-if="masterCategories && masterCategories.length > 0" class="space-y-3 pt-4 border-t border-gray-200 dark:border-slate-800/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-purple-300 flex items-center gap-2">
              <span>📋 Platform Master Category Templates</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20">Admin Templates</span>
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Platform default category template catalog</p>
          </div>

          <button
            v-if="isSuperAdmin"
            @click="openAddModal(true)"
            class="px-3 py-2 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold hover:bg-purple-500/30 transition-all"
          >
            + Create Master Category Template
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="masterCat in masterCategories"
            :key="masterCat.id"
            class="bg-white dark:bg-slate-900/80 border border-gray-200 dark:border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-lg transition-all group"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-lg">
                {{ masterCat.icon || '🏷️' }}
              </div>
              <div>
                <p class="text-xs font-bold text-gray-800 dark:text-slate-200 group-hover:text-purple-300 transition-colors">{{ masterCat.name }}</p>
                <span class="text-[10px] text-gray-500 dark:text-slate-500">Order {{ masterCat.sort_order }}</span>
              </div>
            </div>

            <!-- Actions: Clone for Shop Owner, Edit & Delete for Super Admin -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button
                v-if="isShopOwner"
                @click="cloneCategory(masterCat.id)"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition-all"
              >
                📋 Clone to Shop
              </button>

              <template v-if="isSuperAdmin">
                <button
                  @click="openEditModal(masterCat)"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                >
                  Edit
                </button>
                <button
                  @click="deleteCategory(masterCat, true)"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                >
                  Delete
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Category Modal -->
      <div v-if="showAddModal" class="fixed inset-0 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
            {{ isCreatingMaster ? 'Add Platform Master Category Template' : 'Add New Category' }}
          </h3>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Category Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="e.g., Shirts, Native Wear, Duvet"
                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Select Garment Icon</label>
              <div class="grid grid-cols-6 gap-2 mb-2">
                <button
                  v-for="item in iconPresets"
                  :key="item.icon"
                  type="button"
                  @click="form.icon = item.icon"
                  :title="item.label"
                  class="h-10 rounded-xl text-lg flex items-center justify-center transition-all border"
                  :class="form.icon === item.icon ? 'bg-sky-500/20 border-sky-500 text-sky-400 scale-105 shadow-md shadow-sky-500/20' : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 hover:bg-slate-800 hover:border-slate-700'"
                >
                  {{ item.icon }}
                </button>
              </div>

              <input
                v-model="form.icon"
                type="text"
                placeholder="Or type custom emoji..."
                class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Display Sort Order</label>
              <input
                v-model="form.sort_order"
                type="number"
                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showAddModal = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-500 dark:text-slate-400 hover:text-slate-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-5 py-2 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
              >
                Save Category
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Category Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">Edit Category</h3>
          <form @submit.prevent="submitEdit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Category Name *</label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Select Garment Icon</label>
              <div class="grid grid-cols-6 gap-2 mb-2">
                <button
                  v-for="item in iconPresets"
                  :key="item.icon"
                  type="button"
                  @click="editForm.icon = item.icon"
                  :title="item.label"
                  class="h-10 rounded-xl text-lg flex items-center justify-center transition-all border"
                  :class="editForm.icon === item.icon ? 'bg-sky-500/20 border-sky-500 text-sky-400 scale-105 shadow-md shadow-sky-500/20' : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 hover:bg-slate-800 hover:border-slate-700'"
                >
                  {{ item.icon }}
                </button>
              </div>

              <input
                v-model="editForm.icon"
                type="text"
                placeholder="Or type custom emoji..."
                class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Display Sort Order</label>
              <input
                v-model="editForm.sort_order"
                type="number"
                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-500 dark:text-slate-400 hover:text-slate-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="px-5 py-2 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
              >
                Update Category
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
