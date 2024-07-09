import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SidebarStore {
    keepExpanded: boolean
    setKeepExpanded: (keepExpanded: boolean) => void
    expanded: boolean
    setExpanded: (expanded: boolean) => void
}

export const useSidebarStore = create(
    persist<SidebarStore>(
        set => ({
            keepExpanded: false,
            setKeepExpanded: (keepExpanded: boolean) => set({ keepExpanded }),
            expanded: false,
            setExpanded: (expanded: boolean) => set({ expanded }),
        }),
        {
            name: 'sidebar-state',
        }
    )
)
