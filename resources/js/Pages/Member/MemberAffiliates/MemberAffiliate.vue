<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AffiliateChild from "@/Pages/Member/MemberAffiliates/Partials/AffiliateChild.vue";
import {ref, watch} from "vue";
import InputText from "primevue/inputtext";
import debounce from "lodash/debounce.js";
import {
    IconCircleXFilled,
    IconSearch,
    IconChevronRight
} from "@tabler/icons-vue";

const props = defineProps({
    root: Object,
});

const search = ref('');
const rootNode = ref(props.root);

const clearSearch = () => {
    search.value = '';
    rootNode.value = props.root;
}

const searchUser = async (keyword) => {
    try {
        if (keyword) {
            const response = await axios.get(`/member/getTreeData/${props.root.id}?search=${keyword}`);
            if (response.data.success) {
                rootNode.value = response.data.data;
            } else {
                rootNode.value = props.root;
            }
        }
    } catch (e) {
        console.error('Search failed:', e);
        rootNode.value = props.root;
    }
};

watch(search, debounce(function() {
    searchUser(search.value);
}, 300));
</script>

<template>
    <AuthenticatedLayout title="Member Affiliate">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-sm font-semibold dark:text-gray-400">
                    <a class="dark:hover:text-white hover:text-primary-500" href="/member/member_listing">Member Listing</a>
                </h2>
                <IconChevronRight size="24" stroke-width="1.5" />
                <h2 class="text-sm font-semibold dark:text-white">
                    {{ root.name }} - View Details
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex self-stretch w-full">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                        <IconSearch size="20" stroke-width="1.5"/>
                    </div>
                    <InputText
                        v-model="search"
                        :placeholder="$t('public.search')"
                        class="font-normal pl-12 w-full md:w-60"
                    />
                    <div
                        v-if="search"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                        @click="clearSearch"
                    >
                        <IconCircleXFilled size="16"/>
                    </div>
                </div>
            </div>

            <AffiliateChild
                :root="root"
                :node="rootNode"
            />
        </div>
    </AuthenticatedLayout>
</template>
