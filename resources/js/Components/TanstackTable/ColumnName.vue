<script setup>
const props = defineProps({
    user: Object
})
</script>

<template>
    <div class="flex items-center gap-2 w-52">
        <img :src="user.profile_photo_url ? user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
        <div class="flex flex-col gap-1">
            <div>
                {{ user.name }}
            </div>
            <div>
                {{ user.email }}
            </div>
        </div>
    </div>
</template>
