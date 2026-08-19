import { useServerPresets } from '@/features/servers/presets/api.ts'
import SavePresetDialog from '@/features/servers/presets/components/SavePresetDialog.tsx'
import {
    applyPresetSettings,
    describePresetSettings,
} from '@/features/servers/presets/form-mapping.ts'
import { IconExternalLink } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { useState } from 'react'
import { useFormContext } from 'react-hook-form'

import { buttonVariants } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
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

/**
 * Start from a saved build, or save this one.
 *
 * Applying writes only the settings the preset actually carries, so it can be
 * used on a blank form or over one that is half filled in — the fields a preset
 * says nothing about are left as the operator left them.
 */
const PresetSection = () => {
    const { setValue } = useFormContext()
    const { data: presets, isLoading } = useServerPresets()
    const [applied, setApplied] = useState<string | null>(null)

    const apply = (uuid: string) => {
        const preset = presets?.find(item => item.uuid === uuid)

        if (!preset) return

        applyPresetSettings(preset.settings, setValue)
        setApplied(uuid)

        toast.add({ title: `Applied ${preset.name}`, type: 'success' })
    }

    const items = (presets ?? []).map(preset => ({
        value: preset.uuid,
        label: (
            <span className={'flex flex-col gap-0.5'}>
                <span className={'font-medium'}>{preset.name}</span>
                <span className={'text-muted-foreground text-xs'}>
                    {preset.description ??
                        describePresetSettings(preset.settings)}
                </span>
            </span>
        ),
    }))

    return (
        <Card>
            <CardHeader>
                <CardTitle>Preset</CardTitle>
                <CardDescription>
                    Fill this form from a saved configuration. Identity — name,
                    hostname, VM ID, owner — is never part of one.
                </CardDescription>
                <CardAction>
                    <SavePresetDialog />
                </CardAction>
            </CardHeader>
            <CardContent
                className={'flex flex-wrap items-center gap-x-4 gap-y-2'}
            >
                {isLoading ? (
                    <Skeleton className={'h-8 w-full max-w-sm'} />
                ) : items.length === 0 ? (
                    <p className={'text-muted-foreground text-sm'}>
                        No presets yet. Fill this form in and save it as one.
                    </p>
                ) : (
                    <Select
                        items={items}
                        value={applied}
                        onValueChange={value => value && apply(String(value))}
                    >
                        {/* h-auto so a two-line item does not get clipped in
                            the trigger, matching StoragePicker. */}
                        <SelectTrigger
                            className={
                                'h-auto! w-full max-w-sm text-left *:data-[slot=select-value]:line-clamp-none!'
                            }
                        >
                            <SelectValue placeholder={'Select a preset'} />
                        </SelectTrigger>
                        <SelectContent>
                            {items.map(item => (
                                <SelectItem key={item.value} value={item.value}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                <Link
                    to={'/admin/server-presets'}
                    className={buttonVariants({
                        variant: 'link',
                        size: 'sm',
                    })}
                >
                    Manage presets <IconExternalLink className={'size-4'} />
                </Link>
            </CardContent>
        </Card>
    )
}

export default PresetSection
