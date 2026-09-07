import { cva } from 'class-variance-authority'

// Split out of Input.tsx so a screen can trade the box for a rule without
// forking the control: height, focus ring, file handling, disabled and
// aria-invalid all live in the base and reach both looks at once. Values from
// the create-page default (base + style "nova").
const inputVariants = cva(
    'file:text-foreground placeholder:text-muted-foreground focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 h-8 w-full min-w-0 text-base transition-colors outline-none file:inline-flex file:h-6 file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-3 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:ring-3 md:text-sm',
    {
        variants: {
            variant: {
                // nova ships bg-transparent, which only reads as a field
                // because it assumes a white Card underneath. We put forms on
                // tinted surfaces too, so light mode gets its own fill —
                // mirroring what dark:bg-input/30 already does. Inside a Card
                // this is identical to transparent. See docs/card-design.md.
                default:
                    'border-input focus-visible:border-ring disabled:bg-input/50 aria-invalid:border-destructive dark:bg-input/30 dark:disabled:bg-input/80 dark:aria-invalid:border-destructive/50 rounded-lg border bg-background px-2.5 py-1',
                // The rule is the only thing that changes. `rounded-lg` stays
                // with three borders gone because it is what gives ring-3 a
                // shape to draw — without it the focus state is a hard
                // rectangle that reads as a rendering fault. The invalid state
                // has to colour the bottom border specifically, since there is
                // no longer a border on the other three sides to carry it.
                underline:
                    'border-input focus-visible:border-b-ring aria-invalid:border-b-destructive dark:aria-invalid:border-b-destructive/50 rounded-lg border-b bg-transparent px-1 py-1',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
)

export default inputVariants
