import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    Leaf,
    LayoutGrid,
    ShieldCheck,
    Sprout,
} from 'lucide-react';
import { index as gardensIndex } from '@/actions/App/Http/Controllers/Gardens/GardenController';
import { index as moderationIndex } from '@/actions/App/Http/Controllers/Plants/PlantModerationController';
import { index as plantsIndex } from '@/actions/App/Http/Controllers/Plants/PlantSpeciesController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage<{ auth: { canModerateCatalog: boolean } }>();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : '/';

    const currentUrl = page.url;
    const canModerateCatalog = page.props.auth.canModerateCatalog;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Meine Gärten',
            href: gardensIndex().url,
            icon: Sprout,
            isActive: currentUrl.startsWith('/gardens'),
        },
        {
            title: 'Pflanzenkatalog',
            href: plantsIndex().url,
            icon: Leaf,
            isActive:
                currentUrl.startsWith('/plants') &&
                !currentUrl.startsWith('/moderation'),
        },
        ...(canModerateCatalog
            ? [
                  {
                      title: 'Moderierung',
                      href: moderationIndex().url,
                      icon: ShieldCheck,
                      isActive: currentUrl.startsWith('/moderation'),
                  } satisfies NavItem,
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
