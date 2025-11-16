<template>
  <div>
    <h2 class="mb-4">
      <i class="bi bi-clipboard-check me-2"></i>Attendance Recording
    </h2>

    <!-- Class Selection Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">
          <i class="bi bi-funnel me-1"></i>Select Class & Date
        </h5>
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Class *</label>
            <select v-model="selectedClass" class="form-select" :class="{ 'is-invalid': !selectedClass }">
              <option value="">Select Class</option>
              <option v-for="cls in availableClasses" :key="cls" :value="cls">
                Class {{ cls }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Section</label>
            <select v-model="selectedSection" class="form-select">
              <option value="">All Sections</option>
              <option v-for="section in availableSections" :key="section" :value="section">
                Section {{ section }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Date *</label>
            <input v-model="attendanceDate" type="date" class="form-control" :max="maxDate" />
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary w-100" @click="loadStudents" :disabled="!selectedClass || loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
              {{ loading ? 'Loading...' : 'Load Students' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Actions & Stats -->
    <div v-if="students.length > 0" class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h6 class="mb-2 fw-semibold">Bulk Actions:</h6>
            <div class="btn-group">
              <button class="btn btn-success btn-sm" @click="bulkMark('present')">
                <i class="bi bi-check-circle me-1"></i>Mark All Present
              </button>
              <button class="btn btn-danger btn-sm" @click="bulkMark('absent')">
                <i class="bi bi-x-circle me-1"></i>Mark All Absent
              </button>
              <button class="btn btn-warning btn-sm" @click="bulkMark('late')">
                <i class="bi bi-clock me-1"></i>Mark All Late
              </button>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <div class="attendance-stats">
              <div class="stat-item text-success">
                <i class="bi bi-check-circle-fill me-1"></i>
                <strong>Present: {{ stats.present }}</strong>
              </div>
              <div class="stat-item text-danger">
                <i class="bi bi-x-circle-fill me-1"></i>
                <strong>Absent: {{ stats.absent }}</strong>
              </div>
              <div class="stat-item text-warning">
                <i class="bi bi-clock-fill me-1"></i>
                <strong>Late: {{ stats.late }}</strong>
              </div>
              <div class="stat-total mt-1">
                <small class="text-muted">
                  Total: {{ students.length }} |
                  Recorded: {{ stats.recorded }} |
                  Percentage: <strong :class="stats.percentage >= 80 ? 'text-success' : 'text-warning'">
                    {{ stats.percentage }}%
                  </strong>
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <div v-if="students.length > 0" class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Section</th>
                <th>Status</th>
                <th>Note</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id"
                  :class="getRowClass(attendanceRecords[student.id]?.status)">
                <td>
                  <span class="fw-bold text-primary">{{ student.student_id }}</span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <img
                      :src="student.photo_url || '/default-avatar.png'"
                      :alt="student.name"
                      class="rounded-circle me-2 student-photo"
                    />
                    {{ student.name }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-secondary">{{ student.class }}</span>
                </td>
                <td>
                  <span class="badge bg-light text-dark">{{ student.section }}</span>
                </td>
                <td>
                  <select
                    v-model="attendanceRecords[student.id].status"
                    class="form-select form-select-sm status-select"
                    :class="getStatusClass(attendanceRecords[student.id]?.status)"
                    @change="updateStats"
                  >
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                  </select>
                </td>
                <td>
                  <input
                    v-model="attendanceRecords[student.id].note"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Optional note..."
                    @input="updateStats"
                  />
                </td>
                <td>
                  <button
                    class="btn btn-outline-secondary btn-sm"
                    @click="copyToAll(student.id)"
                    title="Copy this status to all"
                  >
                    <i class="bi bi-copy"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <button class="btn btn-outline-secondary" @click="clearForm">
            <i class="bi bi-arrow-clockwise me-1"></i>Clear Form
          </button>
          <button
            class="btn btn-success"
            @click="submitAttendance"
            :disabled="submitting || stats.recorded === 0"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-check-circle me-1"></i>
            {{ submitting ? 'Submitting...' : `Submit Attendance (${stats.recorded} students)` }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty States -->
    <div v-else-if="selectedClass && !loading" class="text-center py-5 text-muted">
      <i class="bi bi-people display-1"></i>
      <p class="mt-3">No students found for the selected class and section.</p>
    </div>

    <div v-else-if="!selectedClass" class="text-center py-5 text-muted">
      <i class="bi bi-funnel display-1"></i>
      <p class="mt-3">Please select a class to load students.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { api } from '../services/api'

const students = ref([])
const selectedClass = ref('')
const selectedSection = ref('')
const attendanceDate = ref(new Date().toISOString().split('T')[0])
const loading = ref(false)
const submitting = ref(false)
const availableClasses = ref([])
const availableSections = ref([])

const attendanceRecords = reactive({})

const maxDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const stats = computed(() => {
  const records = Object.values(attendanceRecords)
  const present = records.filter(r => r.status === 'present').length
  const absent = records.filter(r => r.status === 'absent').length
  const late = records.filter(r => r.status === 'late').length
  const recorded = present + absent + late
  const percentage = students.value.length > 0 ? Math.round((recorded / students.value.length) * 100) : 0

  return { present, absent, late, recorded, percentage }
})

const loadStudents = async () => {
  if (!selectedClass.value) {
    alert('Please select a class first')
    return
  }

  loading.value = true
  students.value = []

  try {
    const params = new URLSearchParams({
      class: selectedClass.value,
      section: selectedSection.value,
      per_page: 100 // Load all students for the class
    })

    const response = await api.get(`/students?${params}`)
    students.value = response.data.data

    // Initialize attendance records
    students.value.forEach(student => {
      attendanceRecords[student.id] = {
        student_id: student.id,
        date: attendanceDate.value,
        status: student.today_attendance || 'present', // Use existing attendance if available
        note: ''
      }
    })

    updateStats()
  } catch (error) {
    console.error('Error loading students:', error)
    alert('Failed to load students')
  } finally {
    loading.value = false
  }
}

const bulkMark = (status) => {
  students.value.forEach(student => {
    if (attendanceRecords[student.id]) {
      attendanceRecords[student.id].status = status
    }
  })
  updateStats()
}

const copyToAll = (studentId) => {
  const sourceRecord = attendanceRecords[studentId]
  if (sourceRecord) {
    students.value.forEach(student => {
      if (attendanceRecords[student.id]) {
        attendanceRecords[student.id].status = sourceRecord.status
      }
    })
    updateStats()
  }
}

const updateStats = () => {
  // Reactive computed property will update automatically
}

const submitAttendance = async () => {
  if (stats.value.recorded === 0) {
    alert('No attendance records to submit')
    return
  }

  submitting.value = true
  try {
    const attendances = Object.values(attendanceRecords)
    await api.post('/attendance/bulk', { attendances })

    alert(`Attendance recorded successfully for ${stats.value.recorded} students!`)
    clearForm()
  } catch (error) {
    console.error('Error recording attendance:', error)
    if (error.response?.data?.errors) {
      alert('Validation error: ' + JSON.stringify(error.response.data.errors))
    } else {
      alert('Failed to record attendance')
    }
  } finally {
    submitting.value = false
  }
}

const clearForm = () => {
  students.value = []
  selectedClass.value = ''
  selectedSection.value = ''
  Object.keys(attendanceRecords).forEach(key => {
    delete attendanceRecords[key]
  })
}

const getStatusClass = (status) => {
  const classes = {
    present: 'border-success text-success',
    absent: 'border-danger text-danger',
    late: 'border-warning text-warning'
  }
  return classes[status] || ''
}

const getRowClass = (status) => {
  const classes = {
    present: 'table-success',
    absent: 'table-danger',
    late: 'table-warning'
  }
  return classes[status] || ''
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

// Watch for date changes and update all records
watch(attendanceDate, (newDate) => {
  students.value.forEach(student => {
    if (attendanceRecords[student.id]) {
      attendanceRecords[student.id].date = newDate
    }
  })
})

onMounted(() => {
  fetchClassesAndSections()
})
</script>

<style scoped>
.student-photo {
  width: 32px;
  height: 32px;
  object-fit: cover;
}

.attendance-stats {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-item {
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.status-select {
  min-width: 100px;
  font-weight: 500;
}

.table-success {
  background-color: rgba(25, 135, 84, 0.05);
}

.table-danger {
  background-color: rgba(220, 53, 69, 0.05);
}

.table-warning {
  background-color: rgba(255, 193, 7, 0.05);
}

.btn-group .btn {
  margin-right: 0.5rem;
}

.btn-group .btn:last-child {
  margin-right: 0;
}
</style>
