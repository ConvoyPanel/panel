import { create } from 'zustand'

import {
    ConfirmOptions,
    ConfirmationType,
} from '@/components/ui/AlertDialog/ConfirmDialog.types.ts'

interface ConfirmationState {
    isOpen: boolean
    confirm: ConfirmationType
    options: Partial<ConfirmOptions>
    resolver: (value: boolean) => void
    setIsOpen: (isOpen: boolean) => void
    handleConfirm: () => void
    handleCancel: () => void
}

const useConfirmationStore = create<ConfirmationState>((set, get) => ({
    confirm: (options: ConfirmOptions) => {
        set({
            options,
            isOpen: true,
        })

        return new Promise<boolean>(resolve => {
            set({ resolver: resolve })
        })
    },
    options: {},
    resolver: () => {},
    isOpen: false,
    setIsOpen: isOpen => set({ isOpen }),
    handleConfirm: () => {
        set({ isOpen: false })
        get().resolver(true)
    },
    handleCancel: () => {
        set({ isOpen: false })
        get().resolver(false)
    },
}))

export default useConfirmationStore
