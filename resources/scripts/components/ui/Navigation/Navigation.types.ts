import { LinkOptions } from '@tanstack/react-router'

import { TablerIcon } from '@/lib/tabler.ts'

export interface Route {
    icon: TablerIcon
    label: string
    path: string
    activeOptions?: LinkOptions['activeOptions']
    /** Small pill next to the label, e.g. "Beta" / "New". */
    badge?: string
    /** Render a trailing chevron to signal this item drills into a deeper nav. */
    drilldown?: boolean
}

/** A titled (or untitled) block of nav links. */
export interface NavGroup {
    /** Muted caps section header. Omit for the top, header-less group. */
    label?: string
    items: Route[]
}

/** A "back" affordance rendered at the top of a drilled-in nav (Vercel style). */
export interface SidebarBack {
    /** e.g. "Servers" → renders "‹ Servers". */
    label: string
    to: string
}

/** Entity context header shown when drilled into an entity (e.g. a server). */
export interface SidebarContext {
    title: string
    subtitle?: string
    icon?: TablerIcon
}

/**
 * The full sidebar navigation model. Each distinct nav (client root, a specific
 * server, admin, …) carries a stable `key` so the sidebar can animate the swap
 * when you drill between depths.
 */
export interface SidebarNav {
    key: string
    back?: SidebarBack
    context?: SidebarContext
    groups: NavGroup[]
}

/** Callers may still pass a flat list; it becomes a single header-less group. */
export type SidebarNavInput = SidebarNav | Route[]

export function normalizeNav(input: SidebarNavInput): SidebarNav {
    if (Array.isArray(input)) {
        return { key: 'default', groups: [{ items: input }] }
    }

    return input
}

/** Flatten every link across groups (mobile sheet, breadcrumb lookups, …). */
export function flattenNav(nav: SidebarNav): Route[] {
    return nav.groups.flatMap(group => group.items)
}
