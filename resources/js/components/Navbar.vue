<template>
  <nav class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
    <!-- App Title / Logo -->
    <div class="text-lg font-bold">
      Virgosoft Assessment | Seiki Rowins Bie
    </div>

    <div class="flex items-center space-x-4">
      <span v-if="user">{{ user.email }}</span>
      <button @click="handleLogout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">
        Logout
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useAuth } from '../composables/useAuth';
import { useRouter } from 'vue-router';

const { user, logout } = useAuth();
const router = useRouter();

const handleLogout = async () => {
  await logout();
  router.push('/login'); 
};
</script>
