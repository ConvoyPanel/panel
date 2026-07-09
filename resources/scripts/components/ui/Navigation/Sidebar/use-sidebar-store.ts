import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SidebarState {
    /** User pref: keep the sidebar collapsed to an icon rail. Persisted. */
    collapsed: boolean
    toggleCollapsed: () => void
    setCollapsed: (value: boolean) => void
    /** Depth of the last-rendered nav, used to pick the drill transition direction. */
    lastDepth: number
    setLastDepth: (value: number) => void
    /**
     * Whether the collapsed rail is currently hovered (temporarily expanded).
     * Kept in the store (not local state) so it survives the layout remount on
     * navigation — otherwise clicking a link while hovering snaps the rail shut.
     */
    railHovered: boolean
    setRailHovered: (value: boolean) => void
}

export const useSidebarStore = create(
    persist<SidebarState>(
        set => ({
            collapsed: false,
            toggleCollapsed: () => set(state => ({ collapsed: !state.collapsed })),
            setCollapsed: value => set({ collapsed: value }),
            lastDepth: 0,
            setLastDepth: value => set({ lastDepth: value }),
            railHovered: false,
            setRailHovered: value => set({ railHovered: value }),
        }),
        {
            name: 'sidebar-store',
            // @ts-expect-error only the collapse pref should survive reloads
            partialize: state => ({ collapsed: state.collapsed }),
        }
    )
)

export default useSidebarStore
