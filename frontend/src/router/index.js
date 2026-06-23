import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../stores/auth';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/',         name: 'books',    component: () => import('../views/BookList.vue') },
    { path: '/login',    name: 'login',    component: () => import('../views/Login.vue') },
    { path: '/register', name: 'register', component: () => import('../views/Register.vue') },
    { path: '/me',       name: 'me',       component: () => import('../views/Me.vue'),
      meta: { requiresAuth: true } },
  ],
});

router.beforeEach((to) => {
  const auth = useAuth();
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
});

export default router;
