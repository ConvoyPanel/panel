import DeliveryStatusBadge from '@/features/webhooks/components/DeliveryStatusBadge.tsx'
import type { WebhookDelivery } from '@/features/webhooks/types.ts'
import { format } from 'date-fns'
import { ReactNode, useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'

interface Props {
    /** The delivery to show, or null when the dialog is closed. */
    delivery: WebhookDelivery | null
    onClose: () => void
    onRedeliver: (delivery: WebhookDelivery) => void
}

const Row = ({ label, children }: { label: string; children: ReactNode }) => (
    <div className={'flex items-baseline justify-between gap-4'}>
        <dt className={'text-muted-foreground text-xs'}>{label}</dt>
        <dd className={'min-w-0 truncate text-right tabular-nums'}>
            {children}
        </dd>
    </div>
)

const Block = ({ label, children }: { label: string; children: ReactNode }) => (
    <div className={'flex flex-col gap-1.5'}>
        <span className={'text-muted-foreground text-xs'}>{label}</span>
        {children}
    </div>
)

const Code = ({ children }: { children: string }) => (
    <pre
        className={
            'bg-muted max-h-72 overflow-auto rounded-md border p-3 font-mono text-xs break-all whitespace-pre-wrap'
        }
    >
        {children}
    </pre>
)

const none = <span className={'text-muted-foreground'}>&mdash;</span>

const DeliveryDialog = ({ delivery, onClose, onRedeliver }: Props) => {
    // Held past close so the contents stay put through the exit animation.
    const [shown, setShown] = useState(delivery)
    if (delivery !== null && delivery !== shown) setShown(delivery)

    return (
        <ResponsiveDialog
            open={delivery !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent className={'sm:max-w-2xl'}>
                {shown && (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle className={'font-mono'}>
                                {shown.event}
                            </ResponsiveDialogTitle>
                        </ResponsiveDialogHeader>
                        <ResponsiveDialogBody className={'flex flex-col gap-4'}>
                            <dl className={'flex flex-col gap-2'}>
                                <Row label={'Status'}>
                                    <DeliveryStatusBadge
                                        status={shown.status}
                                    />
                                </Row>
                                <Row label={'Response'}>
                                    {shown.responseStatus ?? none}
                                </Row>
                                <Row label={'Attempts'}>{shown.attempts}</Row>
                                <Row label={'Created'}>
                                    {format(new Date(shown.createdAt), 'PPpp')}
                                </Row>
                                <Row label={'Delivered'}>
                                    {shown.deliveredAt
                                        ? format(
                                              new Date(shown.deliveredAt),
                                              'PPpp'
                                          )
                                        : none}
                                </Row>
                            </dl>
                            {shown.error && (
                                <Block label={'Error'}>
                                    <p className={'text-destructive text-sm'}>
                                        {shown.error}
                                    </p>
                                </Block>
                            )}
                            <Block label={'Payload'}>
                                <Code>
                                    {JSON.stringify(shown.payload, null, 2)}
                                </Code>
                            </Block>
                            <Block label={'Response body'}>
                                {shown.responseBody ? (
                                    <Code>{shown.responseBody}</Code>
                                ) : (
                                    <span
                                        className={
                                            'text-muted-foreground text-sm'
                                        }
                                    >
                                        None
                                    </span>
                                )}
                            </Block>
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'}>Close</Button>
                                }
                            />
                            <Button
                                disabled={shown.status === 'pending'}
                                onClick={() => onRedeliver(shown)}
                            >
                                Redeliver
                            </Button>
                        </ResponsiveDialogFooter>
                    </>
                )}
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeliveryDialog
