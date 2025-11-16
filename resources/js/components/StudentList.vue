<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">
        <i class="bi bi-people me-2"></i>Student Management
      </h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        <i class="bi bi-plus-circle me-1"></i>Add Student
      </button>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Search</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Search by name or ID..."
              @input="debouncedFetchStudents"
            />
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Class</label>
            <select v-model="filters.class" class="form-select" @change="fetchStudents">
              <option value="">All Classes</option>
              <option v-for="cls in availableClasses" :key="cls" :value="cls">
                {{ cls }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Section</label>
            <select v-model="filters.section" class="form-select" @change="fetchStudents">
              <option value="">All Sections</option>
              <option v-for="section in availableSections" :key="section" :value="section">
                {{ section }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Per Page</label>
            <select v-model="filters.per_page" class="form-select" @change="fetchStudents">
              <option value="10">10</option>
              <option value="15">15</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100" @click="clearFilters">
              <i class="bi bi-arrow-clockwise me-1"></i>Reset Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Students Table Card -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading students...</p>
        </div>

        <div v-else>
          <!-- Students Count -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
              Showing {{ students.data?.length || 0 }} of {{ students.total || 0 }} students
            </p>
            <div class="text-muted small">
              Page {{ students.current_page }} of {{ students.last_page }}
            </div>
          </div>

          <!-- Students Table -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Photo</th>
                  <th @click="sortBy('student_id')" class="cursor-pointer">
                    Student ID
                    <i class="bi bi-arrow-down-up ms-1 small"></i>
                  </th>
                  <th @click="sortBy('name')" class="cursor-pointer">
                    Name
                    <i class="bi bi-arrow-down-up ms-1 small"></i>
                  </th>
                  <th @click="sortBy('class')" class="cursor-pointer">
                    Class
                    <i class="bi bi-arrow-down-up ms-1 small"></i>
                  </th>
                  <th @click="sortBy('section')" class="cursor-pointer">
                    Section
                    <i class="bi bi-arrow-down-up ms-1 small"></i>
                  </th>
                  <th>Today's Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in students.data" :key="student.id">
                  <td>
                    <img
                      :src="student.photo_url || '/default-avatar.png'"
                      :alt="student.name"
                      class="rounded-circle student-photo"
                    />
                  </td>
                  <td>
                    <span class="fw-bold text-primary">{{ student.student_id }}</span>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ student.name }}</div>
                  </td>
                  <td>
                    <span class="badge bg-secondary">{{ student.class }}</span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark">{{ student.section }}</span>
                  </td>
                  <td>
                    <span v-if="student.today_attendance"
                          :class="`badge bg-${getStatusColor(student.today_attendance)}`">
                      {{ student.today_attendance }}
                    </span>
                    <span v-else class="badge bg-warning">Not Recorded</span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button
                        class="btn btn-outline-primary"
                        @click="editStudent(student)"
                        title="Edit Student"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        @click="deleteStudent(student.id)"
                        title="Delete Student"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="!loading && students.data?.length === 0" class="text-center py-5">
            <i class="bi bi-people display-1 text-muted"></i>
            <p class="mt-3 text-muted">No students found matching your criteria.</p>
            <button class="btn btn-primary" @click="clearFilters">
              <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
            </button>
          </div>

          <!-- Pagination -->
          <nav v-if="students.data?.length > 0" class="mt-4">
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: !students.prev_page_url }">
                <button class="page-link" @click="fetchStudents(students.current_page - 1)">
                  <i class="bi bi-chevron-left"></i> Previous
                </button>
              </li>

              <li v-for="page in paginationRange" :key="page"
                  class="page-item" :class="{ active: page === students.current_page }">
                <button class="page-link" @click="fetchStudents(page)">
                  {{ page }}
                </button>
              </li>

              <li class="page-item" :class="{ disabled: !students.next_page_url }">
                <button class="page-link" @click="fetchStudents(students.current_page + 1)">
                  Next <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div v-if="showAddModal || editingStudent" class="modal fade show d-block" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi" :class="editingStudent ? 'bi-pencil' : 'bi-plus-circle'"></i>
              {{ editingStudent ? 'Edit Student' : 'Add New Student' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveStudent">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Student ID *</label>
                    <input
                      v-model="studentForm.student_id"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.student_id }"
                      required
                      :disabled="!!editingStudent"
                    />
                    <div v-if="errors.student_id" class="invalid-feedback">
                      {{ errors.student_id[0] }}
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name *</label>
                    <input
                      v-model="studentForm.name"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.name }"
                      required
                    />
                    <div v-if="errors.name" class="invalid-feedback">
                      {{ errors.name[0] }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Class *</label>
                    <input
                      v-model="studentForm.class"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.class }"
                      required
                    />
                    <div v-if="errors.class" class="invalid-feedback">
                      {{ errors.class[0] }}
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Section *</label>
                    <input
                      v-model="studentForm.section"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.section }"
                      required
                    />
                    <div v-if="errors.section" class="invalid-feedback">
                      {{ errors.section[0] }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Student Photo</label>
                <input
                  type="file"
                  class="form-control"
                  :class="{ 'is-invalid': errors.photo }"
                  accept="image/*"
                  @change="handlePhotoUpload"
                />
                <div class="form-text">
                  Upload a profile photo (JPEG, PNG, GIF, max 2MB)
                </div>
                <div v-if="errors.photo" class="invalid-feedback">
                  {{ errors.photo[0] }}
                </div>

                <!-- Photo Preview -->
                <div v-if="photoPreview" class="mt-2">
                  <img :src="photoPreview" class="student-photo-preview rounded" />
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closeModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                  {{ saving ? 'Saving...' : (editingStudent ? 'Update' : 'Create') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { api } from '../services/api'

const students = ref({ data: [] })
const loading = ref(false)
const saving = ref(false)
const showAddModal = ref(false)
const editingStudent = ref(null)
const availableClasses = ref([])
const availableSections = ref([])
const photoPreview = ref(null)
const sortField = ref('name')
const sortDirection = ref('asc')

const filters = reactive({
  search: '',
  class: '',
  section: '',
  per_page: 15
})

const studentForm = reactive({
  student_id: '',
  name: '',
  class: '',
  section: '',
  photo: null
})

const errors = ref({})

const paginationRange = computed(() => {
  if (!students.value.last_page) return []

  const current = students.value.current_page
  const last = students.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  let prev
  for (let i of range) {
    if (prev) {
      if (i - prev === 2) {
        rangeWithDots.push(prev + 1)
      } else if (i - prev !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    prev = i
  }

  return rangeWithDots
})

const getStatusColor = (status) => {
  const colors = {
    present: 'success',
    absent: 'danger',
    late: 'warning'
  }
  return colors[status] || 'secondary'
}

const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
  fetchStudents()
}

const debouncedFetchStudents = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fetchStudents(), 500)
  }
})()

