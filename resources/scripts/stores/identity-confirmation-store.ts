import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export enum ConfirmationType {
    Password = 'PASSWORD',
    Passkey = 'PASSKEY',
}

/**
 * Which method the user last confirmed with, so the gate reopens on the tab they
 * actually use. That is all this holds — and being only a UI preference, it is
 * safe to persist wholesale, so the `partialize` cast is gone with it.
 *
 * Whether identity *is* confirmed used to live here too, as a `lastConfirmed`
 * timestamp plus a second copy of the server's five-minute window. One fact with
 * two owners, and it went wrong in both directions: `partialize` dropped the
 * timestamp, so a reload re-prompted while the server still trusted the session
 * for the rest of the window; and nothing re-evaluated it as the window lapsed,
 * since `isIdentityValid()` only recomputed when something else caused a render.
 * RequireIdentityConfirmation is what enforces it, so the server answers it now
 * — see `useIdentityConfirmed` in features/auth/identity/api.ts.
 */
interface IdentityState {
    confirmationType: ConfirmationType
    setConfirmationType: (type: ConfirmationType) => void
}

export const useIdentityConfirmationStore = create(
    persist<IdentityState>(
        set => ({
            confirmationType: ConfirmationType.Password,
            setConfirmationType: type => set({ confirmationType: type }),
        }),
        { name: 'identity-confirmation-store' }
    )
)

export default useIdentityConfirmationStore
