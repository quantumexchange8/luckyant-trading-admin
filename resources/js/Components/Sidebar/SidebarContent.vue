<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import { DashboardIcon, Setting, Users01Icon, UsersSquareIcon, UsersCheckIcon, File06Icon } from '@/Components/Icons/outline'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { ClipboardListIcon, SpeakerphoneIcon } from '@heroicons/vue/outline'
import {usePage} from "@inertiajs/vue3";
import {ref} from "vue";

const page = usePage();
const pendingTransactionCount = ref(page.props.pendingTransactionCount);
const pendingKycCount = ref(page.props.pendingKycCount);
const pendingMasterCount = ref(page.props.pendingMasterCount);
const pendingSubscriberRequestCount = ref(page.props.pendingSubscriberRequestCount);
</script>

<template>
    <PerfectScrollbar
        tagname="nav"
        aria-label="main"
        class="relative flex flex-col flex-1 max-h-full gap-4 px-3"
    >
        <SidebarLink
            title="Dashboard"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <DashboardIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

        <SidebarLink
            title="Announcement"
            :href="route('announcement.announcement_listing')"
            :active="route().current('announcement.announcement_listing')"
        >
            <template #icon>
                <SpeakerphoneIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

        <SidebarCollapsible
            title="Members"
            :active="route().current('member.*')"
            :pending-counts="pendingKycCount"
        >
            <template #icon>
                <Users01Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('member.member_listing')"
                title="Member Listing"
                :active="route().current('member.member_listing')"
                :pending-counts="pendingKycCount"
            />
            <SidebarCollapsibleItem
                :href="route('member.affiliate_listing')"
                title="Affiliate Listing"
                :active="route().current('member.affiliate_listing')"
            />
            <SidebarCollapsibleItem
                :href="route('member.live_trading')"
                title="Live Account Listing"
                :active="route().current('member.live_trading')"
            />
        </SidebarCollapsible>

        <SidebarCollapsible
            title="Transactions"
            :active="route().current('transaction.*')"
            :pending-counts="pendingTransactionCount"
        >
            <template #icon>
                <ClipboardListIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('transaction.pending_transaction')"
                title="Pending Transaction"
                :active="route().current('transaction.pending_transaction')"
                :pending-counts="pendingTransactionCount"
            />

            <SidebarCollapsibleItem
                :href="route('transaction.transaction_history')"
                title="Transaction History"
                :active="route().current('transaction.transaction_history')"
            />
        </SidebarCollapsible>
        <SidebarCollapsible
            title="Master"
            :active="route().current('master.*')"
            :pending-counts="pendingMasterCount"
        >
            <template #icon>
                <UsersSquareIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('master.master_request')"
                title="Master Request"
                :active="route().current('master.master_request')"
                :pending-counts="pendingMasterCount"
            />
            <SidebarCollapsibleItem
                :href="route('master.getMasterListing')"
                title="Master Listing"
                :active="route().current('master.getMasterListing')"
            />
        </SidebarCollapsible>
        <SidebarCollapsible
            title="Subscribers"
            :active="route().current('subscription.*')"
            :pending-counts="pendingSubscriberRequestCount"
        >
            <template #icon>
                <UsersCheckIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('subscription.subscribers')"
                title="Pending Request"
                :active="route().current('subscription.subscribers')"
                :pending-counts="pendingSubscriberRequestCount"
            />
            <SidebarCollapsibleItem
                :href="route('subscription.subscribersListing')"
                title="Subscribers Listing"
                :active="route().current('subscription.subscribersListing')"
            />
            <SidebarCollapsibleItem
                :href="route('subscription.subscriptionHistory')"
                title="Subscriptions History"
                :active="route().current('subscription.subscriptionHistory')"
            />
        </SidebarCollapsible>
        <SidebarCollapsible
            title="Report"
            :active="route().current('report.*')"
        >
            <template #icon>
                <File06Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('report.trading_rebate')"
                title="Trading Rebate"
                :active="route().current('report.trading_rebate')"
            />
        </SidebarCollapsible>
        <SidebarCollapsible
            title="Setting"
            :active="route().current('setting.*')"
        >
            <template #icon>
                <Setting
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
            <SidebarCollapsibleItem
                :href="route('setting.master_setting')"
                title="Master Setting"
                :active="route().current('setting.master_setting')"
            />
            <SidebarCollapsibleItem
                :href="route('setting.payment_setting')"
                title="Payment Setting"
                :active="route().current('setting.payment_setting')"
            />
            <SidebarCollapsibleItem
                :href="route('setting.tnc_setting')"
                title="T&C Setting"
                :active="route().current('setting.tnc_setting')"
            />
            <SidebarCollapsibleItem
                :href="route('setting.leverage_setting')"
                title="Leverage Setting"
                :active="route().current('setting.leverage_setting')"
            />
        </SidebarCollapsible>
        <!-- <SidebarCollapsible
            title="Admin"
            :active="route().current('admin.*')"
        >
            <template #icon>
                <Setting
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('admin.admin')"
                title="Admin Listing"
                :active="route().current('admin.admin')"
            />
        </SidebarCollapsible> -->
<!--        <SidebarCollapsible-->
<!--            title="Components"-->
<!--            :active="route().current('components.*')"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <TemplateIcon-->
<!--                    class="flex-shrink-0 w-6 h-6"-->
<!--                    aria-hidden="true"-->
<!--                />-->
<!--            </template>-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('components.buttons')"-->
<!--                title="Buttons"-->
<!--                :active="route().current('components.buttons')"-->
<!--            />-->
<!--        </SidebarCollapsible>-->

        <!-- Examples -->
        <!--
        => External link example
        <SidebarLink
            title="Github"
            href="https://github.com/kamona-wd/kui-laravel-breeze"
            external
            target="_blank"
        >
        </SidebarLink>

        => Collapsible examples
        <SidebarCollapsible title="Users" :active="$page.url.startsWith('/users')">
            <SidebarCollapsibleItem :href="route('users.index')" title="List" :active="$page.url === '/users/index'" />
            <SidebarCollapsibleItem :href="route('users.create')" title="Create" :active="$page.url === '/users/create'" />
        </SidebarCollapsible>

        <SidebarCollapsible title="Users" :active="usePage().url.value.startsWith('/users')">
            <template #icon>
                <UserIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem :href="route('users.index')" title="List" :active="route().current('users.index')" />
            <SidebarCollapsibleItem :href="route('users.create')" title="Create" :active="route().current('users.create')" />
        </SidebarCollapsible>-->
    </PerfectScrollbar>
</template>
