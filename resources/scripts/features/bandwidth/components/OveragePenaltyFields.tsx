import { useFormContext, useWatch } from 'react-hook-form'

import PenaltyActionFields from '@/features/bandwidth/components/PenaltyActionFields.tsx'
import {
    OveragePenalty,
    describePenalty,
} from '@/features/bandwidth/overage-penalty.ts'

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/ToggleGroup'

interface Props {
    /** The tier this field falls back to when left on Inherit. */
    inheritedFrom: OveragePenalty | null
    /** e.g. "global" or "node" — names the tier in the inherit copy. */
    inheritedLabel: string
}

/**
 * Segmented Inherit | Custom control for a quota-overage penalty override.
 * On Inherit the field sends null and shows the resolved effective value; on
 * Custom it reveals the action, plus a rate when the action is a throttle.
 *
 * The global tier can't inherit, so it renders {@see PenaltyActionFields} on its
 * own instead of using this.
 */
const OveragePenaltyFields = ({ inheritedFrom, inheritedLabel }: Props) => {
    const { control } = useFormContext()
    const mode = useWatch({ control, name: 'overagePenaltyMode' })

    return (
        <div className={'space-y-3'}>
            <FormField
                name={'overagePenaltyMode'}
                render={({ field }) => (
                    <FormItem>
                        <FormLabel>Overage penalty</FormLabel>
                        <FormControl>
                            <ToggleGroup
                                variant={'outline'}
                                spacing={0}
                                multiple={false}
                                value={[field.value]}
                                onValueChange={value => {
                                    // Single-select: ignore the empty array a
                                    // second click on the pressed item produces,
                                    // so one segment is always active.
                                    if (value[0]) field.onChange(value[0])
                                }}
                                aria-label={'Overage penalty mode'}
                            >
                                <ToggleGroupItem value={'inherit'}>
                                    Inherit
                                </ToggleGroupItem>
                                <ToggleGroupItem value={'custom'}>
                                    Custom
                                </ToggleGroupItem>
                            </ToggleGroup>
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                )}
            />

            {mode === 'inherit' ? (
                <p className={'text-sm text-muted-foreground'}>
                    Effective: {describePenalty(inheritedFrom)} (from{' '}
                    {inheritedLabel})
                </p>
            ) : (
                <PenaltyActionFields />
            )}
        </div>
    )
}

export default OveragePenaltyFields
