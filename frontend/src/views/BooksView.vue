<template>
  <div class="layout-container">
    <!-- Navbar -->
    <header class="navbar">
      <div class="navbar-content">
        <div class="brand">
          <span class="brand-title">Katalog Buku</span>
          <span class="brand-subtitle">UTS Konstruksi & Evolusi Perangkat Lunak</span>
        </div>
        <div class="user-menu">
          <span class="user-name">{{ user?.name || 'Administrator' }}</span>
          <button @click="handleLogout" class="btn-outline-danger">Keluar</button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <div class="page-header">
        <div>
          <h2 class="page-title">Daftar Buku</h2>
          <p class="page-description">Kelola inventaris dan arsip data buku pada sistem</p>
        </div>
        <button @click="openCreateModal" class="btn-dark">
          + Tambah Buku
        </button>
      </div>

      <!-- Alert -->
      <div v-if="alertMessage" :class="['alert', alertType === 'error' ? 'alert-error' : 'alert-success']">
        {{ alertMessage }}
      </div>

      <!-- Table Card -->
      <div class="card">
        <div v-if="loading" class="empty-state">
          Memuat data buku...
        </div>

        <div v-else-if="books.length === 0" class="empty-state">
          Belum ada data buku yang tersimpan. Silakan klik tombol "Tambah Buku".
        </div>

        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 60px;">ID</th>
                <th>Judul Buku</th>
                <th>Penulis</th>
                <th style="width: 100px;">Tahun</th>
                <th>Deskripsi</th>
                <th style="width: 140px; text-align: right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="book in books" :key="book.id">
                <td class="text-muted">#{{ book.id }}</td>
                <td class="font-medium">{{ book.title }}</td>
                <td>{{ book.author }}</td>
                <td>
                  <span class="badge">{{ book.year }}</span>
                </td>
                <td class="text-secondary">{{ book.description || '-' }}</td>
                <td style="text-align: right;">
                  <button @click="openEditModal(book)" class="btn-text">Ubah</button>
                  <button @click="deleteBook(book.id)" class="btn-text text-danger">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Modal Form (Create / Edit) -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">{{ isEditing ? 'Ubah Data Buku' : 'Tambah Buku Baru' }}</h3>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <div v-if="formError" class="alert-error" style="margin: 1rem 1.5rem 0 1.5rem;">
          {{ formError }}
        </div>

        <form @submit.prevent="saveBook" class="modal-body">
          <div class="form-group">
            <label class="form-label">Judul Buku *</label>
            <input v-model="form.title" type="text" required class="form-input" placeholder="Masukkan judul buku" />
          </div>

          <div class="form-group">
            <label class="form-label">Nama Penulis *</label>
            <input v-model="form.author" type="text" required class="form-input" placeholder="Masukkan nama penulis" />
          </div>

          <div class="form-group">
            <label class="form-label">Tahun Terbit *</label>
            <input v-model.number="form.year" type="number" required min="1900" max="2100" class="form-input" placeholder="Contoh: 2024" />
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea v-model="form.description" rows="3" class="form-input" placeholder="Deskripsi singkat buku (opsional)"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-dark">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const user = ref(null);
const books = ref([]);
const loading = ref(false);
const submitting = ref(false);

const alertMessage = ref('');
const alertType = ref('success');
const formError = ref('');

const showModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = ref({
  title: '',
  author: '',
  year: new Date().getFullYear(),
  description: ''
});

const showAlert = (message, type = 'success') => {
  alertMessage.value = message;
  alertType.value = type;
  setTimeout(() => {
    alertMessage.value = '';
  }, 4000);
};

const fetchUser = () => {
  const savedUser = localStorage.getItem('user');
  if (savedUser) {
    try {
      user.value = JSON.parse(savedUser);
    } catch {
      user.value = null;
    }
  }
};

const fetchBooks = async () => {
  loading.value = true;
  try {
    const response = await api.get('/books');
    books.value = response.data.data;
  } catch (error) {
    showAlert('Gagal memuat daftar buku dari API.', 'error');
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  editId.value = null;
  form.value = {
    title: '',
    author: '',
    year: new Date().getFullYear(),
    description: ''
  };
  formError.value = '';
  showModal.value = true;
};

const openEditModal = (book) => {
  isEditing.value = true;
  editId.value = book.id;
  form.value = {
    title: book.title,
    author: book.author,
    year: book.year,
    description: book.description || ''
  };
  formError.value = '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveBook = async () => {
  submitting.value = true;
  formError.value = '';

  try {
    if (isEditing.value) {
      await api.put(`/books/${editId.value}`, form.value);
      showAlert('Data buku berhasil diperbarui.');
    } else {
      await api.post('/books', form.value);
      showAlert('Buku baru berhasil ditambahkan.');
    }
    closeModal();
    fetchBooks();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      formError.value = 'Validasi gagal: pastikan semua kolom bertanda * terisi dengan benar.';
    } else {
      formError.value = 'Terjadi kesalahan saat menyimpan data.';
    }
  } finally {
    submitting.value = false;
  }
};

const deleteBook = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data buku ini?')) return;

  try {
    await api.delete(`/books/${id}`);
    showAlert('Buku berhasil dihapus.');
    fetchBooks();
  } catch (error) {
    showAlert('Gagal menghapus buku.', 'error');
  }
};

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch {
    // Ignore error on logout request
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};

onMounted(() => {
  fetchUser();
  fetchBooks();
});
</script>

<style scoped>
.layout-container {
  min-height: 100vh;
  background-color: #f8fafc;
  display: flex;
  flex-direction: column;
}

.navbar {
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.875rem 2rem;
}

.navbar-content {
  max-width: 1140px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: #64748b;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  font-size: 0.875rem;
  color: #334155;
  font-weight: 500;
}

.main-content {
  max-width: 1140px;
  width: 100%;
  margin: 2rem auto;
  padding: 0 1.5rem;
  box-sizing: border-box;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.page-description {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0 0;
}

.card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
}

.data-table th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 600;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
}

.data-table tr:hover {
  background-color: #f8fafc;
}

.badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  background-color: #f1f5f9;
  color: #475569;
  border-radius: 4px;
}

.empty-state {
  padding: 3rem 1.5rem;
  text-align: center;
  color: #64748b;
  font-size: 0.875rem;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.alert-success {
  background-color: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.alert-error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
}

.btn-dark {
  background-color: #0f172a;
  color: #ffffff;
  border: none;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-dark:hover {
  background-color: #1e293b;
}

.btn-secondary {
  background-color: #ffffff;
  color: #334155;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
}

.btn-secondary:hover {
  background-color: #f8fafc;
}

.btn-outline-danger {
  background-color: transparent;
  color: #dc2626;
  border: 1px solid #fecaca;
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  border-radius: 6px;
  cursor: pointer;
}

.btn-outline-danger:hover {
  background-color: #fef2f2;
}

.btn-text {
  background: none;
  border: none;
  color: #2563eb;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
}

.btn-text:hover {
  text-decoration: underline;
}

.text-danger {
  color: #dc2626 !important;
}

.text-muted {
  color: #94a3b8;
}

.text-secondary {
  color: #64748b;
}

.font-medium {
  font-weight: 500;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 50;
}

.modal-card {
  width: 100%;
  max-width: 480px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1rem;
  color: #94a3b8;
  cursor: pointer;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #0f172a;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
}

.modal-footer {
  margin-top: 0.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
