<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  status?: string;
}>();

const form = useForm({});

const submit = () => {
  form.post('/email/verification-notification');
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
  <Head title="Email Verification — LaundryHub" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6 selection:bg-sky-500 selection:text-slate-950">
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
        <h2 class="text-xl font-bold text-slate-200">Verify your Email Address</h2>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 space-y-6 shadow-2xl">
        <p class="text-xs text-slate-300 leading-relaxed">
          Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </p>

        <div v-if="verificationLinkSent" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
          A new verification link has been sent to the email address you provided during registration.
        </div>

        <form @submit.prevent="submit" class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full sm:w-auto px-5 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:opacity-95 transition-all"
          >
            Resend Verification Email
          </button>

          <Link
            href="/logout"
            method="post"
            as="button"
            class="text-xs font-semibold text-rose-400 hover:underline"
          >
            Log Out
          </Link>
        </form>
      </div>
    </div>
  </div>
</template>
