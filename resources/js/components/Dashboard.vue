<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
      </h2>
      <div class="text-muted">
        {{ currentDate }}
      </div>
    </div>

    <!-- Today's Summary Cards -->
    <div class="row mb-5">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-primary shadow h-100">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col">
                <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                  Total Students
                </div>
                <div class="h5 mb-0 fw-bold text-gray-800">
                  {{ summary.total_students }}
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-people fa-2x text-primary"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-success shadow h-100">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col">
                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                  Present Today
                </div>
                <div class="h5 mb-0 fw-bold text-gray-800">
                  {{ summary.present_count }}
                </div>
                <div class="mt-2 text-success small">
                  <i class="bi bi-arrow-up me-1"></i>
                  {{ summary.present_percentage }}%
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-check-circle fa-2x text-success"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-danger shadow h-100">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col">
                <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                  Absent Today
                </div>
                <div class="h5 mb-0 fw-bold text-gray-800">
                  {{ summary.absent_count }}
                </div>
                <div class="mt-2 text-muted small">
                  {{ summary.recorded_count }}/{{ summary.total_students }} recorded
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-x-circle fa-2x text-danger"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-warning shadow h-100">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col">
                <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                  Overall Attendance
                </div>
                <div class="h5 mb-0 fw-bold text-gray-800">
                  {{ summary.overall_percentage }}%
                </div>
                <div class="mt-2 text-warning small">
                  {{ summary.recorded_count }} students recorded
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-clipboard-data fa-2x text-warning"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Chart -->
    <div class="row">
      <div class="col-12">
        <div class="card shadow">
          <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
              <i class="bi bi-bar-chart me-2"></i>Monthly Attendance Report
            </h5>
          </div>
          <div class="card-body">
            <div class="row mb-4">
              <div class="col-md-4">
                <label class="form-label fw-bold">Select Month</label>
                <select v-model="chartMonth" class="form-select" @change="fetchMonthlyReport">
                  <option v-for="month in months" :key="month.value" :value="month.value">
                    {{ month.label }}
                  </option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Filter by Class</label>
                <select v-model="selectedClass" class="form-select" @change="fetchMonthlyReport">
                  <option value="">All Classes</option>
                  <option v-for="cls in classes" :key="cls" :value="cls">
                    Class {{ cls }}
                  </option>
                </select>
              </div>
            </div>

            <div v-if="monthlyReport.length > 0" class="chart-container">
              <Bar :data="chartData" :options="chartOptions" />
            </div>
            <div v-else class="text-center text-muted py-5">
              <i class="bi bi-inbox fa-3x mb-3"></i>
              <p class="mb-0">No attendance data available for the selected period.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { api } from '../services/api'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const summary = ref({
  total_students: 0,
  present_count: 0,
  absent_count: 0,
  late_count: 0,
  recorded_count: 0,
  present_percentage: 0,
  overall_percentage: 0
})

const monthlyReport = ref([])
const classes = ref([])
const chartMonth = ref(new Date().toISOString().slice(0, 7))
const selectedClass = ref('')

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const months = computed(() => {
  const months = []
  const currentDate = new Date()
  for (let i = 0; i < 6; i++) {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1)
    months.push({
      value: date.toISOString().slice(0, 7),
      label: date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
    })
  }
  return months
})

const chartData = computed(() => {
  const classData = monthlyReport.value.reduce((acc, item) => {
    const cls = item.student.class
    if (!acc[cls]) {
      acc[cls] = { present: 0, total: 0 }
    }
    acc[cls].present += item.present_days
    acc[cls].total += item.total_days
    return acc
  }, {})

  const labels = Object.keys(classData).sort()
  const percentages = labels.map(cls => {
    const data = classData[cls]
    return data.total > 0 ? (data.present / data.total) * 100 : 0
  })

  return {
    labels: labels.map(cls => `Class ${cls}`),
    datasets: [
      {
        label: 'Attendance Percentage (%)',
        data: percentages,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        borderRadius: 4,
      }
    ]
  }
})

const chartOptions = reactive({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: true,
      text: 'Class-wise Monthly Attendance Percentage'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `Attendance: ${context.raw.toFixed(1)}%`
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      title: {
        display: true,
        text: 'Attendance Percentage (%)'
      },
      ticks: {
        callback: function(value) {
          return value + '%'
        }
      }
    },
    x: {
      title: {
        display: true,
        text: 'Classes'
      }
    }
  }
})

const fetchTodaySummary = async () => {
  try {
    const response = await api.get('/attendance/today-summary')
    summary.value = response.data.data
  } catch (error) {
    console.error('Error fetching today summary:', error)
  }
}

const fetchMonthlyReport = async () => {
  try {
    const params = { month: chartMonth.value }
    if (selectedClass.value) {
      params.class = selectedClass.value
    }

    const response = await api.get('/attendance/monthly-report', { params })
    monthlyReport.value = response.data.data
  } catch (error) {
    console.error('Error fetching monthly report:', error)
    monthlyReport.value = []
  }
}

const fetchClasses = async () => {
  try {
    const response = await api.get('/students/classes')
    classes.value = response.data.data
  } catch (error) {
    console.error('Error fetching classes:', error)
  }
}

onMounted(() => {
  fetchTodaySummary()
  fetchMonthlyReport()
  fetchClasses()
})
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 400px;
  width: 100%;
}

.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}
</style>
