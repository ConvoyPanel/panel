import { useProvisioners } from '@/features/provisioners/api.ts'
import {
    IconArrowDown,
    IconArrowUp,
    IconPlus,
    IconX,
} from '@tabler/icons-react'
import { useState } from 'react'
import { useFormContext, useWatch } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

interface Props {
    name: string
    disabled?: boolean
}

/**
 * Which provisioners an image runs, in order. At most ten, so a list with
 * up and down buttons does what dragging would, without the drag.
 */
const ProvisionerPicker = ({ name, disabled }: Props) => {
    const { setValue } = useFormContext()
    const selected: string[] = useWatch({ name }) ?? []
    const { data: provisioners = [] } = useProvisioners()
    const [adding, setAdding] = useState<string | null>(null)

    const byUuid = new Map(provisioners.map(p => [p.uuid, p]))
    const available = provisioners.filter(p => !selected.includes(p.uuid))

    const set = (next: string[]) =>
        setValue(name, next, { shouldDirty: true, shouldValidate: true })

    const move = (index: number, by: -1 | 1) => {
        const next = [...selected]
        const [item] = next.splice(index, 1)
        next.splice(index + by, 0, item)
        set(next)
    }

    return (
        <FormField
            name={name}
            render={() => (
                <FormItem>
                    <FormLabel>Provisioners</FormLabel>
                    {selected.length > 0 && (
                        <ol className={'flex flex-col gap-2'}>
                            {selected.map((uuid, index) => {
                                const provisioner = byUuid.get(uuid)

                                return (
                                    <li
                                        key={uuid}
                                        className={
                                            'flex items-center gap-2 rounded-lg border py-1 pr-1 pl-3'
                                        }
                                    >
                                        <span
                                            className={
                                                'text-muted-foreground w-4 font-mono text-xs tabular-nums'
                                            }
                                        >
                                            {index + 1}
                                        </span>
                                        <span
                                            className={
                                                'min-w-0 grow truncate text-sm'
                                            }
                                        >
                                            {provisioner?.name ?? uuid}
                                        </span>
                                        <Button
                                            type={'button'}
                                            variant={'ghost'}
                                            size={'icon'}
                                            disabled={disabled || index === 0}
                                            onClick={() => move(index, -1)}
                                            aria-label={`Run ${provisioner?.name ?? 'this'} earlier`}
                                        >
                                            <IconArrowUp className={'size-4'} />
                                        </Button>
                                        <Button
                                            type={'button'}
                                            variant={'ghost'}
                                            size={'icon'}
                                            disabled={
                                                disabled ||
                                                index === selected.length - 1
                                            }
                                            onClick={() => move(index, 1)}
                                            aria-label={`Run ${provisioner?.name ?? 'this'} later`}
                                        >
                                            <IconArrowDown
                                                className={'size-4'}
                                            />
                                        </Button>
                                        <Button
                                            type={'button'}
                                            variant={'ghost'}
                                            size={'icon'}
                                            disabled={disabled}
                                            onClick={() =>
                                                set(
                                                    selected.filter(
                                                        value => value !== uuid
                                                    )
                                                )
                                            }
                                            aria-label={`Remove ${provisioner?.name ?? 'provisioner'}`}
                                        >
                                            <IconX className={'size-4'} />
                                        </Button>
                                    </li>
                                )
                            })}
                        </ol>
                    )}
                    {available.length > 0 && selected.length < 10 && (
                        <div className={'flex gap-2'}>
                            <Select
                                items={available.map(p => ({
                                    value: p.uuid,
                                    label: p.name,
                                }))}
                                modal={false}
                                value={adding}
                                onValueChange={value =>
                                    setAdding(value as string | null)
                                }
                                disabled={disabled}
                            >
                                <SelectTrigger className={'w-full'}>
                                    <SelectValue
                                        placeholder={'Choose a provisioner'}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {available.map(p => (
                                        <SelectItem key={p.uuid} value={p.uuid}>
                                            {p.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type={'button'}
                                variant={'outline'}
                                disabled={disabled || !adding}
                                onClick={() => {
                                    if (!adding) return
                                    set([...selected, adding])
                                    setAdding(null)
                                }}
                            >
                                <IconPlus className={'size-4'} /> Add
                            </Button>
                        </div>
                    )}
                    {provisioners.length === 0 && (
                        <FormDescription>
                            No provisioners yet. Add one under Provisioners.
                        </FormDescription>
                    )}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default ProvisionerPicker
