import { userQueries } from '@/features/users/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useMutation } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { toast } from '@/components/ui/Toast'

interface Args<T> {
    /** The cached list this credential lives in, so the row can leave without a refetch. */
    queryKey: readonly unknown[]
    remove: (item: T) => Promise<void>
    /*
     * Every string is authored rather than derived from one noun. Case-folding a noun to build a
     * sentence works until the noun is an initialism: `'SSH key'.toLowerCase()` asks the operator
     * to confirm they want to "Revoke ssh key?".
     */
    question: string
    successTitle: string
    errorTitle: string
    nameOf: (item: T) => string
    /** What the account loses, said plainly in the confirmation. */
    consequence: string
    /** The button says what it does — an OAuth provider is unlinked, not revoked. */
    confirmText?: string
}

/**
 * Confirm, revoke, drop the row — shared by the four credential cards so that taking away an SSH
 * key and taking away a passkey behave and read the same way.
 *
 * Revoking is destructive and immediate, so it always asks first. There is no undo: a passkey
 * cannot be put back by an admin at all, and the account has to enrol a new one.
 */
const useCredentialRevocation = <T extends { id: number }>({
    queryKey,
    remove,
    question,
    successTitle,
    errorTitle,
    nameOf,
    consequence,
    confirmText = 'Revoke',
}: Args<T>) => {
    const confirm = useConfirmationStore(state => state.confirm)
    const mutateCache = useQueryMutator<T[]>(queryKey)

    const { mutate } = useMutation({
        mutationFn: remove,
        onSuccess: (_, item) => {
            mutateCache(items => items?.filter(entry => entry.id !== item.id))
            toast.add({ title: successTitle, type: 'success' })
            // The detail payload carries these as counts, and the header is on the same screen.
            void queryClient.invalidateQueries({ queryKey: userQueries.all() })
        },
        onError: () => toast.add({ title: errorTitle, type: 'error' }),
    })

    return async (item: T) => {
        const confirmed = await confirm({
            title: question,
            description: `${nameOf(item)} will stop working immediately. ${consequence}`,
            confirmText,
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) mutate(item)
    }
}

export default useCredentialRevocation
