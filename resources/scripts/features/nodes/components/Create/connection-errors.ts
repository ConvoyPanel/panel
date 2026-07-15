import { ConnectionErrorType } from '@/types/node.ts'

interface ConnectionErrorCopy {
    /** What went wrong, in the admin's terms rather than cURL's. */
    title: string
    /** Why it happened and what to do about it, ideally on this very screen. */
    description: string
}

/**
 * The API already classifies failures (see `App\Enums\Node\Testing\
 * ConnectionErrorCode`), so the UI's job is to turn that classification into
 * something actionable. Each message names the control on this page that fixes
 * it where one exists; the raw cURL text stays available behind the details
 * disclosure for the cases where it genuinely helps.
 */
const CONNECTION_ERROR_COPY: Record<ConnectionErrorType, ConnectionErrorCopy> = {
    [ConnectionErrorType.TlsError]: {
        title: "Couldn't verify the TLS certificate",
        description:
            'Convoy reached the host but could not verify its certificate. Proxmox generates a self-signed certificate when it installs, which is normal on a private network — switch off “Verify TLS certificate” above and test again. On a host that faces the internet, install a trusted certificate instead.',
    },
    [ConnectionErrorType.ConnectionRefused]: {
        title: 'The host refused the connection',
        description:
            'Convoy reached the host, but nothing was listening on that port. Check the port is right — Proxmox uses 8006 by default — and that the host’s firewall allows connections from Convoy.',
    },
    [ConnectionErrorType.Timeout]: {
        title: 'The host did not respond in time',
        description:
            'Convoy could not get a reply before giving up. The host may be powered off or unreachable from this network, or a firewall may be silently dropping the traffic rather than refusing it.',
    },
    [ConnectionErrorType.DnsError]: {
        title: "That hostname doesn't resolve",
        description:
            'Convoy could not look up the FQDN, so it never reached a host. Check it for typos, and make sure the name resolves from wherever Convoy runs — an internal-only name will not resolve from outside your network.',
    },
    [ConnectionErrorType.TokenInvalid]: {
        title: 'Proxmox rejected the API token',
        description:
            'The host answered, but it does not recognise this token. Check the Token ID and Token Secret above. Proxmox shows the secret only once, when the token is created, so if it was not saved you will need to generate a new one.',
    },
    [ConnectionErrorType.TokenMissingPermissions]: {
        title: 'The token lacks the required permissions',
        description:
            'The token is valid, but Proxmox will not let it manage this node. Convoy needs a token with root privileges and privilege separation disabled — set that on the token in Proxmox, then match the switches above.',
    },
    [ConnectionErrorType.Other]: {
        title: 'Convoy could not connect to this host',
        description:
            'The connection failed for a reason Convoy does not recognise. The technical details below are the raw error from the host.',
    },
}

export const connectionErrorCopy = (
    code: ConnectionErrorType | null
): ConnectionErrorCopy =>
    CONNECTION_ERROR_COPY[code ?? ConnectionErrorType.Other] ??
    CONNECTION_ERROR_COPY[ConnectionErrorType.Other]
