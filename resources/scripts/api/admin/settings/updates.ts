import useSWR from 'swr'

import {
    Announcement,
    rawDataToAnnouncement,
} from '@/api/admin/announcements/getAnnouncements'
import http from '@/api/http'

export interface UpdatesAnnouncement extends Announcement {
    applies: boolean
    /** One per check; `matches` says whether it holds for this panel. */
    reasons: { text: string; matches: boolean }[]
    dismissed: boolean
}

export interface Updates {
    panel: {
        version: string
        php: string
        fromSource: boolean
    }
    release: {
        enabled: boolean
        latest: {
            version: string
            url: string
            publishedAt: Date | null
            checkedAt: Date
        } | null
        updateAvailable: boolean | null
    }
    feed: {
        enabled: boolean
        generatedAt: Date | null
        checkedAt: Date | null
        error: { message: string; at: Date } | null
    }
    announcements: UpdatesAnnouncement[]
}

const rawDataToUpdates = (data: any): Updates => ({
    panel: {
        version: data.panel.version,
        php: data.panel.php,
        fromSource: data.panel.from_source,
    },
    release: {
        enabled: data.release.enabled,
        latest: data.release.latest
            ? {
                  version: data.release.latest.version,
                  url: data.release.latest.url,
                  publishedAt: data.release.latest.published_at
                      ? new Date(data.release.latest.published_at)
                      : null,
                  checkedAt: new Date(data.release.latest.checked_at),
              }
            : null,
        updateAvailable: data.release.update_available,
    },
    feed: {
        enabled: data.feed.enabled,
        generatedAt: data.feed.generated_at
            ? new Date(data.feed.generated_at)
            : null,
        checkedAt: data.feed.checked_at ? new Date(data.feed.checked_at) : null,
        error: data.feed.error
            ? {
                  message: data.feed.error.message,
                  at: new Date(data.feed.error.at),
              }
            : null,
    },
    announcements: data.announcements.map((announcement: any) => ({
        ...rawDataToAnnouncement(announcement),
        applies: announcement.applies,
        reasons: announcement.reasons,
        dismissed: announcement.dismissed,
    })),
})

export const getUpdates = async (): Promise<Updates> => {
    const { data } = await http.get('/api/admin/settings/updates')

    return rawDataToUpdates(data.data)
}

export const checkForUpdates = async (): Promise<Updates> => {
    const { data } = await http.post('/api/admin/settings/updates/check')

    return rawDataToUpdates(data.data)
}

export const useUpdatesSWR = () =>
    useSWR<Updates>('admin:settings:updates', getUpdates)
