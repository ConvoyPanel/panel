import { useHardwareSchema } from '@/features/images/hardware/api'
import { HardwareSchema, PveParameter } from '@/types/image.ts'
import { IconAlertTriangle, IconPlus, IconX } from '@tabler/icons-react'
import { useMemo, useState } from 'react'
import { useFormContext } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

/**
 * The fields worth putting in front of an admin, in the order they matter.
 *
 * Everything else Proxmox accepts is still reachable through the advanced rows
 * below — this is about what the form volunteers, not what it permits.
 */
const PROMOTED = ['bios', 'machine', 'scsihw', 'cpu', 'agent', 'vga', 'boot']

interface Props {
    /** Whose Proxmox to validate against. Null uses the panel's bundled copy. */
    nodeId?: number | null
    ostype: string
}

/**
 * Edits a hardware overlay without ever showing anyone JSON.
 *
 * Every control here is built from the schema Proxmox itself publishes: the
 * options in a select are that node's enum, the help text is Proxmox's own
 * description, and a parameter the running version does not accept never
 * appears. Nothing about Proxmox's rules is restated in this file, so nothing
 * here can drift out of step with them.
 *
 * A field left alone stays *unset* rather than being written with its default.
 * That is the whole contract of an overlay: an untouched field follows the OS
 * default, including when that default later changes.
 */
const HardwareForm = ({ nodeId, ostype }: Props) => {
    const form = useFormContext()
    const { data: schema, isLoading } = useHardwareSchema(nodeId)
    const [showAdvanced, setShowAdvanced] = useState(false)

    const hardware: Record<string, unknown> = form.watch('hardware') ?? {}
    const defaults =
        schema?.defaults?.[ostype] ?? schema?.defaults?.['l26'] ?? {}

    const set = (key: string, value: unknown) => {
        const next = { ...hardware }

        // Empty means "inherit", not "set to empty" — so the key is removed
        // rather than written as a blank, and the OS default applies again.
        if (value === '' || value === undefined || value === null) {
            delete next[key]
        } else {
            next[key] = value
        }

        form.setValue('hardware', next, { shouldDirty: true })
    }

    const promoted = useMemo(
        () =>
            PROMOTED.filter(key => schema?.parameters?.[key]).map(key => ({
                key,
                parameter: schema!.parameters[key],
            })),
        [schema]
    )

    const extra = Object.keys(hardware).filter(
        key => !PROMOTED.includes(key) && !schema?.metaKeys?.includes(key)
    )

    if (isLoading) {
        return (
            <p className={'text-muted-foreground text-sm'}>
                Loading Proxmox settings…
            </p>
        )
    }

    return (
        <div className={'space-y-4'}>
            {schema && schema.nodeId === null && (
                <p
                    className={
                        'text-muted-foreground flex items-start gap-2 text-sm'
                    }
                >
                    <IconAlertTriangle className={'mt-0.5 size-4 shrink-0'} />
                    <span>
                        No node answered, so these are Convoy's bundled Proxmox
                        settings. A node's own schema is more complete and is
                        what the build is finally judged against.
                    </span>
                </p>
            )}

            {promoted.map(({ key, parameter }) => (
                <HardwareField
                    key={key}
                    name={key}
                    parameter={parameter}
                    value={hardware[key]}
                    inherited={defaults[key]}
                    onChange={value => set(key, value)}
                />
            ))}

            {extra.map(key => (
                <HardwareField
                    key={key}
                    name={key}
                    parameter={schema?.parameters?.[key] ?? {}}
                    value={hardware[key]}
                    inherited={defaults[key]}
                    onChange={value => set(key, value)}
                    removable
                />
            ))}

            {showAdvanced ? (
                <AddSetting
                    schema={schema}
                    taken={[...PROMOTED, ...extra]}
                    onAdd={key => {
                        set(key, '')
                        setShowAdvanced(false)
                    }}
                    onCancel={() => setShowAdvanced(false)}
                />
            ) : (
                <Button
                    type={'button'}
                    variant={'secondary'}
                    size={'sm'}
                    onClick={() => setShowAdvanced(true)}
                >
                    <IconPlus className={'size-4'} /> Add another setting
                </Button>
            )}
        </div>
    )
}

interface FieldProps {
    name: string
    parameter: PveParameter
    value: unknown
    inherited: unknown
    onChange: (value: unknown) => void
    removable?: boolean
}

const HardwareField = ({
    name,
    parameter,
    value,
    inherited,
    onChange,
    removable,
}: FieldProps) => {
    const isSet = value !== undefined && value !== null && value !== ''
    const placeholder =
        inherited !== undefined
            ? `Inherited — ${String(inherited)}`
            : parameter.default !== undefined
              ? `Proxmox default — ${String(parameter.default)}`
              : 'Not set'

    return (
        <div className={'space-y-1.5'}>
            <div className={'flex items-center justify-between gap-2'}>
                <Label className={'font-mono text-xs'}>{name}</Label>
                {isSet && (
                    <button
                        type={'button'}
                        className={'text-muted-foreground text-xs underline'}
                        onClick={() => onChange('')}
                    >
                        {removable ? 'Remove' : 'Reset to inherited'}
                    </button>
                )}
            </div>

            {parameter.enum ? (
                <Select
                    value={isSet ? String(value) : ''}
                    onValueChange={onChange}
                >
                    <SelectTrigger>
                        <SelectValue placeholder={placeholder} />
                    </SelectTrigger>
                    <SelectContent>
                        {parameter.enum.map(option => (
                            <SelectItem key={option} value={option}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <Input
                    value={isSet ? String(value) : ''}
                    placeholder={placeholder}
                    onChange={event => onChange(event.target.value)}
                />
            )}

            {parameter.description && (
                <p className={'text-muted-foreground text-xs'}>
                    {parameter.description}
                </p>
            )}
        </div>
    )
}

/**
 * Adds a parameter by picking it, never by typing a key.
 *
 * A misspelled key is the one failure a free-form editor makes easy and this
 * makes impossible: the list is exactly what Proxmox accepts.
 */
const AddSetting = ({
    schema,
    taken,
    onAdd,
    onCancel,
}: {
    schema?: HardwareSchema
    taken: string[]
    onAdd: (key: string) => void
    onCancel: () => void
}) => {
    const options = Object.keys(schema?.parameters ?? {})
        .filter(key => !taken.includes(key))
        .sort()

    return (
        <div className={'flex items-end gap-2'}>
            <div className={'grow space-y-1.5'}>
                <Label>Proxmox setting</Label>
                <Select
                    onValueChange={(value: string | null) =>
                        value && onAdd(value)
                    }
                >
                    <SelectTrigger>
                        <SelectValue placeholder={'Choose a setting…'} />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map(key => (
                            <SelectItem key={key} value={key}>
                                {key}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <Button
                type={'button'}
                variant={'ghost'}
                size={'icon'}
                onClick={onCancel}
                aria-label={'Cancel'}
            >
                <IconX className={'size-4'} />
            </Button>
        </div>
    )
}

export default HardwareForm
