<script setup>
import RoomCreateForm from './components/RoomCreateForm.vue'
</script>

<template>
  <main style="max-width: 900px; margin: 32px auto; padding: 0 16px;">
    <h1 style="margin-bottom: 8px;">Đăng phòng (Vue + FastAPI)</h1>
    <p style="color:#64748b; margin-top:0;">Bản scaffold: gọi API /api/v1/rooms</p>
    <RoomCreateForm />
  </main>
</template>
