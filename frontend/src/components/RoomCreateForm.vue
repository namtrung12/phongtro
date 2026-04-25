<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'

const form = reactive({
  title: '',
  price: '',
  area: '',
  address: '',
  description: '',
  thumbnail: '',
  video_url: '',
  lead_price_expect: '',
  shared_owner: false,
  closed_room: false
})

const loading = ref(false)
const message = ref('')

const submit = async () => {
  message.value = ''
  loading.value = true
  try {
    const payload = {
      ...form,
      price: Number(form.price),
      lead_price_expect: form.lead_price_expect ? Number(form.lead_price_expect) : null,
      thumbnail: form.thumbnail || null,
      video_url: form.video_url || null
    }
    const res = await axios.post('/api/v1/rooms', payload)
    message.value = `Đã tạo phòng #${res.data.id}, trạng thái: ${res.data.status}`
  } catch (err) {
    message.value = err?.response?.data?.detail || 'Lỗi gửi form'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="card">
    <div class="grid">
      <label>
        <span>Tiêu đề</span>
        <input v-model="form.title" required maxlength="255" placeholder="Phòng studio 25m2..." />
      </label>
      <label>
        <span>Giá (đ/tháng)</span>
        <input v-model="form.price" required type="number" min="0" />
      </label>
      <label>
        <span>Giá lead mong muốn</span>
        <input v-model="form.lead_price_expect" type="number" min="0" />
      </label>
      <label>
        <span>Khu vực</span>
        <input v-model="form.area" required maxlength="120" placeholder="TP Thanh Hóa" />
      </label>
      <label>
        <span>Địa chỉ</span>
        <input v-model="form.address" required maxlength="255" placeholder="25 Trần Phú, P. Điện Biên, TP Thanh Hóa" />
      </label>
      <label class="full">
        <span>Mô tả</span>
        <textarea v-model="form.description" rows="3" maxlength="4000" placeholder="Nội thất, diện tích, tiện ích..."></textarea>
      </label>
      <label>
        <span>Ảnh chính (URL)</span>
        <input v-model="form.thumbnail" type="url" placeholder="https://..." />
      </label>
      <label>
        <span>Video (URL)</span>
        <input v-model="form.video_url" type="url" placeholder="https://youtube.com/watch?v=..." />
      </label>
      <label class="checkbox">
        <input v-model="form.shared_owner" type="checkbox" />
        <span>Chung chủ</span>
      </label>
      <label class="checkbox">
        <input v-model="form.closed_room" type="checkbox" />
        <span>Phòng khép kín</span>
      </label>
    </div>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Đang gửi...' : 'Đăng phòng' }}
    </button>
    <p v-if="message" class="msg">{{ message }}</p>
  </form>
</template>

<style scoped>
.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 12px;
}
label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-weight: 600;
  color: #0f172a;
}
label span { font-size: 14px; }
input, textarea {
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid #d8dce6;
  font-size: 14px;
}
textarea { resize: vertical; }
.checkbox {
  flex-direction: row;
  align-items: center;
  gap: 8px;
}
.full { grid-column: 1 / -1; }
button {
  margin-top: 14px;
  padding: 10px 14px;
  border-radius: 10px;
  border: none;
  background: #2563eb;
  color: #fff;
  font-weight: 700;
  width: 160px;
}
.msg { color: #0f172a; font-size: 14px; margin-top: 10px; }
</style>
