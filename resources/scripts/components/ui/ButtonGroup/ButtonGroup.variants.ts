import { cva } from 'class-variance-authority'

// Ported by hand from nova's source (registry/base-nova/ui/button-group.tsx);
// `npx shadcn add` would pull new-york + Radix, wrong on both axes. The
// selectors key off `[data-slot]`, which is why Button now sets one.
const buttonGroupVariants = cva(
    "flex w-fit items-stretch has-[>[data-slot=button-group]]:gap-2 has-[select[aria-hidden=true]:last-child]:[&>[data-slot=select-trigger]:last-of-type]:rounded-r-lg *:focus-visible:relative *:focus-visible:z-10 [&>[data-slot=select-trigger]:not([class*='w-'])]:w-fit [&>input]:flex-1",
    {
        variants: {
            orientation: {
                horizontal:
                    '*:data-slot:rounded-r-none [&>[data-slot]:not(:has(~[data-slot]))]:rounded-r-lg! [&>[data-slot]~[data-slot]]:rounded-l-none [&>[data-slot]~[data-slot]]:border-l-0',
                vertical:
                    'flex-col *:data-slot:rounded-b-none [&>[data-slot]:not(:has(~[data-slot]))]:rounded-b-lg! [&>[data-slot]~[data-slot]]:rounded-t-none [&>[data-slot]~[data-slot]]:border-t-0',
            },
        },
        defaultVariants: {
            orientation: 'horizontal',
        },
    }
)

export default buttonGroupVariants
