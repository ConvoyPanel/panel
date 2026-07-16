import { cn } from '@/utils'

interface Props {
    codes: string[]
    className?: string
}

/**
 * The codes themselves, as a two-column monospace block on a muted panel.
 *
 * Shared by the reveal step of each enable flow and the recovery-codes dialog so
 * a code looks the same wherever it is read from — these get transcribed by hand
 * onto paper, so the tabular figures and the panel are doing real work, not
 * decoration.
 */
const RecoveryCodeList = ({ codes, className }: Props) => (
    <ul
        className={cn(
            'bg-muted/50 grid grid-cols-2 gap-x-4 gap-y-1.5 rounded-md p-4 text-center font-mono text-sm tabular-nums',
            className
        )}
    >
        {codes.map(code => (
            <li key={code} className={'select-all'}>
                {code}
            </li>
        ))}
    </ul>
)

export default RecoveryCodeList
