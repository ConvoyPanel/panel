import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

const Card = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
    ({ className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                // Values from the shadcn create-page default (base + style
                // "nova"): a flat subtle ring instead of border+shadow, and a
                // text-sm base. Not new-york-v4, which deviates.
                //
                // `flex flex-col` is upstream's too — we dropped it when we
                // swapped its `gap-6` for per-part padding, and lost the part
                // that matters: a card in a grid row is stretched to the tallest
                // card beside it, and without a column here its CardContent
                // cannot grow into that height. It sat at its own height under
                // the header with the surplus dead at the bottom, which is why
                // every empty state on /security rendered high rather than
                // centred. Give CardContent `flex-1` to claim the extra space.
                'flex flex-col rounded-xl bg-card text-sm text-card-foreground ring-1 ring-foreground/10',
                className
            )}
            {...props}
        />
    )
)
Card.displayName = 'Card'

export default Card
