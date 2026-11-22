import {
    ALL_EVENTS,
    type PaginatedWebhookDeliveries,
    type PaginatedWebhooks,
    type Webhook,
    type WebhookDelivery,
} from '@/features/webhooks/types.ts'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http.ts'
import WebhookController from '@/wayfinder/actions/App/Http/Controllers/Admin/WebhookController'
import WebhookDeliveryController from '@/wayfinder/actions/App/Http/Controllers/Admin/WebhookDeliveryController'
import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, type PaginatedResponse, apiFetch } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'

// Both controllers are served on the panel (`/api/admin`) and the Application
// token API (`/api/application`), so Wayfinder emits URI-keyed dictionaries
// instead of callables — the panel references the admin routes.
const eventsRoute = WebhookController.events['/api/admin/webhooks/events']
const indexRoute = WebhookController.index['/api/admin/webhooks']
const storeRoute = WebhookController.store['/api/admin/webhooks']
const showRoute = WebhookController.show['/api/admin/webhooks/{webhook}']
const updateRoute = WebhookController.update['/api/admin/webhooks/{webhook}']
const destroyRoute = WebhookController.destroy['/api/admin/webhooks/{webhook}']
const testRoute = WebhookController.test['/api/admin/webhooks/{webhook}/test']
const deliveriesRoute =
    WebhookDeliveryController.index['/api/admin/webhooks/{webhook}/deliveries']
const redeliverRoute =
    WebhookDeliveryController.redeliver[
        '/api/admin/webhooks/{webhook}/deliveries/{delivery}/redeliver'
    ]

export type WebhookQueryParams = QueryBuilderParams

export type WebhookDeliveryQueryParams = QueryBuilderParams<'status' | 'event'>

const isHttpUrl = (value: string) => {
    try {
        const { protocol } = new URL(value)

        return protocol === 'http:' || protocol === 'https:'
    } catch {
        return false
    }
}

export const webhookSchema = z
    .object({
        url: z
            .string()
            .trim()
            .min(1, 'Required')
            .max(2048, 'Must be at most 2048 characters')
            .refine(
                isHttpUrl,
                'Enter a URL that starts with http:// or https://'
            ),
        description: z
            .string()
            .trim()
            .max(191, 'Must be at most 191 characters'),
        allEvents: z.boolean(),
        events: z.array(z.string()),
        enabled: z.boolean(),
    })
    .refine(data => data.allEvents || data.events.length > 0, {
        message: 'Pick at least one event.',
        path: ['events'],
    })

export type WebhookInput = z.infer<typeof webhookSchema>

const payload = ({
    url,
    description,
    allEvents,
    events,
    enabled,
}: WebhookInput) => ({
    url,
    description: description === '' ? null : description,
    events: allEvents ? [ALL_EVENTS] : events,
    enabled,
})

const getWebhookEvents = async (): Promise<string[]> =>
    (await apiFetch<DataResponse<string[]>>(eventsRoute())).data

export const getWebhooks = async (
    params: WebhookQueryParams
): Promise<PaginatedWebhooks> => {
    const res = await apiFetch<PaginatedResponse<Webhook>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return { items: res.items, pagination: res.pagination }
}

const getWebhook = async (uuid: string): Promise<Webhook> =>
    (await apiFetch<DataResponse<Webhook>>(showRoute(uuid))).data

const getWebhookDeliveries = async (
    uuid: string,
    params: WebhookDeliveryQueryParams
): Promise<PaginatedWebhookDeliveries> => {
    const res = await apiFetch<PaginatedResponse<WebhookDelivery>>(
        deliveriesRoute(uuid),
        { params: withQueryBuilderParams(params) }
    )

    return { items: res.items, pagination: res.pagination }
}

/** Creates a webhook and returns it with its signing secret, shown only this once. */
export const createWebhook = async (input: WebhookInput): Promise<Webhook> =>
    (
        await apiFetch<DataResponse<Webhook>>(storeRoute(), {
            body: payload(input),
        })
    ).data

export const updateWebhook = async (
    uuid: string,
    input: WebhookInput
): Promise<Webhook> =>
    (
        await apiFetch<DataResponse<Webhook>>(updateRoute(uuid), {
            body: payload(input),
        })
    ).data

/** Issues a new signing secret and returns the webhook with it. The old one stops working at once. */
export const rotateWebhookSecret = async (uuid: string): Promise<Webhook> =>
    (
        await apiFetch<DataResponse<Webhook>>(updateRoute(uuid), {
            body: { rotate_secret: true },
        })
    ).data

export const deleteWebhook = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}

/** Sends a `webhook.ping` to this webhook alone. */
export const testWebhook = async (uuid: string): Promise<WebhookDelivery> =>
    (await apiFetch<DataResponse<WebhookDelivery>>(testRoute(uuid))).data

export const redeliverWebhookDelivery = async (
    uuid: string,
    deliveryUuid: string
): Promise<WebhookDelivery> =>
    (
        await apiFetch<DataResponse<WebhookDelivery>>(
            redeliverRoute([uuid, deliveryUuid])
        )
    ).data

export const webhookQueries = {
    all: () => ['admin', 'webhooks'] as const,
    lists: () => [...webhookQueries.all(), 'list'] as const,
    list: (params: WebhookQueryParams) =>
        queryOptions({
            queryKey: [...webhookQueries.lists(), params] as const,
            queryFn: () => getWebhooks(params),
            placeholderData: keepPreviousData,
        }),
    // The catalogue only changes with a release, so it is fetched once per session.
    events: () =>
        queryOptions({
            queryKey: [...webhookQueries.all(), 'events'] as const,
            queryFn: getWebhookEvents,
            staleTime: Infinity,
        }),
    details: () => [...webhookQueries.all(), 'detail'] as const,
    detail: (uuid: string) =>
        queryOptions({
            queryKey: [...webhookQueries.details(), uuid] as const,
            queryFn: () => getWebhook(uuid),
        }),
    deliveries: (uuid: string, params: WebhookDeliveryQueryParams) =>
        queryOptions({
            queryKey: [
                ...webhookQueries.detail(uuid).queryKey,
                'deliveries',
                params,
            ] as const,
            queryFn: () => getWebhookDeliveries(uuid, params),
            placeholderData: keepPreviousData,
            // A pending delivery is still being tried, so its row is worth
            // watching until it settles.
            refetchInterval: query =>
                query.state.data?.items.some(
                    delivery => delivery.status === 'pending'
                )
                    ? 10_000
                    : false,
        }),
}

export const useWebhooks = (params: WebhookQueryParams) =>
    useQuery(webhookQueries.list(params))

export const useWebhook = (uuid: string) =>
    useQuery(webhookQueries.detail(uuid))

export const useWebhookEvents = () => useQuery(webhookQueries.events())

/** Loads the webhook before its page renders, so a 404 can become the not-found page. */
export const preloadWebhook = (uuid: string) =>
    queryClient.ensureQueryData(webhookQueries.detail(uuid))

export const useWebhookDeliveries = (
    uuid: string,
    params: WebhookDeliveryQueryParams
) => useQuery(webhookQueries.deliveries(uuid, params))
