import { useCallback, useEffect } from 'react'
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
 *
 * Consume a family through `useModal` / `useOpenModal` rather than selecting out
 * of the store by hand.
 */

/**
 * Families whose modals act on a row declare that row's type; families whose
 * modals are plain steps (`enable`, `reset`) declare `void` and are typed to
 * take no payload at all — that is the difference `any` used to paper over.
 */
type CarriesData<TData> = [TData] extends [void] ? false : true

type OpenModal<TData, TId extends string> =
    CarriesData<TData> extends true
        ? (modal: TId, data: TData) => void
        : (modal: TId) => void

export interface ModalState<TData, TId extends string> {
    activeModal: TId | null
    /**
     * The row the active modal was opened for.
     *
     * Survives closing on purpose: the popup plays an exit transition after
     * `open` flips false and still has to render its contents throughout. That
     * is also why this is `TData | null` and never narrows to `TData` — see
     * `useModal`.
     */
    modalData: TData | null
    openModal: OpenModal<TData, TId>
    closeModal: (modal: TId) => void
    /**
     * Internal, for `useModal` only. A store is a module singleton, so it
     * outlives the tree that renders its modals — see `retain`.
     */
    retain: () => () => void
}

export type ModalStore<TData, TId extends string> = UseBoundStore<
    StoreApi<ModalState<TData, TId>>
>

const createModalStore = <TData = void, TId extends string = string>() => {
    // How many modals of this family are currently mounted. Not in the store's
    // state: nothing renders off it, and a re-render per mount would be noise.
    let mounted = 0

    return create<ModalState<TData, TId>>((set, get) => ({
        activeModal: null,
        modalData: null,
        /**
         * Called by every mounted `useModal`. When the last one goes, the family
         * is reset.
         *
         * The store is created at import time and never torn down, but the tree
         * that renders its modals is. Without this, leaving a page with a modal
         * open and coming back re-opened it on its own — `activeModal` had
         * simply never been cleared, so the popup remounted already-open (and,
         * because Base UI seeds `mounted` from `open`, without its enter
         * transition) holding whichever row was selected minutes earlier.
         *
         * No modal of the family mounted means none can be showing, so this is
         * the invariant rather than a guess about who "owns" the family.
         */
        retain: () => {
            mounted++

            return () => {
                mounted--

                // Deferred: React StrictMode runs effect cleanups and re-runs
                // them within the same commit, and a route swap can unmount one
                // modal a tick before mounting the next. Both dip to zero
                // without the family actually going away.
                queueMicrotask(() => {
                    if (mounted === 0) {
                        set({ activeModal: null, modalData: null })
                    }
                })
            }
        },
        // The cast is the price of the conditional signature above; the public
        // surface is exact, and this is the only place that widens it.
        openModal: ((modal: TId, data?: TData) => {
            set(state => ({
                activeModal: modal,
                // Every family that declares a row is now *required* to pass one,
                // so an 'edit' can no longer inherit the previous 'delete's row.
                // The fallback survives only for void families, where it is a
                // no-op either way.
                modalData: data ?? state.modalData,
            }))
        }) as OpenModal<TData, TId>,
        closeModal: (modal: TId) => {
            // Ignore a stale close from a modal that is no longer the active one.
            if (get().activeModal !== modal) return

            set({ activeModal: null })
        },
    }))
}

/**
 * One modal's handle: whether it is open, the row it was opened for, and a close
 * already bound to it.
 *
 * `data` is `TData | null` and deliberately does not narrow when `open` is true.
 * It cannot: the value has to outlive `open` for the exit transition, so it is
 * still needed in renders where `open` is already false.
 *
 * Never resolve that by returning null from the component until data arrives —
 * `openModal` sets the row and the id in one go, so the popup would then mount
 * with `open` already true, and Base UI's `useTransitionStatus` seeds `mounted`
 * from `open`: it would never get `data-starting-style` and never play its enter
 * transition. Instead:
 *
 * - in handlers, guard: `onClick={() => row && submit(row)}`
 * - in a submit fn, `if (!row) return` at the top
 * - in JSX, guard the popup's *contents* and leave the root mounted:
 *   `<Dialog open={open}><Content>{row && <>…</>}</Content></Dialog>`
 */
export const useModal = <TData, TId extends string>(
    store: ModalStore<TData, TId>,
    modal: TId
) => {
    // Three narrow selectors rather than one tuple behind `useShallow`: each
    // already returns either a primitive or a store action that zustand keeps
    // referentially stable, so there is nothing for a shallow compare to do.
    const open = store(state => state.activeModal === modal)
    const data = store(state => state.modalData)
    const closeModal = store(state => state.closeModal)
    const retain = store(state => state.retain)

    // Ties the family's lifetime to the tree rendering it; see `retain`.
    useEffect(() => retain(), [retain])

    const close = useCallback(() => closeModal(modal), [closeModal, modal])

    return { open, data, close }
}

/** The opener for a family, for the row/menu that triggers its modals. */
export const useOpenModal = <TData, TId extends string>(
    store: ModalStore<TData, TId>
) => store(state => state.openModal)

export default createModalStore
