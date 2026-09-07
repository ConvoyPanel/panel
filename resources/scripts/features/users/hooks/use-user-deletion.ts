import { useUser as useCurrentUser } from '@/features/auth/api.ts'
import { deleteUser, userQueries } from '@/features/users/api.ts'
import type { AdminUser } from '@/types/admin/user.ts'
import { useMutation } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { toast } from '@/components/ui/Toast'

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

interface Options {
    /** Runs after a successful delete — the detail page leaves for the list, the list stays put. */
    onDeleted?: () => void
}

/**
 * Deleting a user, with the refusals stated in the confirmation rather than by hiding the action.
 *
 * Shared by the list row and the detail page header so the two cannot answer differently. Delete is
 * always offered and the dialog is where it is refused — the same shape the anchors list uses:
 * hiding the item an operator came for leaves them working out why it is missing, whereas asking
 * and then answering "because four servers belong to them" is the same refusal with its reason
 * attached.
 */
const useUserDeletion = ({ onDeleted }: Options = {}) => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: currentUser } = useCurrentUser()

    const { mutate: remove } = useMutation({
        mutationFn: (user: AdminUser) => deleteUser(user.id),
        onSuccess: async () => {
            toast.add({ title: 'User deleted', type: 'success' })
            // A delete changes counts on rows this page cannot see, so this refetches rather than
            // patching the cached page in place.
            await queryClient.invalidateQueries({ queryKey: userQueries.all() })
            onDeleted?.()
        },
        onError: () =>
            toast.add({ title: 'Failed to delete user', type: 'error' }),
    })

    const confirmAndDelete = async (user: AdminUser) => {
        const isSelf = user.id === currentUser?.id
        const owns = user.serversCount > 0

        const confirmed = await confirm({
            title: isSelf
                ? 'You are signed in as this account'
                : owns
                  ? `${user.name} still owns servers`
                  : 'Delete user',
            description: isSelf
                ? 'An account cannot delete itself. Another administrator can remove it for you.'
                : owns
                  ? `${countOf(user.serversCount, 'server')} still belong to this account. Transfer or delete ${user.serversCount === 1 ? 'it' : 'them'} first.`
                  : `Delete ${user.name} (${user.email})? Their API tokens, SSH keys and sessions go with them. This cannot be undone.`,
            confirmText: 'Delete',
            cancelText: isSelf || owns ? 'Close' : 'Cancel',
            confirmButton: {
                variant: 'destructive',
                disabled: isSelf || owns,
            },
        })

        if (confirmed) remove(user)
    }

    return { confirmAndDelete, currentUserId: currentUser?.id }
}

export default useUserDeletion
