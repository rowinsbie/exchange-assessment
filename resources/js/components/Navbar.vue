<template>
  <nav class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
    <div class="text-lg font-bold">
      Trading | Seiki Rowins Bie
    </div>

    <div class="flex items-center space-x-4">
      <span v-if="user">{{ user.email }}</span>

      <button
        @click="handleLogout"
        :disabled="loading"
        class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded
               disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        <span v-if="loading">Logging out...</span>
        <span v-else>Logout</span>
      </button>
    </div>
  </nav>
</template>


<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useRouter } from 'vue-router';

const { user, logout } = useAuth();
const router = useRouter();

const loading = ref(false);

const handleLogout = async () => {
  if (loading.value) return;

  try {
    loading.value = true;
    await logout();
    router.push('/login');
  } finally {
    loading.value = false;
  }
};
</script>
