<template>
  <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5 relative">
    <h2 class="text-lg font-semibold text-gray-900">Place Limit Order</h2>

    <div class="space-y-1">
      <label class="text-sm font-medium text-gray-600">Symbol</label>
      <select
        v-model="symbol"
        :disabled="loading"
        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
      >
        <option value="BTC">BTC</option>
        <option value="ETH">ETH</option>
      </select>
    </div>

    <div class="space-y-1">
      <label class="text-sm font-medium text-gray-600">Side</label>
      <select
        v-model="side"
        :disabled="loading"
        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
      >
        <option value="buy">Buy</option>
        <option value="sell">Sell</option>
      </select>
    </div>

    <div class="space-y-1">
      <label class="text-sm font-medium text-gray-600">Price</label>
      <input
        type="number"
        v-model.number="price"
        :disabled="loading"
        placeholder="Enter price"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
      />
    </div>

    <div class="space-y-1">
      <label class="text-sm font-medium text-gray-600">Amount</label>
      <input
        type="number"
        v-model.number="amount"
        :disabled="loading"
        placeholder="Enter amount"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
      />
    </div>

    <button
      @click="submitOrder"
      :disabled="loading"
      class="flex w-full items-center justify-center gap-2 rounded-lg
             bg-indigo-600 py-2.5 text-sm font-semibold text-white transition
             hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-indigo-400"
    >
      <svg
        v-if="loading"
        class="h-4 w-4 animate-spin text-white"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
      </svg>
      <span>{{ loading ? 'Placing order...' : 'Place Order' }}</span>
    </button>

    <!-- Toast Container -->
    <div class="fixed top-6 right-6 flex flex-col space-y-2 z-50">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['px-4 py-2 rounded shadow text-sm font-medium flex items-center gap-2',
                 toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white']"
      >
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../axios';

const emit = defineEmits(['orderPlaced']);

const symbol = ref('BTC');
const side = ref('buy');
const price = ref(null);
const amount = ref(null);

const loading = ref(false);

const toasts = ref([]);
let toastId = 0;

const showToast = (message, type = 'success', duration = 3000) => {
  const id = toastId++;
  toasts.value.push({ id, message, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, duration);
};

const submitOrder = async () => {
  if (loading.value) return;

  loading.value = true;

  try {
    const res = await api.post('/orders', {
      symbol: symbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    });

    showToast('Order placed successfully', 'success');
    emit('orderPlaced', res.data);

    price.value = null;
    amount.value = null;
  } catch (err) {
    showToast(err.response?.data?.message || 'Failed to place order', 'error');
  } finally {
    loading.value = false;
  }
};
</script>
