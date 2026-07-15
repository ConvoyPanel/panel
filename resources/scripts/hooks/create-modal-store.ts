import { StoreApi, UseBoundStore, create } from 'zustand'

/**
 * Tracks which modal of a family is open, plus the row it was opened for.
 *
 * Deliberately has no queue, no middleware and no transition delay. The previous
 * version simulated a modal stack: opening a second modal set `activeModal` to
 * null, waited a hardcoded 250ms, then mounted the next — that unmount/remount
 * is what made the backdrop flash, and the delay existed only to hide the seam.
 *
 * Anything that needs one modal *on top of* another is a nested dialog now:
 * render a `<ResponsiveDialog>` inside another dialog's content. Base UI gives
 * the child no backdrop of its own, so the parent stays visible underneath and
 * there is nothing to flash.
 */
export interface ModalState<ModalData, ModalIdentifier extends string> {
    activeModal: ModalIdentifier | null
    modalData: ModalData | null
    openModal: (modal: ModalIdentifier, data?: ModalData) => void
    closeModal: (modal: ModalIdentifier) => void
}

export type ModalStore<
    ModalData,
    ModalIdentifier extends string,
> = UseBoundStore<StoreApi<ModalState<ModalData, ModalIdentifier>>>

const createModalStore = <ModalData, ModalIdentifier extends string>() =>
    create<ModalState<ModalData, ModalIdentifier>>((set, get) => ({
        activeModal: null,
        modalData: null,
        openModal: (modal, data) =>
            set(state => ({
                activeModal: modal,
                modalData: data ?? state.modalData,
            })),
        closeModal: modal => {
            // Ignore a stale close from a modal that is no longer the active one.
            if (get().activeModal !== modal) return

            set({ activeModal: null })
        },
    }))

export default createModalStore
