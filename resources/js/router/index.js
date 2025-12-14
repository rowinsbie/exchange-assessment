import { createRouter, createWebHistory } from "vue-router";
import Login from "../Pages/Login.vue";
import Dashboard from "../Pages/Dashboard.vue"; 

const routes = [
  {
    path: "/login",
    name: "login",
    component: Login,
  },
  {
    path: "/dashboard",
    name: "dashboard",
    component: Dashboard,
    meta: { requiresAuth: true }, 
  },
  { path: "/", redirect: "/login" },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});


router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
  if (to.meta.requiresAuth && !token) {
    next("/login");
  } else if (to.name === "login" && token) {
    next("/dashboard");
  } else {
    next();
  }
});

export default router;
