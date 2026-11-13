import useSWR from 'swr'

import getAnnouncements, {
    AdminAnnouncements,
} from '@/api/admin/announcements/getAnnouncements'

export const getKey = () => 'admin:announcements'

/**
 * Read on every page an admin opens, so it isn't refetched on window focus: the
 * panel only learns of a new announcement once an hour anyway.
 */
const useAnnouncementsSWR = (enabled = true) =>
    useSWR<AdminAnnouncements>(enabled ? getKey() : null, getAnnouncements, {
        revalidateOnFocus: false,
        refreshInterval: 15 * 60 * 1000,
    })

export default useAnnouncementsSWR
