<template>
  <div>
    <Navbar v-if="user"  />
  <router-view />
  </div>
</template>

<script setup>
import Navbar from './components/Navbar.vue';
import { onMounted } from 'vue';
import { useAuth, user } from './composables/useAuth'; 
const { fetchUser } = useAuth(); 

onMounted(() => {
  fetchUser(); 
});
</script>
