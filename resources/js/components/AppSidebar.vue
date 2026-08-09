<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    CheckSquare2,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Network,
    Settings2,
    UsersRound,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import type { NavItem } from '@/types';

const page = usePage();
const mainNavItems: NavItem[] = [
    {
        title: 'Áttekintés',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    { title: 'Naptár', href: '/naptar', icon: CalendarDays },
    { title: 'Feladatok', href: '/feladatok', icon: CheckSquare2 },
    { title: 'Kurzusok', href: '/kurzusok', icon: BookOpen },
    { title: 'Dokumentumok', href: '/dokumentumok', icon: FileText },
    { title: 'Szervezet', href: '/szervezet', icon: Network },
    { title: 'Tagi életút', href: '/eletut', icon: GraduationCap },
    { title: 'Alumni és mentorok', href: '/alumni', icon: UsersRound },
];

if ((page.props.auth as any)?.abilities?.isPresident) {
    mainNavItems.push({
        title: 'Adminisztráció',
        href: '/admin',
        icon: Settings2,
    });
}

const footerNavItems: NavItem[] = [
    {
        title: 'FAKT nyilvános oldal',
        href: 'https://www.fakt.org.hu/',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
