import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SidebarState {
    /** User pref: keep the sidebar collapsed to an icon rail. Persisted. */
    collapsed: boolean
    toggleCollapsed: () => void
    setCollapsed: (value: boolean) => void
}

export const useSidebarStore = create(
    persist<SidebarState>(
        set => ({
            collapsed: false,
            toggleCollapsed: () => set(state => ({ collapsed: !state.collapsed })),
            setCollapsed: value => set({ collapsed: value }),
        }),
        {
            name: 'sidebar-store',
        }
    )
)

export default useSidebarStore
