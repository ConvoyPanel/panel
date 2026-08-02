import { cn } from '@/utils'
import type { VariantProps } from 'class-variance-authority'
import type { ComponentProps } from 'react'

import buttonGroupVariants from './ButtonGroup.variants.ts'

/**
 * Joins adjacent controls into one segmented unit — the group owns the shared
 * radius and the collapsed inner border, so members stay plain `Button`s with
 * no per-corner overrides at the call site.
 *
 * Outline members need nothing further; a filled variant reads better with a
 * `ButtonGroupSeparator` between the halves, since there is no border to act
 * as the seam.
 */
const ButtonGroup = ({
    className,
    orientation,
    ...props
}: ComponentProps<'div'> & VariantProps<typeof buttonGroupVariants>) => (
    <div
        role={'group'}
        data-slot={'button-group'}
        data-orientation={orientation}
        className={cn(buttonGroupVariants({ orientation }), className)}
        {...props}
    />
)
ButtonGroup.displayName = 'ButtonGroup'

export default ButtonGroup
