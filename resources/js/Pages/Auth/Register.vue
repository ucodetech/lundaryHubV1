<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GlobalLoader from '@/Components/GlobalLoader.vue';

const props = defineProps<{
  defaultRole?: string;
  initialReferralCode?: string;
}>();

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: props.defaultRole ?? 'customer',
  referral_code: props.initialReferralCode ?? '',
  password: '',
  password_confirmation: '',
});

function selectRole(newRole: string) {
  form.role = newRole;
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    url.searchParams.set('role', newRole);
    window.history.replaceState({}, '', url.toString());
  }
}

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Create Account — LaundryHub" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6 selection:bg-sky-500 selection:text-slate-950 relative">
    <GlobalLoader />

    <div class="w-full max-w-lg space-y-6">
      <div class="text-center space-y-2">
        <Link href="/" class="inline-flex items-center gap-2">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-sky-500/20">
            L
          </div>
          <span class="font-extrabold text-2xl bg-gradient-to-r from-sky-400 via-cyan-300 to-teal-300 bg-clip-text text-transparent">
            LaundryHub
          </span>
        </Link>
        <h2 class="text-xl font-bold text-slate-200">Join the Laundry Platform</h2>
      </div>

      <form @submit.prevent="submit" class="bg-slate-900 border border-slate-800 rounded-3xl p-8 space-y-4 shadow-2xl">
        <!-- Role Selection -->
        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">I am registering as:</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="selectRole('customer')"
              class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center"
              :class="form.role === 'customer' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-950 border-slate-800 text-slate-400'"
            >
              🙋 Customer
            </button>
            <button
              type="button"
              @click="selectRole('shop_owner')"
              class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center"
              :class="form.role === 'shop_owner' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-950 border-slate-800 text-slate-400'"
            >
              🏪 Dry Cleaner
            </button>
            <button
              type="button"
              @click="selectRole('rider')"
              class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center"
              :class="form.role === 'rider' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-950 border-slate-800 text-slate-400'"
            >
              🛵 Rider
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">First Name *</label>
            <input
              v-model="form.first_name"
              type="text"
              required
              placeholder="First name"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
            <p v-if="form.errors.first_name" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.first_name }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Last Name *</label>
            <input
              v-model="form.last_name"
              type="text"
              required
              placeholder="Last name"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
            <p v-if="form.errors.last_name" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.last_name }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Email Address *</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="name@domain.com"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
            <p v-if="form.errors.email" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Phone Number *</label>
            <input
              v-model="form.phone"
              type="text"
              required
              placeholder="+2348000000000"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
            <p v-if="form.errors.phone" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.phone }}</p>
          </div>
        </div>

        <!-- Referral Phone / Code Input -->
        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">
            Referral Phone Number / Code <span class="text-slate-500 font-normal text-[11px] lowercase">(optional)</span>
          </label>
          <input
            v-model="form.referral_code"
            type="text"
            placeholder="e.g. 08012345678"
            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm font-mono focus:border-sky-500 focus:ring-0"
          />
          <p v-if="form.errors.referral_code" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.referral_code }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Password *</label>
            <input
              v-model="form.password"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
            <p v-if="form.errors.password" class="mt-1 text-[11px] text-rose-400 font-semibold">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Confirm Password *</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:opacity-95 transition-all flex items-center justify-center gap-2"
        >
          <span v-if="form.processing" class="w-4 h-4 rounded-full border-2 border-slate-950/20 border-t-slate-950 animate-spin"></span>
          <span>Create {{ form.role === 'shop_owner' ? 'Dry Cleaner' : form.role === 'rider' ? 'Rider' : 'Customer' }} Account</span>
        </button>

        <div class="text-center pt-2">
          <p class="text-xs text-slate-400">
            Already have an account?
            <Link href="/login" class="text-sky-400 font-semibold hover:underline">Sign In</Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>
