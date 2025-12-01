<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { GraduationCap, LayoutGrid, User, Users, Wallet, FileText, BookOpen, CreditCard, BarChart } from 'lucide-vue-next';
import StudentController from '@/actions/App/Http/Controllers/StudentController';
import TeacherController from '@/actions/App/Http/Controllers/TeacherController';
import UserController from '@/actions/App/Http/Controllers/UserController';
import ChartOfAccountController from '@/actions/App/Http/Controllers/Finance/ChartOfAccountController';
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import JournalEntryController from '@/actions/App/Http/Controllers/Finance/JournalEntryController';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import ReportController from '@/actions/App/Http/Controllers/Finance/ReportController';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: UserController.index().url,
        icon: User,
    },
    {
        title: 'Teachers',
        href: TeacherController.index().url,
        icon: GraduationCap,
    },
    {
        title: 'Students',
        href: StudentController.index().url,
        icon: Users,
    },
    {
        title: 'Chart of Accounts',
        href: ChartOfAccountController.index().url,
        icon: Wallet,
    },
    {
        title: 'Transaction Configurations',
        href: TransactionTypeController.index().url,
        icon: FileText,
    },
    {
        title: 'Journal Entries',
        href: JournalEntryController.index().url,
        icon: BookOpen,
    },
    {
        title: 'Transactions',
        href: TransactionController.index().url,
        icon: CreditCard,
    },
    {
        title: 'Reports',
        href: ReportController.index().url,
        icon: BarChart,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
