import { Link, usePage } from '@inertiajs/react';
import { Headset, LayoutGrid, Plane, Ticket, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import * as employee from '@/routes/employee';
import * as kupac from '@/routes/kupac';
import * as supportTickets from '@/routes/support-tickets';
import * as zaposleniPodrska from '@/routes/zaposleni/podrska';
import type { Auth, UserRole } from '@/types/auth';
import type { NavItem } from '@/types';

const customerNav: NavItem[] = [
    { title: 'Letovi', href: kupac.pretragaLetova().url, icon: Plane },
    { title: 'Moji letovi', href: kupac.mojiLetovi().url, icon: Ticket },
    { title: 'Moji tiketi', href: supportTickets.index().url, icon: Headset },
];

const agentNav: NavItem[] = [
    { title: 'Tiketi podrške', href: zaposleniPodrska.index().url, icon: Headset },
];

const adminNav: NavItem[] = [
    { title: 'Admin panel', href: admin.index().url, icon: LayoutGrid },
    { title: 'Zaposleni', href: admin.employee.index().url, icon: Users },
];

const employeeNav: NavItem[] = [
    { title: 'Moji letovi', href: employee.myFlights().url, icon: Plane },
];

function navForRole(role: UserRole): NavItem[] {
    switch (role) {
        case 'admin':
            return adminNav;
        case 'agent':
            return agentNav;
        case 'pilot':
        case 'cabin_crew':
        case 'dispatcher':
            return employeeNav;
        case 'kupac':
            return customerNav;
        default:
            return [];
    }
}

function homeForRole(role: UserRole): string {
    switch (role) {
        case 'admin':
            return admin.index().url;
        case 'agent':
            return zaposleniPodrska.index().url;
        case 'pilot':
        case 'cabin_crew':
        case 'dispatcher':
            return employee.myFlights().url;
        case 'kupac':
            return kupac.pretragaLetova().url;
        default:
            return '/';
    }
}

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = (auth?.role ?? null) as UserRole;
    const items = navForRole(role);
    const home = homeForRole(role);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={home} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}