import {
    type FirewallLogLevel,
    type FirewallPolicy,
    type OptionsFormValues,
    firewallQueries,
    updateOptions,
    useFirewallOptions,
    useFirewallRules,
    willBlockSsh,
} from '@/features/servers/firewall/api.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconLock } from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useMemo, useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

const policyItems: { value: FirewallPolicy; label: string }[] = [
    { value: 'ACCEPT', label: 'Accept' },
    { value: 'DROP', label: 'Drop' },
    { value: 'REJECT', label: 'Reject' },
]

/**
 * Logging is presented as one question — which directions to log — rather than
 * two syslog severities, which is not a choice anyone using this page wants to
 * make. The level itself is preserved rather than flattened: an operator who
 * set `debug` from Proxmox keeps it when toggling the other direction.
 */
type LoggingChoice = 'none' | 'inbound' | 'outbound' | 'both'

const loggingItems: { value: LoggingChoice; label: string }[] = [
    { value: 'none', label: "Don't log" },
    { value: 'inbound', label: 'Inbound only' },
    { value: 'outbound', label: 'Outbound only' },
    { value: 'both', label: 'Inbound and outbound' },
]

const DEFAULT_LEVEL: FirewallLogLevel = 'info'

const toChoice = (
    inbound: FirewallLogLevel,
    outbound: FirewallLogLevel
): LoggingChoice => {
    const inOn = inbound !== 'nolog'
    const outOn = outbound !== 'nolog'

    if (inOn && outOn) return 'both'
    if (inOn) return 'inbound'
    if (outOn) return 'outbound'

    return 'none'
}

interface Props {
    uuid: string
}

const FirewallOptionsCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: options, isLoading } = useFirewallOptions(uuid)
    const { data: rules } = useFirewallRules(uuid)

    const [inboundPolicy, setInboundPolicy] = useState<FirewallPolicy>('ACCEPT')
    const [outboundPolicy, setOutboundPolicy] =
        useState<FirewallPolicy>('ACCEPT')
    const [logging, setLogging] = useState<LoggingChoice>('none')

    const reset = () => {
        if (!options) return

        setInboundPolicy(options.inboundPolicy)
        setOutboundPolicy(options.outboundPolicy)
        setLogging(toChoice(options.inboundLogLevel, options.outboundLogLevel))
    }

    useEffect(reset, [options])

    const dirty = useMemo(() => {
        if (!options) return false

        return (
            inboundPolicy !== options.inboundPolicy ||
            outboundPolicy !== options.outboundPolicy ||
            logging !==
                toChoice(options.inboundLogLevel, options.outboundLogLevel)
        )
    }, [options, inboundPolicy, outboundPolicy, logging])

    const { mutate: save, isPending } = useMutation({
        mutationFn: (values: OptionsFormValues) => updateOptions(uuid, values),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: firewallQueries.all(uuid),
            })
            toast.add({ title: 'Firewall updated', type: 'success' })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to update the firewall'),
                type: 'error',
            }),
    })

    const submit = async () => {
        if (!options) return

        // Keep whatever severity is already configured; only decide whether
        // each direction logs at all.
        const inboundLevel =
            options.inboundLogLevel !== 'nolog'
                ? options.inboundLogLevel
                : DEFAULT_LEVEL
        const outboundLevel =
            options.outboundLogLevel !== 'nolog'
                ? options.outboundLogLevel
                : DEFAULT_LEVEL

        if (willBlockSsh(inboundPolicy, rules ?? [])) {
            const confirmed = await confirm({
                title: 'This will cut off SSH',
                description:
                    'No enabled rule allows inbound traffic on port 22, so once the default policy changes you will not be able to reach this server over SSH. The console will still work.',
                confirmText: 'Apply anyway',
                confirmButton: { variant: 'destructive' },
            })

            if (!confirmed) return
        }

        save({
            digest: options.digest,
            inboundPolicy,
            outboundPolicy,
            inboundLogLevel:
                logging === 'inbound' || logging === 'both'
                    ? inboundLevel
                    : 'nolog',
            outboundLogLevel:
                logging === 'outbound' || logging === 'both'
                    ? outboundLevel
                    : 'nolog',
        })
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Firewall</CardTitle>
                <CardDescription>
                    Default behaviour for traffic that no rule below matches.
                </CardDescription>
                {options && (
                    <CardAction>
                        <Badge
                            variant={
                                options.isEnabled ? 'default' : 'secondary'
                            }
                        >
                            {options.isEnabled ? 'Active' : 'Off'}
                        </Badge>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent>
                {isLoading || !options ? (
                    <Skeleton className={'h-20 w-full'} />
                ) : (
                    <dl
                        className={
                            'grid grid-cols-1 gap-4 @md:grid-cols-2 @xl:grid-cols-4'
                        }
                    >
                        <div className={'space-y-1.5'}>
                            <dt
                                className={
                                    'text-xs font-medium text-muted-foreground'
                                }
                            >
                                Inbound traffic
                            </dt>
                            <dd>
                                <Select
                                    items={policyItems}
                                    value={inboundPolicy}
                                    onValueChange={value =>
                                        setInboundPolicy(value as FirewallPolicy)
                                    }
                                >
                                    <SelectTrigger className={'w-full'}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {policyItems.map(item => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </dd>
                        </div>

                        <div className={'space-y-1.5'}>
                            <dt
                                className={
                                    'text-xs font-medium text-muted-foreground'
                                }
                            >
                                Outbound traffic
                            </dt>
                            <dd>
                                <Select
                                    items={policyItems}
                                    value={outboundPolicy}
                                    onValueChange={value =>
                                        setOutboundPolicy(
                                            value as FirewallPolicy
                                        )
                                    }
                                >
                                    <SelectTrigger className={'w-full'}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {policyItems.map(item => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </dd>
                        </div>

                        <div className={'space-y-1.5'}>
                            <dt
                                className={
                                    'text-xs font-medium text-muted-foreground'
                                }
                            >
                                Log dropped packets
                            </dt>
                            <dd>
                                <Select
                                    items={loggingItems}
                                    value={logging}
                                    onValueChange={value =>
                                        setLogging(value as LoggingChoice)
                                    }
                                >
                                    <SelectTrigger className={'w-full'}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {loggingItems.map(item => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </dd>
                        </div>

                        {/* Shown rather than hidden because it is genuinely on
                            and genuinely affects traffic — but the network sync
                            rewrites it on every address change, so offering it
                            as a control would be offering a lie. */}
                        <div className={'space-y-1.5'}>
                            <dt
                                className={
                                    'text-xs font-medium text-muted-foreground'
                                }
                            >
                                IP spoofing protection
                            </dt>
                            <dd className={'flex h-9 items-center gap-2'}>
                                <Badge variant={'secondary'}>
                                    {options.hasIpFilter ? 'On' : 'Off'}
                                </Badge>
                                <span
                                    className={
                                        'inline-flex items-center gap-1 text-xs text-muted-foreground'
                                    }
                                >
                                    <IconLock className={'size-3'} />
                                    Managed by Convoy
                                </span>
                            </dd>
                        </div>
                    </dl>
                )}
            </CardContent>
            {dirty && (
                <CardFooter className={'justify-end gap-3'}>
                    <Button
                        variant={'outline'}
                        onClick={reset}
                        disabled={isPending}
                    >
                        Reset
                    </Button>
                    <Button onClick={submit} loading={isPending}>
                        Save changes
                    </Button>
                </CardFooter>
            )}
        </Card>
    )
}

export default FirewallOptionsCard
