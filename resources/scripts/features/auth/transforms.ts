import { AuthenticatedUser } from '@/types/user.ts'

/**
 * Absent capabilities read as unrestricted rather than as denied: every self
 * endpoint sends them, so a missing field means an unexpected payload, and
 * failing open there only shows a control the server would refuse anyway —
 * whereas failing closed would hide the account screen from everyone.
 */
const UNRESTRICTED: App.Data.User.AccountCapabilitiesData = {
    canChangeName: true,
    canChangeEmail: true,
    canChangePassword: true,
    canChangeAvatar: true,
}

export const rawDataToAuthenticatedUser = (data: any): AuthenticatedUser => ({
    id: data.id,
    name: data.name,
    email: data.email,
    avatarUrl: data.avatarUrl ?? null,
    type: data.type ?? 'standard',
    rootAdmin: data.rootAdmin,
    adminRole: data.adminRole ?? null,
    accountCapabilities: data.accountCapabilities ?? UNRESTRICTED,
    // The opposite default to the capabilities above, and deliberately so: a missing capability
    // shows a control the server would refuse, while a missing permission would open an admin
    // section to somebody who cannot reach it.
    adminPermissions: data.adminPermissions ?? [],
})
