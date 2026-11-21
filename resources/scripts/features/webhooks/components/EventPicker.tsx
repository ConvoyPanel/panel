import { useWebhookEvents } from '@/features/webhooks/api.ts'
import {
    type EventGroup,
    entryMatches,
    groupEvents,
} from '@/features/webhooks/types.ts'
import { useMemo } from 'react'

import { Checkbox } from '@/components/ui/Checkbox'
import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    /** Subscription entries: exact event names, or patterns such as `server.*`. */
    value: string[]
    onChange: (value: string[]) => void
    disabled?: boolean
}

const EventCheckbox = ({
    label,
    checked,
    indeterminate,
    disabled,
    onCheckedChange,
}: {
    label: string
    checked: boolean
    indeterminate?: boolean
    disabled?: boolean
    onCheckedChange: (checked: boolean) => void
}) => (
    <label className={'flex min-w-0 items-center gap-2'}>
        <Checkbox
            checked={checked}
            indeterminate={indeterminate}
            disabled={disabled}
            onCheckedChange={next => onCheckedChange(next === true)}
        />
        <span className={'truncate font-mono text-xs'} title={label}>
            {label}
        </span>
    </label>
)

/**
 * The events a webhook subscribes to, grouped by their first segment. Ticking a
 * group subscribes to its pattern (`server.*`), so events added to it later are
 * included too.
 */
const EventPicker = ({ value, onChange, disabled }: Props) => {
    const { data: catalogue, isLoading, isError, refetch } = useWebhookEvents()

    const groups = useMemo(() => groupEvents(catalogue ?? []), [catalogue])

    if (isError && !catalogue) {
        return (
            <CollectionErrorState
                className={'rounded-lg border'}
                onRetry={refetch}
            />
        )
    }

    if (isLoading || !catalogue) {
        return <Skeleton className={'h-40 w-full'} />
    }

    const groupPatterns = groups.map(group => group.pattern)

    // Entries the groups can't show: a narrower pattern like `admin.server.*`
    // set through the API, or an event a later release no longer sends. Kept
    // as their own rows so saving the form doesn't drop them silently.
    const other = value.filter(
        entry => !catalogue.includes(entry) && !groupPatterns.includes(entry)
    )

    const coveringPattern = (event: string) =>
        value.find(entry => entry !== event && entryMatches(entry, event))

    const toggleEvent = (event: string, checked: boolean) => {
        if (checked) {
            onChange([...value, event])

            return
        }

        const group = groups.find(group => group.events.includes(event))

        // Unticking one event of a ticked group spells the group out without it.
        if (group && value.includes(group.pattern)) {
            onChange([
                ...value.filter(
                    entry =>
                        entry !== group.pattern && !group.events.includes(entry)
                ),
                ...group.events.filter(name => name !== event),
            ])

            return
        }

        onChange(value.filter(entry => entry !== event))
    }

    const toggleGroup = (group: EventGroup, checked: boolean) => {
        const rest = value.filter(
            entry => entry !== group.pattern && !group.events.includes(entry)
        )

        onChange(checked ? [...rest, group.pattern] : rest)
    }

    return (
        <div className={'flex flex-col gap-2'}>
            {other.length > 0 && (
                <div
                    className={
                        'grid gap-2 rounded-lg border p-3 @md:grid-cols-2'
                    }
                >
                    {other.map(entry => (
                        <EventCheckbox
                            key={entry}
                            label={entry}
                            checked
                            disabled={disabled}
                            onCheckedChange={() =>
                                onChange(value.filter(item => item !== entry))
                            }
                        />
                    ))}
                </div>
            )}
            {groups.map(group => {
                const wholeGroup = value.includes(group.pattern)
                const selected = group.events.filter(
                    event => value.includes(event) || !!coveringPattern(event)
                ).length

                return (
                    <Collapsible
                        key={group.prefix}
                        className={'rounded-lg border'}
                    >
                        <div className={'flex items-center gap-3 px-3 py-2'}>
                            <Checkbox
                                aria-label={`All ${group.label.toLowerCase()} events`}
                                checked={wholeGroup}
                                indeterminate={!wholeGroup && selected > 0}
                                disabled={disabled}
                                onCheckedChange={checked =>
                                    toggleGroup(group, checked === true)
                                }
                            />
                            <CollapsibleTrigger
                                className={'text-foreground min-w-0 flex-1'}
                            >
                                <span className={'truncate'}>
                                    {group.label}
                                </span>
                                <span
                                    className={
                                        'text-muted-foreground ml-auto text-xs font-normal tabular-nums'
                                    }
                                >
                                    {selected}/{group.events.length}
                                </span>
                            </CollapsibleTrigger>
                        </div>
                        <CollapsiblePanel>
                            <div
                                className={
                                    'grid gap-2 border-t p-3 @md:grid-cols-2'
                                }
                            >
                                {group.events.map(event => {
                                    const pattern = coveringPattern(event)

                                    return (
                                        <EventCheckbox
                                            key={event}
                                            label={event}
                                            checked={
                                                value.includes(event) ||
                                                !!pattern
                                            }
                                            // A narrower pattern can't be split
                                            // here; untick it above instead.
                                            disabled={
                                                disabled ||
                                                (!!pattern &&
                                                    pattern !== group.pattern)
                                            }
                                            onCheckedChange={checked =>
                                                toggleEvent(event, checked)
                                            }
                                        />
                                    )
                                })}
                            </div>
                        </CollapsiblePanel>
                    </Collapsible>
                )
            })}
        </div>
    )
}

export default EventPicker
