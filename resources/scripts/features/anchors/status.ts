import { formatDistanceToNowStrict } from 'date-fns'

import type { Anchor } from './types'

export type AnchorTone = 'online' | 'waiting' | 'down'

export interface AnchorStatus {
    tone: AnchorTone
    label: string
    /**
     * The line under the label. Every state has one: for a healthy anchor it
     * is provenance, and for every other state it is the diagnosis, which is
     * why "last seen" lives here rather than in a column of its own -- next to
     * "Online" it says nothing, and next to "Unreachable" it is the whole
     * story.
     */
    detail: string
}

/**
 * Colour on the roster means state and nothing else, so this is the only
 * palette on the page. Amber has no token yet (only `--success` and
 * `--destructive` do), so the waiting tone uses the same literal pair the node
 * cards already use for warnings.
 */
export const toneDotClass: Record<AnchorTone, string> = {
    online: 'bg-success',
    waiting: 'bg-amber-500 dark:bg-amber-400',
    down: 'bg-destructive',
}

const relative = (value: string) =>
    formatDistanceToNowStrict(new Date(value), { addSuffix: true })

const lastSeen = (anchor: Anchor) =>
    anchor.lastSeenAt
        ? `Last seen ${relative(anchor.lastSeenAt)}`
        : 'Has never reported in'

const installProgress = (anchor: Anchor) => {
    if (!anchor.enrollmentExpiresAt) {
        return 'No install command issued yet'
    }

    return new Date(anchor.enrollmentExpiresAt).getTime() < Date.now()
        ? 'Install command expired — reissue it to finish setup'
        : `Install command expires ${relative(anchor.enrollmentExpiresAt)}`
}

/**
 * `AnchorCompatibility` is a lifecycle wearing an attribute's name. The enum
 * stays as it is on the wire; this is what an operator gets asked to read.
 */
const anchorStatus = (anchor: Anchor): AnchorStatus => {
    switch (anchor.compatibility) {
        case 'compatible':
            return { tone: 'online', label: 'Online', detail: lastSeen(anchor) }
        case 'offline':
            return {
                tone: 'down',
                label: 'Unreachable',
                detail: lastSeen(anchor),
            }
        // The version numbers that explain this live under the Protocol marker
        // (AnchorProtocol), where they are one hover away instead of asking
        // every reader to compare two ranges in passing. What belongs here is
        // the same thing every other state reports: when we last heard from it.
        case 'incompatible':
            return {
                tone: 'down',
                label: 'Version mismatch',
                detail: lastSeen(anchor),
            }
        default:
            return {
                tone: 'waiting',
                label: 'Waiting for install',
                detail: installProgress(anchor),
            }
    }
}

export default anchorStatus
