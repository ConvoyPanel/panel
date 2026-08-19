import { ReactNode } from 'react'

interface Props {
    icon: ReactNode
    title: string
    /** Right-aligned slot for a live readout — a computed total, a tag. */
    aside?: ReactNode
}

/**
 * The small icon'd heading that separates groups of fields *inside* one card —
 * Processor and Memory on a node's capacity card, Compute and Backups on the
 * server create form. A card gets a `CardTitle`; the groups within it get one
 * of these, so a long form reads as a few labelled blocks rather than a wall of
 * inputs.
 */
const GroupHeader = ({ icon, title, aside }: Props) => (
    <div className={'mb-3 flex items-center gap-2'}>
        <span
            className={
                'bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md'
            }
        >
            {icon}
        </span>
        <span className={'text-sm font-semibold'}>{title}</span>
        {aside && <div className={'ml-auto'}>{aside}</div>}
    </div>
)

export default GroupHeader