const fetchStudents = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page,
      ...filters,
      sort: sortField.value,
      direction: sortDirection.value
    })

    const response = await api.get(`/students?${params}`)
    students.value = response.data
  } catch (error) {
    console.error('Error fetching students:', error)
    alert('Failed to fetch students')
  } finally {
    loading.value = false
  }
}

const fetchClassesAndSections = async () => {
  try {
    const [classesRes, sectionsRes] = await Promise.all([
      api.get('/students/classes'),
      api.get('/students/sections')
    ])
    availableClasses.value = classesRes.data.data
    availableSections.value = sectionsRes.data.data
  } catch (error) {
    console.error('Error fetching classes and sections:', error)
  }
}

const saveStudent = async () => {
  saving.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    Object.keys(studentForm).forEach(key => {
      if (studentForm[key] !== null) {
        formData.append(key, studentForm[key])
      }
    })

    if (editingStudent.value) {
      await api.post(`/students/${editingStudent.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      await api.post('/students', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }

    closeModal()
    fetchStudents()
    alert(`Student ${editingStudent.value ? 'updated' : 'created'} successfully!`)
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      alert(`Failed to ${editingStudent.value ? 'update' : 'create'} student`)
    }
  } finally {
    saving.value = false
  }
}

const editStudent = (student) => {
  editingStudent.value = student
  Object.assign(studentForm, student)
  photoPreview.value = student.photo_url
}

const deleteStudent = async (id) => {
  if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
    return
  }

  try {
    await api.delete(`/students/${id}`)
    fetchStudents()
    alert('Student deleted successfully!')
  } catch (error) {
    console.error('Error deleting student:', error)
    alert('Failed to delete student')
  }
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    if (key !== 'per_page') {
      filters[key] = ''
    }
  })
  sortField.value = 'name'
  sortDirection.value = 'asc'
  fetchStudents()
}

const closeModal = () => {
  showAddModal.value = false
  editingStudent.value = null
  Object.keys(studentForm).forEach(key => {
    studentForm[key] = ''
  })
  studentForm.photo = null
  photoPreview.value = null
  errors.value = {}
}

const handlePhotoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    studentForm.photo = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

onMounted(() => {
  fetchStudents()
  fetchClassesAndSections()
})
</script>

<style scoped>
.student-photo {
  width: 40px;
  height: 40px;
  object-fit: cover;
}

.student-photo-preview {
  width: 100px;
  height: 100px;
  object-fit: cover;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: #f8f9fa;
}

.modal {
  backdrop-filter: blur(2px);
}
</style>
