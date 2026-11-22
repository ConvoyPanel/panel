import {
    type WebhookDeliveryStatus,
    deliveryStatusLabels,
} from '@/features/webhooks/types.ts'

import { Badge, type BadgeProps } from '@/components/ui/Badge.tsx'

const variants: Record<WebhookDeliveryStatus, BadgeProps['variant']> = {
    pending: 'outline',
    succeeded: 'secondary',
    failed: 'destructive',
}

const DeliveryStatusBadge = ({ status }: { status: WebhookDeliveryStatus }) => (
    <Badge variant={variants[status]} className={'whitespace-nowrap'}>
        {deliveryStatusLabels[status]}
    </Badge>
)

export default DeliveryStatusBadge
