import { createRouter, createWebHistory } from 'vue-router'
import Login from './views/Login.vue'
import Register from './views/Register.vue'
import Tasks from './views/Tasks.vue'

const routes = [
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/tasks', component: Tasks },
  { path: '/', redirect: '/tasks' },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
