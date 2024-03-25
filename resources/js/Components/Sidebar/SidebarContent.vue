<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import { DashboardIcon, Setting } from '@/Components/Icons/outline'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { TemplateIcon, UsersIcon, ClipboardListIcon, SpeakerphoneIcon, DocumentReportIcon } from '@heroicons/vue/outline'
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

        <SidebarCollapsible
            title="Members"
            :active="route().current('member.*')"
        >
            <template #icon>
                <UsersIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('member.member_listing')"
                title="Member Listing"
                :active="route().current('member.member_listing')"
            />
            <SidebarCollapsibleItem
                :href="route('member.live_trading')"
                title="Live Account Listing"
                :active="route().current('member.live_trading')"
            />
        </SidebarCollapsible>

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
            title="Transactions"
            :active="route().current('transaction.*')"
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
        >
            <template #icon>
                <UsersIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('master.master_request')"
                title="Master Request"
                :active="route().current('master.master_request')"
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
        >
            <template #icon>
                <UsersIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('subscription.subscribers')"
                title="Pending Request"
                :active="route().current('subscription.subscribers')"
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
        <!-- <SidebarCollapsible
            title="Report"
            :active="route().current('report.*')"
        >
            <template #icon>
                <DocumentReportIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template> -->

            <!-- <SidebarCollapsibleItem
                :href="route('setting.payment_setting')"
                title="Payment Setting"
                :active="route().current('setting.payment_setting')"
            /> -->
            <!-- <SidebarCollapsibleItem title="In Progress..."/>
        </SidebarCollapsible> -->
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
