import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/customer/login',
    name: 'CustomerLogin',
    component: () => import('../views/customer/auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/customer/register',
    name: 'CustomerRegister',
    component: () => import('../views/customer/auth/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue'),
      },
      {
        path: 'dashboard',
        redirect: '/'
      },
      {
        path: 'pos',
        name: 'POS',
        component: () => import('../views/POS.vue'),
      },
      {
        path: 'cart',
        name: 'Cart',
        component: () => import('../views/Cart.vue'),
      },
      {
        path: 'transactions',
        name: 'Transactions',
        component: () => import('../views/Transactions.vue'),
      },
      {
        path: 'customers',
        name: 'Customers',
        component: () => import('../views/Customers.vue'),
        meta: { roles: ['owner', 'kasir'] }
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('../views/Products.vue'),
        meta: { roles: ['owner'] }
      },
      {
        path: 'packages',
        name: 'Packages',
        component: () => import('../views/Packages.vue'),
        meta: { roles: ['owner'] }
      },
      {
        path: 'inventory',
        name: 'Inventory',
        component: () => import('../views/Inventory.vue'),
        meta: { roles: ['owner', 'gudang'] }
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('../views/Reports.vue'),
        meta: { roles: ['owner'] }
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('../views/Settings.vue'),
        meta: { roles: ['owner'] }
      },
      {
        path: 'tables',
        name: 'Tables',
        component: () => import('../views/Tables.vue'),
        meta: { roles: ['owner'] }
      },
    ]
  },
  {
    path: '/customer',
    component: () => import('../layouts/CustomerLayout.vue'),
    meta: { requiresAuth: false },
    children: [
      {
        path: 'home',
        name: 'CustomerHome',
        component: () => import('../views/customer/Home.vue'),
      },
      {
        path: 'menu',
        name: 'CustomerMenu',
        component: () => import('../views/customer/Menu.vue'),
      },
      {
        path: 'profil',
        name: 'CustomerProfile',
        alias: 'profile', // Allow /customer/profile to work too
        component: () => import('../views/customer/Profile.vue'),
      },
      {
        path: 'addresses',
        name: 'CustomerAddresses',
        component: () => import('../views/customer/Addresses.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'language-settings',
        name: 'CustomerLanguageSettings',
        component: () => import('../views/customer/LanguageSettings.vue'),
        meta: { requiresAuth: false }
      },
      {
        path: 'settings',
        name: 'CustomerSettings',
        component: () => import('../views/customer/Settings.vue'),
        meta: { requiresAuth: false }
      },
      {
        path: 'printer-settings',
        name: 'CustomerPrinterSettings',
        component: () => import('../views/customer/PrinterSettings.vue'),
        meta: { requiresAuth: false }
      },
      {
        path: 'order',
        name: 'CustomerOrder',
        component: () => import('../views/customer/Order.vue'),
        meta: { requiresAuth: false }
      },
      {
        path: 'order/:id',
        name: 'CustomerOrderDetail',
        component: () => import('../views/customer/OrderDetail.vue'),
        meta: { requiresAuth: false }
      },
      {
        path: 'cart',
        name: 'CustomerCart',
        component: () => import('../views/customer/Cart.vue'),
        meta: { requiresAuth: false }
      },
    ]
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  console.log('Router Navigation:', { to: to.path, from: from.path, meta: to.meta })
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const isStaff = ['owner', 'kasir', 'gudang'].includes(user.role)

  if (to.path === '/') {
    if (token && isStaff) {
      next()
    } else {
      next('/customer/home')
    }
    return
  }

  // Khusus halaman Cart & Order: Sekarang ditangani di level komponen untuk memberikan pilihan Login/Register
  // atau via Scan QR (ada tableCode) yang otomatis diizinkan.
  if (to.path.startsWith('/customer/cart') || to.path.startsWith('/customer/order')) {
    // Biarkan masuk, komponen akan memvalidasi status auth
    next()
    return
  }

  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.meta.guest && token) {
    if (user.role === 'customer' || user.is_customer) {
      next('/customer/home')
    } else {
      next('/')
    }
  } else if (to.meta.roles && !to.meta.roles.includes(user.role)) {
    // Allow customer to access customer routes even if role check fails initially (if strict roles are used)
    if (to.path.startsWith('/customer') && (user.role === 'customer' || user.is_customer)) {
      next()
    } else {
      next('/')
    }
  } else {
    next()
  }
})

export default router
