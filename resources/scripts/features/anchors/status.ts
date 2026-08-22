import { formatDistanceToNowStrict } from 'date-fns'

export type AnchorTone = 'online' | 'waiting' | 'down'

export interface AnchorStatus {
    tone: AnchorTone
    label: string
    /**
     * The line under the label. Every state has one: for a healthy installation
     * it is provenance, and for every other state it is the diagnosis, which is
     * why "last seen" lives here rather than in a column of its own -- next to
     * "Online" it says nothing, and next to "Unreachable" it is the whole story.
     */
    detail: string
}

/**
 * Colour means state and nothing else, so this is the only palette on the page.
 * Amber has no token yet (only `--success` and `--destructive` do), so the
 * waiting tone uses the same literal pair the node cards already use.
 */
export const toneDotClass: Record<AnchorTone, string> = {
    online: 'bg-success',
    waiting: 'bg-amber-500 dark:bg-amber-400',
    down: 'bg-destructive',
}

const relative = (value: string) =>
    formatDistanceToNowStrict(new Date(value), { addSuffix: true })

/**
 * Reads any Anchor installation: a node's agent, a relay, or a machine still
 * waiting in the queue. They answer the same question, so they get the same
 * vocabulary.
 */
export interface AnchorLike {
    compatibility: App.Enums.Anchor.AnchorCompatibility | null
    lastSeenAt: string | null
}

const anchorStatus = (anchor: AnchorLike): AnchorStatus => {
    const lastSeen = anchor.lastSeenAt
        ? `Last seen ${relative(anchor.lastSeenAt)}`
        : 'Has never reported in'

    switch (anchor.compatibility) {
        case 'compatible':
            return { tone: 'online', label: 'Online', detail: lastSeen }
        case 'offline':
            return { tone: 'down', label: 'Unreachable', detail: lastSeen }
        // The version numbers that explain this live under the Protocol marker
        // (AnchorProtocol), where they are one hover away instead of asking
        // every reader to compare two ranges in passing.
        case 'incompatible':
            return { tone: 'down', label: 'Version mismatch', detail: lastSeen }
        case 'unenrolled':
            return {
                tone: 'waiting',
                label: 'Waiting for install',
                detail: 'No agent has enrolled on this host yet',
            }
        default:
            // Null: a node with no agent installed. The v4 shape, and not a
            // fault -- everything except the console works without one.
            return {
                tone: 'waiting',
                label: 'No agent',
                detail: 'Console and template installs are unavailable',
            }
    }
}

export default anchorStatus
