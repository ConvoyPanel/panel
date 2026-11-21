import {
    deleteWebhook,
    rotateWebhookSecret,
    testWebhook,
    webhookQueries,
} from '@/features/webhooks/api.ts'
import { type Webhook, webhookName } from '@/features/webhooks/types.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { toast } from '@/components/ui/Toast'

interface Options {
    /** Runs after a delete lands, e.g. to leave the deleted webhook's page. */
    onDeleted?: () => void
}

/**
 * The row actions a webhook has on both the list and its own page: send a
 * test, rotate the secret, delete. Shared so the two can't word or confirm
 * them differently.
 *
 * A rotation's new secret comes back as `secret`; render it with
 * `WebhookSecretDialog` and call `clearSecret` when it closes.
 */
const useWebhookActions = ({ onDeleted }: Options = {}) => {
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)
    const mutateLists = useQueryMutator(webhookQueries.lists())
    const [secret, setSecret] = useState<string | null>(null)

    // The detail key prefixes the deliveries key, so this refreshes both.
    const refresh = (webhook: Webhook) =>
        Promise.all([
            mutateLists(),
            queryClient.invalidateQueries({
                queryKey: webhookQueries.detail(webhook.uuid).queryKey,
            }),
        ])

    const { mutate: sendTest } = useMutation({
        mutationFn: (webhook: Webhook) => testWebhook(webhook.uuid),
        onSuccess: (_, webhook) => {
            toast.add({ title: 'Test sent', type: 'success' })
            void refresh(webhook)
        },
        onError: () =>
            toast.add({ title: 'Failed to send test', type: 'error' }),
    })

    const { mutate: rotate } = useMutation({
        mutationFn: (webhook: Webhook) => rotateWebhookSecret(webhook.uuid),
        onSuccess: ({ secret: rotated }, webhook) => {
            setSecret(rotated ?? null)
            void refresh(webhook)
        },
        onError: () =>
            toast.add({ title: 'Failed to rotate secret', type: 'error' }),
    })

    const { mutate: remove } = useMutation({
        mutationFn: (webhook: Webhook) => deleteWebhook(webhook.uuid),
        onSuccess: (_, webhook) => {
            queryClient.removeQueries({
                queryKey: webhookQueries.detail(webhook.uuid).queryKey,
            })
            void mutateLists()
            toast.add({ title: 'Webhook deleted', type: 'success' })
            onDeleted?.()
        },
        onError: () =>
            toast.add({ title: 'Failed to delete webhook', type: 'error' }),
    })

    const confirmRotate = async (webhook: Webhook) => {
        const confirmed = await confirm({
            title: `Rotate the secret for ${webhookName(webhook)}?`,
            description:
                'The current secret stops working immediately. Deliveries are signed with the new one from then on.',
            confirmText: 'Rotate secret',
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) rotate(webhook)
    }

    const confirmDelete = async (webhook: Webhook) => {
        const confirmed = await confirm({
            title: `Delete ${webhookName(webhook)}?`,
            description:
                'Deleting this webhook stops all deliveries to it and deletes its delivery history. This can’t be undone.',
            confirmText: 'Delete',
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) remove(webhook)
    }

    return {
        sendTest,
        confirmRotate,
        confirmDelete,
        secret,
        clearSecret: () => setSecret(null),
    }
}

export default useWebhookActions
