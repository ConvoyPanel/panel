import http from '@/api/http'

export type AnnouncementType = 'security' | 'release' | 'maintenance' | 'info'

export type AnnouncementSeverity = 'critical' | 'high' | 'medium' | 'low'

export interface VersionRange {
    from?: string
    before?: string
}

export interface Announcement {
    id: string
    type: AnnouncementType
    severity: AnnouncementSeverity | null
    title: string
    body: string
    url: string
    affects: {
        panel: VersionRange[] | null
        php: VersionRange[] | null
    }
    publishedAt: Date
    expiresAt: Date | null
}

export interface AdminAnnouncements {
    /** Critical and applying to this panel: a banner on every page, never dismissable. */
    banner: Announcement[]
    /** Everything else that applies and this admin hasn't dismissed. */
    overview: Announcement[]
    /** Whether this admin still has to see the notice that telemetry is on. */
    telemetryNotice: boolean
}

export const rawDataToAnnouncement = (data: any): Announcement => ({
    id: data.id,
    type: data.type,
    severity: data.severity ?? null,
    title: data.title,
    body: data.body,
    url: data.url,
    affects: {
        panel: data.affects?.panel ?? null,
        php: data.affects?.php ?? null,
    },
    publishedAt: new Date(data.published_at),
    expiresAt: data.expires_at ? new Date(data.expires_at) : null,
})

const getAnnouncements = async (): Promise<AdminAnnouncements> => {
    const {
        data: { data },
    } = await http.get('/api/admin/announcements')

    return {
        banner: data.banner.map(rawDataToAnnouncement),
        overview: data.overview.map(rawDataToAnnouncement),
        telemetryNotice: Boolean(data.telemetry_notice),
    }
}

export default getAnnouncements
