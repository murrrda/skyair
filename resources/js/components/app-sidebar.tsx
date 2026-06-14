import { Link, usePage } from '@inertiajs/react';
import { CalendarClock, Headset, LayoutGrid, Plane, PlaneTakeoff, Tag, Ticket, Users, Warehouse } from 'lucide-react';
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
import type { NavItem } from '@/types';
import type { Auth, UserRole } from '@/types/auth';

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
    { title: 'Kategorije tiketa', href: '/admin/kategorije', icon: Tag },
];

const dispatcherNav: NavItem[] = [
    { title: 'Upravljanje letovima', href: '/dispatcher', icon: PlaneTakeoff },
    { title: 'Dostupnost aviona', href: '/dispatcher/dostupnost-aviona', icon: Warehouse },
    { title: 'Zakaži let', href: '/dispatcher/zakazivanje-leta', icon: CalendarClock },
    { title: 'Šabloni letova', href: '/dispatcher/sabloni', icon: Plane },
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
        case 'dispatcher':
            return dispatcherNav;
        case 'pilot':
        case 'cabin_crew':
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
        case 'dispatcher':
            return '/dispatcher';
        case 'pilot':
        case 'cabin_crew':
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