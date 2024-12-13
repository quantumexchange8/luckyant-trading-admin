<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import {
    DashboardIcon,
    Setting,
    Users01Icon,
    UsersSquareIcon,
    UsersCheckIcon,
    File06Icon,
    UserUp01Icon,
    FileCheck02Icon,
    ScalesTwoIcon
} from '@/Components/Icons/outline'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { ClipboardListIcon, SpeakerphoneIcon } from '@heroicons/vue/outline'
import {usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import { usePermission } from '@/Composables/permissions.js'

const page = usePage();
const pendingTransactionCount = ref(page.props.pendingTransactionCount);
const pendingKycCount = ref(page.props.pendingKycCount);
const pendingMasterCount = ref(page.props.pendingMasterCount);
const pendingSubscriberRequestCount = ref(page.props.pendingSubscriberRequestCount);
const pendingRenewalCount = ref(page.props.pendingRenewalCount);
const pendingSwitchMasterCount = ref(page.props.pendingSwitchMasterCount);
const pendingPammCount = ref(page.props.pendingPammCount);
const { hasRole } = usePermission();
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

        <SidebarLink
            :title="$t('public.master')"
            :href="route('master.master_listing')"
            :active="route().current('master.master_listing') || route().current('master.*')"
        >
            <template #icon>
                <UsersSquareIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

<!--        <SidebarCollapsible-->
<!--            title="Subscribers"-->
<!--            :active="route().current('subscriber.*')"-->
<!--            :pending-counts="pendingSubscriberRequestCount"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <UsersCheckIcon-->
<!--                    class="flex-shrink-0 w-6 h-6"-->
<!--                    aria-hidden="true"-->
<!--                />-->
<!--            </template>-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscriber.pending_subscriber')"-->
<!--                title="Pending Subscribers"-->
<!--                :active="route().current('subscriber.pending_subscriber')"-->
<!--                :pending-counts="pendingSubscriberRequestCount"-->
<!--            />-->
<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscriber.subscribersListing')"-->
<!--                title="Subscribers Listing"-->
<!--                :active="route().current('subscriber.subscribersListing')"-->
<!--            />-->
<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscriber.switch_master')"-->
<!--                title="Switch Master"-->
<!--                :active="route().current('subscriber.switch_master')"-->
<!--                :pending-counts="pendingSwitchMasterCount"-->
<!--            />-->
<!--        </SidebarCollapsible>-->

<!--        <SidebarCollapsible-->
<!--            title="Subscriptions"-->
<!--            :active="route().current('subscription.*')"-->
<!--            :pending-counts="pendingSubscriberRequestCount"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <FileCheck02Icon-->
<!--                    class="flex-shrink-0 w-6 h-6"-->
<!--                    aria-hidden="true"-->
<!--                />-->
<!--            </template>-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscription.pending_renewal')"-->
<!--                title="Pending Renewal"-->
<!--                :active="route().current('subscription.pending_renewal')"-->
<!--                :pending-counts="pendingRenewalCount"-->
<!--            />-->
<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscription.subscription_listing')"-->
<!--                title="Subscriptions Listing"-->
<!--                :active="route().current('subscription.subscription_listing')"-->
<!--            />-->
<!--            <SidebarCollapsibleItem-->
<!--                :href="route('subscription.termination_fee')"-->
<!--                title="Termination Fee"-->
<!--                :active="route().current('subscription.termination_fee')"-->
<!--            />-->
<!--        </SidebarCollapsible>-->

        <SidebarCollapsible
            title="Copy Trading"
            :active="route().current('copy_trading.*')"
            :pending-counts="pendingSubscriberRequestCount"
        >
            <template #icon>
                <ScalesTwoIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('copy_trading.pending')"
                :title="$t('public.pending')"
                :active="route().current('copy_trading.pending')"
                :pending-counts="pendingSubscriberRequestCount"
            />
            <SidebarCollapsibleItem
                :href="route('copy_trading.listing')"
                :title="$t('public.listing')"
                :active="route().current('copy_trading.listing')"
            />
            <SidebarCollapsibleItem
                :href="route('copy_trading.termination_report')"
                :title="$t('public.termination_report')"
                :active="route().current('copy_trading.termination_report')"
            />
            <SidebarCollapsibleItem
                :href="route('copy_trading.switch_master')"
                :title="$t('public.switch_master')"
                :active="route().current('copy_trading.switch_master')"
            />
        </SidebarCollapsible>

        <SidebarCollapsible
            title="PAMM"
            :active="route().current('pamm.*')"
            :pending-counts="pendingPammCount"
        >
            <template #icon>
                <FileCheck02Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('pamm.pending_pamm')"
                title="Pending PAMM"
                :active="route().current('pamm.pending_pamm')"
                :pending-counts="pendingRenewalCount"
            />
            <SidebarCollapsibleItem
                :href="route('pamm.pamm_listing')"
                title="PAMM Listing"
                :active="route().current('pamm.pamm_listing')"
            />
            <SidebarCollapsibleItem
                :href="route('pamm.termination_report')"
                :title="$t('public.termination_report')"
                :active="route().current('pamm.termination_report')"
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
            <SidebarCollapsibleItem
                :href="route('report.performance_incentive')"
                title="Performance Incentive"
                :active="route().current('report.performance_incentive')"
            />
            <SidebarCollapsibleItem
                :href="route('report.daily_register')"
                :title="$t('public.daily_register')"
                :active="route().current('report.daily_register')"
            />
        </SidebarCollapsible>

        <SidebarCollapsible
            title="Admin"
            :active="route().current('admin.*')"
        >
            <template #icon>
                <UserUp01Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('admin.admin_listing')"
                title="Admin Listing"
                :active="route().current('admin.admin_listing')"
            />
        </SidebarCollapsible>

        <SidebarCollapsible
            v-if="hasRole('super-admin')"
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
                :href="route('setting.account_type')"
                title="Account Type"
                :active="route().current('setting.account_type')"
            />
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
                :href="route('setting.payment_gateway')"
                title="Payment Gateway"
                :active="route().current('setting.payment_gateway')"
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
            <SidebarCollapsibleItem
                :href="route('setting.bank_withdrawal_setting')"
                title="Bank Withdrawal Setting"
                :active="route().current('setting.bank_withdrawal_setting')"
            />
        </SidebarCollapsible>

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
