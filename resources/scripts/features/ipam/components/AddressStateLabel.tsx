import { Address, AddressState, AddressStateReason } from '@/types/address.ts'
import { cn } from '@/utils'

export type AddressStateKind = 'assigned' | 'reserved' | 'system' | 'available'

/**
 * The four states an operator sees, not the three the column stores: a system reservation is a
 * reserved row the panel made and nobody can release, so it reads as its own thing. Split exactly
 * the way the API counts and filters them.
 */
export const addressStateKind = (address: Address): AddressStateKind => {
    if (address.state === AddressState.Reserved) {
        return address.stateReason === AddressStateReason.System
            ? 'system'
            : 'reserved'
    }

    return address.state === AddressState.Assigned ? 'assigned' : 'available'
}

const TONES: Record<
    AddressStateKind,
    { pip: string; text: string; label: string }
> = {
    assigned: {
        pip: 'bg-address-assigned',
        text: 'text-address-assigned',
        label: 'Assigned',
    },
    reserved: {
        pip: 'bg-address-reserved',
        text: 'text-address-reserved',
        label: 'Reserved',
    },
    /* Neutral on purpose: network, broadcast and gateway are not anybody's decision, and colouring
       them like a hold invites a click that always fails. */
    system: {
        pip: 'bg-address-system',
        text: 'text-muted-foreground',
        label: 'System',
    },
    /* An outline rather than a fill, because free is the absence of a claim — but the ring has to
       be dark enough to actually see at 6px. `ring-border` is the hairline used to separate large
       surfaces and disappears entirely at this size. */
    available: {
        pip: 'bg-background ring-muted-foreground/60 ring-1 ring-inset',
        text: 'text-muted-foreground',
        label: 'Available',
    },
}

interface Props {
    kind: AddressStateKind
    /** Override the word, e.g. a count in the pool card's definition list. */
    children?: React.ReactNode
    className?: string
}

/**
 * A coloured pip plus the word, replacing the grey `Badge` every address column used to be.
 *
 * The colour carries the state and the word carries it again, so the row still reads in greyscale
 * and under colour-blind simulation — and, more prosaically, so the IP is the only mono-weighted
 * thing left in the row and the eye lands on the identity.
 */
const AddressStateLabel = ({ kind, children, className }: Props) => {
    const tone = TONES[kind]

    return (
        <span
            className={cn(
                'inline-flex items-center gap-2 text-sm whitespace-nowrap',
                tone.text,
                className
            )}
        >
            <span
                aria-hidden
                className={cn('size-1.5 shrink-0 rounded-xs', tone.pip)}
            />
            {children ?? tone.label}
        </span>
    )
}

export default AddressStateLabel
