import { cva } from 'class-variance-authority'

// Base UI drives enter/exit from data-starting-style/data-ending-style; Radix's
// data-[state=open]/[state=closed] does not exist here and would silently never
// match. Slide offsets are plain translates so both phases share one rule.
const sheetVariants = cva(
    'fixed z-50 gap-4 bg-background p-6 shadow-lg transition-transform duration-300 ease-in-out motion-reduce:transition-none',
    {
        variants: {
            side: {
                top: 'inset-x-0 top-0 border-b data-ending-style:-translate-y-full data-starting-style:-translate-y-full',
                bottom: 'inset-x-0 bottom-0 border-t data-ending-style:translate-y-full data-starting-style:translate-y-full',
                left: 'inset-y-0 left-0 h-full w-3/4 border-r data-ending-style:-translate-x-full data-starting-style:-translate-x-full sm:max-w-sm',
                right: 'inset-y-0 right-0 h-full w-3/4 border-l data-ending-style:translate-x-full data-starting-style:translate-x-full sm:max-w-sm',
            },
        },
        defaultVariants: {
            side: 'right',
        },
    }
)

export default sheetVariants
