<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const user = computed(() => page.props.auth?.user);

function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

const subscribeUser = async () => {
  if (!user.value || !('serviceWorker' in navigator) || !('PushManager' in window)) return;

  try {
    const registration = await navigator.serviceWorker.ready;
    
    // Check if already subscribed
    let subscription = await registration.pushManager.getSubscription();
    
    if (!subscription) {
      const publicVapidKey = (window as any).VAPID_PUBLIC_KEY;
      if (!publicVapidKey) return;
      
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
      });
    }

    // Send subscription to backend
    const key = subscription.getKey('p256dh');
    const token = subscription.getKey('auth');
    
    if (!key || !token) return;

    await axios.post('/push-subscriptions', {
      endpoint: subscription.endpoint,
      keys: {
        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key) as unknown as number[])),
        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(token) as unknown as number[]))
      }
    });

  } catch (err) {
    console.error('Failed to subscribe to Push Notifications:', err);
  }
};

const requestNotificationPermission = () => {
  if (!user.value) return;
  if (Notification.permission === 'default') {
    Notification.requestPermission().then(permission => {
      if (permission === 'granted') {
        subscribeUser();
      }
    });
  } else if (Notification.permission === 'granted') {
    subscribeUser();
  }
};

onMounted(() => {
  setTimeout(() => {
    requestNotificationPermission();
  }, 3000); // Wait 3s after load to request permission so it's not too aggressive
});
</script>

<template>
  <!-- Silent component that handles push registration in the background -->
</template>
