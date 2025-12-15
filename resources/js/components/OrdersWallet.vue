<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-6 relative">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Wallet & Orders</h2>

      <select
        v-model="localSymbol"
        @change="onSymbolChange"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100"
      >
        <option value="BTC">BTC</option>
        <option value="ETH">ETH</option>
      </select>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">USD Balance</span>
        <span class="text-lg font-semibold text-gray-900">
          {{ localBalances.usd_balance }}
        </span>
      </div>

      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div
          v-for="asset in localBalances.assets"
          :key="asset.symbol"
          class="flex items-center justify-between rounded-md border bg-white px-3 py-2"
        >
          <span class="font-medium text-gray-700">{{ asset.symbol }}</span>
          <div class="text-right text-sm">
            <div class="text-gray-900">{{ asset.amount }}</div>
            <div class="text-xs text-gray-500">Locked: {{ asset.locked_amount }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-3">
      <h3 class="text-sm font-semibold text-gray-700">
        Orders ({{ localSymbol }})
      </h3>

      <div class="overflow-hidden rounded-lg border">
        <ul class="divide-y">
          <li
            v-for="order in localOrders"
            :key="order.id"
            class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50"
          >
            <div class="flex items-center gap-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="order.side === 'buy' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ order.side.toUpperCase() }}
              </span>

              <span class="text-gray-700">{{ order.amount }} {{ order.symbol }} @ {{ order.price }}</span>
            </div>

            <span
              class="text-xs font-medium"
              :class="order.status === 'filled' ? 'text-green-600' : 'text-yellow-600'"
            >
              {{ order.status }}
            </span>
          </li>

          <li v-if="!localOrders.length" class="px-4 py-6 text-center text-sm text-gray-500">
            No orders found
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  orders: Array,
  balances: Object,
  symbol: String,
});

const emit = defineEmits(['symbol-changed']);

const localSymbol = ref(props.symbol);
const localOrders = ref([...props.orders]);
const localBalances = ref({ ...props.balances });


watch(() => props.orders, (newOrders) => {
  localOrders.value = [...newOrders];
});

watch(() => props.balances, (newBalances) => {
  localBalances.value = { ...newBalances };
});

const onSymbolChange = async () => {
  emit('symbol-changed', localSymbol.value);
  await new Promise(resolve => setTimeout(resolve, 200));
};
</script>
