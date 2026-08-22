import { cn } from '@/utils'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { Input } from '@/components/ui/Input'

const PRESETS = [
    { label: 'None', value: '0' },
    { label: '25%', value: '25' },
    { label: '50%', value: '50' },
]

/**
 * Oversubscription as a choice rather than a number field.
 *
 * Three presets cover what operators actually pick, and the number stays
 * reachable behind "Custom" for the fleet that has a policy about it. A bare
 * percentage input made a real decision look like a value to be defaulted past.
 */
const OverallocatePresets = ({ name }: { name: string }) => {
    const [custom, setCustom] = useState(false)

    return (
        <FormField
            name={name}
            render={({ field, formState }) => {
                const value = String(field.value ?? '0')
                const isCustom =
                    custom || !PRESETS.some(preset => preset.value === value)

                return (
                    <FormItem>
                        <FormLabel>Oversubscribe memory by</FormLabel>
                        <FormControl>
                            <div className={'flex flex-wrap gap-1.5'}>
                                {PRESETS.map(preset => (
                                    <Button
                                        key={preset.value}
                                        type={'button'}
                                        variant={
                                            !isCustom && value === preset.value
                                                ? 'default'
                                                : 'outline'
                                        }
                                        disabled={formState.isSubmitting}
                                        onClick={() => {
                                            setCustom(false)
                                            field.onChange(preset.value)
                                        }}
                                    >
                                        {preset.label}
                                    </Button>
                                ))}
                                <Button
                                    type={'button'}
                                    variant={isCustom ? 'default' : 'outline'}
                                    disabled={formState.isSubmitting}
                                    onClick={() => setCustom(true)}
                                >
                                    Custom
                                </Button>
                                <Input
                                    type={'number'}
                                    min={0}
                                    aria-label={'Memory overallocate percent'}
                                    className={cn(
                                        'w-24',
                                        !isCustom && 'hidden'
                                    )}
                                    value={value}
                                    onChange={event =>
                                        field.onChange(event.target.value)
                                    }
                                />
                            </div>
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )
            }}
        />
    )
}

export default OverallocatePresets
