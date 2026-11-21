import { preloadWebhook } from '@/features/webhooks/api.ts'
import { processAxiosError } from '@/utils/http.ts'
import { createFileRoute } from '@tanstack/react-router'

/*
 * No `staticData.title`: the parent already contributes "Webhooks" to the
 * breadcrumb, and the webhook's own name is on the page heading.
 */
export const Route = createFileRoute(
    '/_app/admin/_dashboard/webhooks/$webhookUuid'
)({
    loader: ({ params: { webhookUuid } }) =>
        preloadWebhook(webhookUuid).catch(processAxiosError),
})
