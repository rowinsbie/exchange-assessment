<template>
  <div class="p-6">
    <div class="mx-auto max-w-7xl grid grid-cols-1 gap-8 lg:grid-cols-12">
      <!-- Limit Order Form -->
      <div class="lg:col-span-4 flex justify-center lg:justify-start">
        <LimitOrderForm @order-placed="refreshOrders" />
      </div>

      <!-- Orders & Wallet -->
      <div class="lg:col-span-8 relative">
        <!-- Orders Loading Spinner -->
        <div
          v-if="loadingOrders"
          class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-sm rounded-xl"
        >
          <svg class="h-6 w-6 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
          </svg>
        </div>

        <OrdersWallet
          :symbol="selectedSymbol"
          :orders="orders"
          :balances="balances"
          @symbol-changed="changeSymbol"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import LimitOrderForm from '../components/LimitOrderForm.vue';
import OrdersWallet from '../components/OrdersWallet.vue';
import { useAuth, user } from '../composables/useAuth';
import api from '../axios';
import { echo } from '../echo';


const selectedSymbol = ref('BTC');
const orders = ref([]);
const balances = ref({ usd_balance: 0, assets: [] });
const loadingOrders = ref(false);

const { fetchUser } = useAuth();


const fetchBalances = async () => {
  await fetchUser();
  if (!user.value) return;

  balances.value = {
    usd_balance: user.value.usd_balance || 0,
    assets: Array.isArray(user.value.assets) ? [...user.value.assets] : [],
  };
};


const fetchOrders = async () => {
  try {
    const res = await api.get('/orders', { params: { symbol: selectedSymbol.value } });
    orders.value = Array.isArray(res.data.data) ? [...res.data.data] : [];
  } catch (err) {
    console.error('Failed to fetch orders', err);
  }
};


const refreshOrders = async () => {
  await fetchBalances();
  await fetchOrders();
};

let currentUserId = null;

const subscribeToPrivate = (userId) => {
  if (!userId || currentUserId === userId) return;

  if (currentUserId) {
    window.echo.leave(`user.${currentUserId}`);
  }

  currentUserId = userId;

  window.echo.private(`user.${userId}`)
    .listen('.order.matched', (event) => {
      orders.value = [{ ...event.order }, ...orders.value.filter(o => o.id !== event.order.id)];

      balances.value = {
        usd_balance: event.balances.usd_balance,
        assets: [...event.balances.assets],
      };
      console.log("test");
      orders.value = orders.value.filter(o => o.id !== event.order.id || o.user_id === userId);
    });
};

let currentOrderBookSymbol = null;

const subscribeToOrderBook = (symbol) => {
  if (!symbol) return;

  if (currentOrderBookSymbol && currentOrderBookSymbol !== symbol) {
    window.echo.leaveChannel(`orderbook.${currentOrderBookSymbol}`);
  }

  currentOrderBookSymbol = symbol;

  window.echo.channel(`orderbook.${symbol}`)
    .listen('.orderbook.updated', (event) => {
      if (event.action === 'created' && event.order.status === 1) {
        orders.value = [{ ...event.order }, ...orders.value.filter(o => o.id !== event.order.id)];
      }
      if (event.action === 'removed') {
        orders.value = orders.value.filter(o => o.id !== event.order.id);
      }
    });
};


const changeSymbol = async (symbol) => {
  selectedSymbol.value = symbol;
};


watch(selectedSymbol, async (symbol, oldSymbol) => {
  loadingOrders.value = true;

  await fetchOrders();

  if (oldSymbol) {
    window.echo.leaveChannel(`orderbook.${oldSymbol}`);
  }

  subscribeToOrderBook(symbol);

  await new Promise(resolve => setTimeout(resolve, 150));

  loadingOrders.value = false;
});

watch(
  () => user.value,
  (u) => {
    if (u && u.id) subscribeToPrivate(u.id);
  },
  { immediate: true }
);


onMounted(async () => {
  await refreshOrders();

  if (user.value && user.value.id) subscribeToPrivate(user.value.id);
  subscribeToOrderBook(selectedSymbol.value);
});

onBeforeUnmount(() => {
  if (currentUserId) {
    window.echo.leave(`private-user.${currentUserId}`);
    currentUserId = null;
  }

  if (currentOrderBookSymbol) {
    window.echo.leaveChannel(`orderbook.${currentOrderBookSymbol}`);
    currentOrderBookSymbol = null;
  }
});
</script>
