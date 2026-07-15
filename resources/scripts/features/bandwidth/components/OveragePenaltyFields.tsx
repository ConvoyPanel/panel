import { useFormContext, useWatch } from 'react-hook-form'

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
import { InputForm, SelectForm } from '@/components/ui/Forms'
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
 */
const OveragePenaltyFields = ({ inheritedFrom, inheritedLabel }: Props) => {
    const { control } = useFormContext()
    const mode = useWatch({ control, name: 'overagePenaltyMode' })
    const action = useWatch({ control, name: 'overagePenaltyAction' })

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
                <div className={'space-y-3'}>
                    <SelectForm
                        name={'overagePenaltyAction'}
                        label={'Action'}
                        items={[
                            { value: 'throttle', label: 'Throttle to rate' },
                            { value: 'disconnect', label: 'Disconnect NIC' },
                        ]}
                        description={
                            action === 'disconnect'
                                ? 'The guest keeps its NIC but loses carrier until the quota resets. Reversible, but it drops all traffic.'
                                : 'Caps every NIC on the server at the rate below once its quota is used up.'
                        }
                    />

                    {action === 'throttle' && (
                        <InputForm
                            name={'overagePenaltyRate'}
                            label={'Rate (MB/s)'}
                            type={'number'}
                            inputMode={'numeric'}
                        />
                    )}
                </div>
            )}
        </div>
    )
}

export default OveragePenaltyFields
