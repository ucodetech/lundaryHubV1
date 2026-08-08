<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GlobalLoader from '@/Components/GlobalLoader.vue';

defineProps<{
  status?: string;
}>();

const form = useForm({
  login: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Sign In — LaundryHub" />

  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-slate-100 flex items-center justify-center p-6 selection:bg-sky-500 selection:text-slate-950 relative">
    <GlobalLoader />

    <div class="w-full max-w-md space-y-6">
      <div class="text-center space-y-2">
        <Link href="/" class="inline-flex items-center gap-2">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-sky-500/20">
            L
          </div>
          <span class="font-extrabold text-2xl bg-gradient-to-r from-sky-400 via-cyan-300 to-teal-300 bg-clip-text text-transparent">
            LaundryHub
          </span>
        </Link>
        <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200">Sign in to your account</h2>
      </div>

      <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-8 space-y-4 shadow-2xl">
        <div v-if="form.errors.login" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold">
          {{ form.errors.login }}
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Email Address or Phone Number</label>
          <input
            v-model="form.login"
            type="text"
            required
            placeholder="name@domain.com or +2348012345678"
            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 focus:ring-0"
          />
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:opacity-95 transition-all flex items-center justify-center gap-2"
        >
          <span v-if="form.processing" class="w-4 h-4 rounded-full border-2 border-slate-950/20 border-t-slate-950 animate-spin"></span>
          <span>Sign In</span>
        </button>

        <div class="text-center pt-2">
          <p class="text-xs text-gray-500 dark:text-slate-400">
            Don't have an account?
            <Link href="/register" class="text-sky-400 font-semibold hover:underline">Create Account</Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>
