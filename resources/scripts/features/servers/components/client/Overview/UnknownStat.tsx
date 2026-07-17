import { cn } from '@/utils'

/**
 * What a live tile shows once the state call has failed.
 *
 * These tiles read `useServerState`, which is a live PVE call per request. When
 * a server's node is unreachable that query fails, `data` stays `undefined`, and
 * a `{state ? value : <Skeleton/>}` tile sits on its skeleton *forever* --
 * claiming to still be loading something that is never going to arrive.
 *
 * Unknown is a state, and saying so is the honest answer: the same reason
 * `NodeStatus::UNKNOWN` is first-class rather than folded into `unreachable`.
 * See docs/node-status-plan.md ("Unknown is not stopped").
 */
const UnknownStat = ({ className }: { className?: string }) => (
    <p
        className={cn(
            'text-muted-foreground text-lg font-semibold tracking-tight @sm:text-2xl',
            className
        )}
    >
        Unknown
    </p>
)

export default UnknownStat
