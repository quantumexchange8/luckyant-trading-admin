<script setup>
import Tooltip from "@/Components/Tooltip.vue";
import Button from '@/Components/Button.vue'
import {ClipboardListIcon, TrashIcon, UserGroupIcon, PlusIcon} from "@heroicons/vue/outline";
import { Configuration } from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    masters: Object,
})

</script>

<template>
    <div class="inline-flex justify-center items-center gap-2">
        <Tooltip content="Configuration" placement="bottom" class="relative">
            <Button
                type="button"
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                :href="'/master/master_configuration/' + masters.id"
                pill
            >
                <Configuration aria-hidden="true" class="w-3 h-3 absolute" />
                <span class="sr-only">View Details</span>
            </Button>
        </Tooltip>
    </div>
</template>