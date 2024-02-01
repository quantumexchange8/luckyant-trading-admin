<script setup>
import AffiliateChild from "@/Pages/Member/MemberAffiliates/Partials/AffiliateChild.vue";
import {ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import Input from "@/Components/Input.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";

const props = defineProps({
    user: Object
})

let search = ref(null);
let root = ref({});
const isLoading = ref(false);

watch(search, debounce(function() {
    isLoading.value = true;
    getResults(search.value);
}, 300));

const getResults = async (search = '') => {
    isLoading.value = true;
    try {
        let url = `/member/getTreeData/${props.user.id}`;

        if (search) {
            url += `?search=${search}`;
        }

        const response = await axios.get(url);
        root.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

getResults();

</script>

<template>
    <div class="pt-8">
        <InputIconWrapper>
            <template #icon>
                <SearchIcon aria-hidden="true" class="w-5 h-5" />
            </template>
            <Input
                withIcon
                id="search"
                type="text"
                class="block border-transparent w-full md:w-1/3"
                placeholder="Search"
                v-model="search"
            />
        </InputIconWrapper>
    </div>
    <AffiliateChild
        :node="root"
        :isLoading="isLoading"
        class="pt-8 overflow-x-auto"
    />
</template>
