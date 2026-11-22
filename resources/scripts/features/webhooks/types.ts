import type { PaginatedResponse } from '@/lib/api'

export type Webhook = App.Data.Webhook.WebhookData

export type WebhookDelivery = App.Data.Webhook.WebhookDeliveryData

export type WebhookDeliveryStatus = App.Enums.Webhook.WebhookDeliveryStatus

export type PaginatedWebhooks = PaginatedResponse<Webhook>

export type PaginatedWebhookDeliveries = PaginatedResponse<WebhookDelivery>

/** The subscription entry that matches every event. */
export const ALL_EVENTS = '*'

export const deliveryStatusLabels: Record<WebhookDeliveryStatus, string> = {
    pending: 'Pending',
    succeeded: 'Delivered',
    failed: 'Failed',
}

/** What the list and the detail page call a webhook: its description, else its URL. */
export const webhookName = (webhook: Webhook) =>
    webhook.description || webhook.url

/**
 * A compact summary of a subscription, for the list. Entries can be patterns,
 * so they are named rather than counted as events.
 */
export const summarizeEvents = (events: string[]): string => {
    if (events.includes(ALL_EVENTS)) return 'All events'
    if (events.length <= 2) return events.join(', ')

    return `${events[0]} and ${events.length - 1} more`
}

/**
 * Whether a subscription entry covers an event: the exact name, or a pattern
 * with `*` wildcards (`server.*`). Mirrors `WebhookEvents::matches`.
 */
export const entryMatches = (entry: string, event: string): boolean => {
    if (entry === event) return true
    if (!entry.includes('*')) return false

    const pattern = entry
        .split('*')
        .map(part => part.replace(/[.+?^${}()|[\]\\]/g, '\\$&'))
        .join('.*')

    return new RegExp(`^${pattern}$`).test(event)
}

const groupLabels: Record<string, string> = {
    server: 'Servers',
    admin: 'Admin',
    account: 'Accounts',
    auth: 'Authentication',
}

export interface EventGroup {
    /** The first segment of the event names, e.g. `server`. */
    prefix: string
    label: string
    /** The pattern that subscribes to the whole group, e.g. `server.*`. */
    pattern: string
    events: string[]
}

/** Event names grouped by their first segment, in the order the API lists them. */
export const groupEvents = (events: string[]): EventGroup[] => {
    const groups = new Map<string, EventGroup>()

    for (const event of events) {
        const prefix = event.split('.')[0]
        const group = groups.get(prefix) ?? {
            prefix,
            label: groupLabels[prefix] ?? prefix,
            pattern: `${prefix}.*`,
            events: [],
        }

        group.events.push(event)
        groups.set(prefix, group)
    }

    return [...groups.values()]
}
