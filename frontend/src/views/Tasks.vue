<template>
  <div class="tasks-container">
    <header class="tasks-header">
      <h1>Мои задачи</h1>
      <button @click="handleLogout" class="btn btn-danger">Выйти</button>
    </header>

    <div class="card">
      <h3>{{ editingTask ? 'Редактировать' : 'Добавить' }} задачу</h3>
      <form @submit.prevent="handleSubmit">
        <div class="form-row">
          <input v-model="form.title" type="text" placeholder="Название" required class="input flex-2" />
          <input v-model="form.description" type="text" placeholder="Описание" class="input flex-3" />
          <button type="submit" :disabled="loading" class="btn btn-primary">
            {{ editingTask ? 'Сохранить' : 'Добавить' }}
          </button>
          <button v-if="editingTask" type="button" @click="cancelEdit" class="btn btn-secondary">Отмена</button>
        </div>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
      </form>
    </div>

    <div class="tasks-list">
      <div v-if="tasks.length === 0" class="empty-state">Задач пока нет</div>
      <div v-for="task in tasks" :key="task.id" class="card task-card">
        <div class="task-header">
          <h3 @click="startEdit(task)" class="task-title">{{ task.title }}</h3>
          <button @click="toggleStatus(task)" :class="['btn', 'btn-sm', task.status === 'pending' ? 'btn-success' : 'btn-secondary']">
            {{ task.status === 'pending' ? 'Активна' : 'Выполнена' }}
          </button>
        </div>
        <!-- ✅ ИСПРАВЛЕНО: обрезка до 50 символов (ТЗ) -->
        <p class="task-desc">{{ task.description ? truncate(task.description, 50) : 'Нет описания' }}</p>
        <div class="task-footer">
          <span :class="task.reminder_at ? 'text-primary' : 'text-muted'">
            {{ task.reminder_at ? `📅 ${formatDate(task.reminder_at)}` : 'Без напоминания' }}
          </span>
          <div class="actions">
            <button @click="openReminderModal(task)" class="btn btn-sm btn-info">
              {{ task.reminder_at ? 'Изменить' : 'Напомнить' }}
            </button>
            <button @click="handleDelete(task)" class="btn btn-sm btn-danger">Удалить</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="modal.task" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content card">
        <h3>Напоминание: {{ modal.task.title }}</h3>
        <input v-model="modal.datetime" type="datetime-local" class="input" style="width:100%; margin: 15px 0;" />
        <div v-if="modal.error" class="alert alert-danger">{{ modal.error }}</div>
        <div class="actions">
          <button @click="handleSetReminder" class="btn btn-success">Сохранить</button>
          <button v-if="modal.task.reminder_at" @click="handleDeleteReminder" class="btn btn-danger">Удалить</button>
          <button @click="closeModal" class="btn btn-secondary">Отмена</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../axios'

const router = useRouter()
const authStore = useAuthStore()

const tasks = ref([])
const loading = ref(false)
const error = ref('')
const editingTask = ref(null)
const form = ref({ title: '', description: '' })
const modal = ref({ task: null, datetime: '', error: '' })

const loadTasks = async () => {
  try {
    const res = await api.get('/tasks')
    tasks.value = res.data.data
  } catch (err) {
    console.error('Ошибка загрузки задач:', err)
  }
}

const handleSubmit = async () => {
  error.value = ''
  loading.value = true
  try {
    if (editingTask.value) {
      await api.put(`/tasks/${editingTask.value.id}`, { ...form.value, status: editingTask.value.status })
    } else {
      await api.post('/tasks', form.value)
    }
    form.value = { title: '', description: '' }
    editingTask.value = null
    await loadTasks()
  } catch (err) {
    error.value = Object.values(err.response?.data?.errors || {}).flat().join(', ') || 'Ошибка'
  } finally { loading.value = false }
}

const startEdit = (task) => {
  editingTask.value = { ...task }
  form.value = { title: task.title, description: task.description }
}

const cancelEdit = () => {
  editingTask.value = null
  form.value = { title: '', description: '' }
}

const toggleStatus = async (task) => {
  try {
    await api.put(`/tasks/${task.id}`, { 
      title: task.title, 
      description: task.description, 
      status: task.status === 'pending' ? 'completed' : 'pending' 
    })
    await loadTasks()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка изменения статуса')
  }
}

const handleDelete = async (task) => {
  if (!confirm('Удалить задачу?')) return
  try {
    await api.delete(`/tasks/${task.id}`)
    await loadTasks()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления')
  }
}

const openReminderModal = (task) => {
  modal.value = { task, datetime: task.reminder_at ? formatDatetimeLocal(task.reminder_at) : '', error: '' }
}

const closeModal = () => { modal.value = { task: null, datetime: '', error: '' } }

const handleSetReminder = async () => {
  modal.value.error = ''
  try {
    await api.post(`/tasks/${modal.value.task.id}/reminder`, { reminder_at: modal.value.datetime })
    closeModal()
    await loadTasks()
  } catch (err) {
    modal.value.error = err.response?.status === 409 ? 'Лимит 3 напоминания' : 'Ошибка'
  }
}

const handleDeleteReminder = async () => {
  try {
    await api.delete(`/tasks/${modal.value.task.id}/reminder`)
    closeModal()
    await loadTasks()
  } catch (err) {
    modal.value.error = err.response?.data?.message || 'Ошибка удаления напоминания'
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const truncate = (text, length) => text.length <= length ? text : text.substring(0, length) + '...'

const formatDate = (d) => new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
const formatDatetimeLocal = (d) => {
  const date = new Date(d)
  return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}T${String(date.getHours()).padStart(2,'0')}:${String(date.getMinutes()).padStart(2,'0')}`
}

onMounted(loadTasks)
</script>

<style scoped>
.tasks-container { max-width: 900px; margin: 0 auto; padding: 20px; font-family: sans-serif; }
.tasks-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px; }
.form-row { display: flex; gap: 10px; margin-bottom: 10px; }
.input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.flex-2 { flex: 2; } .flex-3 { flex: 3; }
.btn { padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; color: #fff; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-primary { background: #4a90e2; } .btn-success { background: #28a745; } .btn-danger { background: #dc3545; }
.btn-secondary { background: #6c757d; } .btn-info { background: #17a2b8; }
.btn:disabled { background: #ccc; cursor: not-allowed; }
.alert { padding: 10px; border-radius: 4px; margin-top: 10px; }
.alert-danger { background: #fee; color: #c33; }
.task-card { display: flex; flex-direction: column; }
.task-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.task-title { cursor: pointer; margin: 0; } .task-title:hover { color: #4a90e2; }
.task-desc { color: #666; margin-bottom: 15px; flex-grow: 1; }
.task-footer { display: flex; justify-content: space-between; align-items: center; }
.actions { display: flex; gap: 10px; }
.text-primary { color: #4a90e2; font-weight: 500; } .text-muted { color: #999; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { width: 100%; max-width: 400px; }
.empty-state { text-align: center; padding: 40px; color: #999; }
</style>