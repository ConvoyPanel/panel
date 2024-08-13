import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SidebarState {
    keepExpanded: boolean
    setKeepExpanded: (keepExpanded: boolean) => void
    expanded: boolean
    setExpanded: (expanded: boolean) => void
}

const useSidebarStore = create(
    persist<SidebarState>(
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

export default useSidebarStore
