<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2>Регистрация</h2>
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label>Имя</label>
          <input v-model="name" type="text" required placeholder="Иван Иванов" />
        </div>
        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" required placeholder="your@email.com" />
        </div>
        <div class="form-group">
          <label>Пароль</label>
          <input v-model="password" type="password" required placeholder="••••••••" />
        </div>
        <div class="form-group">
          <label>Подтверждение пароля</label>
          <input v-model="passwordConfirmation" type="password" required placeholder="••••••••" />
        </div>
        <div v-if="error" class="error-message">{{ error }}</div>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}
        </button>
      </form>
      <p class="auth-link">
        Уже есть аккаунт? <router-link to="/login">Войти</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { auth, register } from '../auth'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

onMounted(() => {
  if (auth.token) {
    router.push('/tasks')
  }
})

const handleRegister = async () => {
  error.value = ''
  loading.value = true
  try {
    await register(name.value, email.value, password.value, passwordConfirmation.value)
    router.push('/tasks')
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = err.response?.data?.message || 'Ошибка регистрации'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
}

.auth-card {
  background: white;
  padding: 40px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

h2 {
  margin-bottom: 30px;
  text-align: center;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #555;
}

input {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

input:focus {
  outline: none;
  border-color: #4a90e2;
}

button {
  width: 100%;
  padding: 12px;
  background-color: #4a90e2;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
}

button:hover:not(:disabled) {
  background-color: #357abd;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.error-message {
  background-color: #fee;
  color: #c33;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 14px;
}

.auth-link {
  margin-top: 20px;
  text-align: center;
  font-size: 14px;
  color: #666;
}

.auth-link a {
  color: #4a90e2;
  text-decoration: none;
}

.auth-link a:hover {
  text-decoration: underline;
}
</style>