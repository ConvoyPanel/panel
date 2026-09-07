import { cn } from '@/utils'
import * as LabelPrimitive from '@radix-ui/react-label'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import { Label } from '@/components/ui/Label'

import useFormField from './use-form-field'

/*
 * `mono` is the whole typographic weight of the quiet register used on the
 * full-page screens outside the app shell — auth, and anything that follows it.
 * It borrows rather than invents: `text-label` is the existing token (already
 * resolving to --muted-foreground), and `font-mono` is the app's existing mono
 * decision — the system stack, since tailwind.config.cjs only overrides `sans`.
 * The tracking is the one literal the register owns.
 */
const TONES = {
    default: '',
    mono: 'text-label font-mono text-[11px] tracking-[0.08em] uppercase',
} as const

type FormLabelProps = ComponentPropsWithoutRef<typeof LabelPrimitive.Root> & {
    tone?: keyof typeof TONES
}

const FormLabel = forwardRef<
    ElementRef<typeof LabelPrimitive.Root>,
    FormLabelProps
>(({ className, tone = 'default', ...props }, ref) => {
    const { error, formItemId } = useFormField()

    return (
        <Label
            ref={ref}
            data-slot='field-label'
            className={cn(
                'group/field-label peer/field-label flex w-fit gap-2 leading-snug',
                TONES[tone],
                // After the tone, so an invalid field still turns red rather
                // than staying muted because the register set a colour.
                error && 'text-destructive',
                className
            )}
            htmlFor={formItemId}
            {...props}
        />
    )
})
FormLabel.displayName = 'FormLabel'

export default FormLabel
